<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactImportRequest;
use App\Http\Requests\ImportMappingRequest;
use App\Jobs\ProcessImport;
use App\Models\Import;
use App\Models\ImportRow;
use App\Models\ListModel;
use App\Models\Tag;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use League\Csv\Reader;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;

class ImportController extends Controller
{
    public function __construct(
        protected ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->toString();

        $imports = $this->paginate(
            Import::with('creator')
                ->when($search !== '', fn ($q) => $q->whereRaw('LOWER(original_filename) LIKE ?', ['%'.mb_strtolower($search).'%']))
                ->when($request->status, fn ($q, $status) => $q->where('status', $status))
                ->latest(),
            $request,
            25
        );
        $imports['data'] = collect($imports['data'])
            ->map(fn (Import $import): array => $this->formatImportSummary($import))
            ->all();

        return Inertia::render('Imports/Index', [
            'imports' => $imports,
            'filters' => $request->only(['search', 'status', 'per_page']),
            'summary' => [
                'total' => Import::count(),
                'processing' => Import::whereIn('status', ['pending', 'processing'])->count(),
                'completed' => Import::where('status', 'completed')->count(),
                'failed' => Import::where('status', 'failed')->count(),
            ],
        ]);
    }

    /**
     * Show the upload form.
     */
    public function create(Request $request): Response
    {
        $lists = ListModel::active()->orderBy('name')->get(['id', 'name', 'colour']);
        $tags = Tag::orderBy('name')->get(['id', 'name', 'colour']);

        return Inertia::render('Imports/Upload', [
            'lists' => $lists,
            'tags' => $tags,
            'selected_list_ids' => $request->integer('list_id') > 0 ? [$request->integer('list_id')] : [],
            'selected_tag_ids' => $request->integer('tag_id') > 0 ? [$request->integer('tag_id')] : [],
        ]);
    }

    /**
     * Step 1: Upload file.
     */
    public function upload(ContactImportRequest $request)
    {
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $type = in_array($extension, ['xlsx', 'xls']) ? 'xlsx' : 'csv';
        $filename = Str::uuid().'.'.$extension;
        $path = $file->storeAs('imports', $filename, 'local');

        // Count rows
        $totalRows = $this->countRows($path, $type);

        $options = [
            'duplicate_handling' => $this->normaliseDuplicateHandling($request->input('duplicate_handling', 'skip')),
            'list_ids' => $this->normaliseIds($request->input('list_ids', [])),
            'tag_ids' => $this->normaliseIds($request->input('tag_ids', [])),
        ];

        $import = Import::create([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'type' => $type,
            'status' => 'uploaded',
            'total_rows' => $totalRows,
            'options' => $options,
            'list_id' => $request->input('list_id') ?? collect($options['list_ids'])->first(),
            'created_by' => $request->user()->id,
        ]);

        $this->activityLogger->logBulkImportUploaded($import);

        if ($request->expectsJson()) {
            return response()->json(['import' => $import], 201);
        }

        return redirect()->route('imports.mapping', $import);
    }

    /**
     * Step 2: Show mapping screen.
     */
    public function mapping(Import $import): Response
    {
        $headers = $this->getHeaders($import->file_path, $import->type);
        $sampleRows = $this->getSampleRows($import->file_path, $import->type, 5);
        $suggestedMapping = $this->suggestMapping($headers);
        $options = $import->options ?? [];
        $lists = ListModel::active()->orderBy('name')->get(['id', 'name', 'colour']);
        $tags = Tag::orderBy('name')->get(['id', 'name', 'colour']);

        $contactFields = [
            'first_name', 'last_name', 'full_name', 'phone', 'email',
            'company', 'job_title', 'city', 'district', 'address', 'notes',
        ];

        return Inertia::render('Imports/Mapping', [
            'import_id' => $import->id,
            'columns' => [
                'file_columns' => $headers,
                'contact_fields' => $contactFields,
                'mapping' => $suggestedMapping,
                'preview_data' => $sampleRows,
            ],
            'lists' => $lists,
            'tags' => $tags,
            'options' => [
                'duplicate_handling' => $options['duplicate_handling'] ?? 'skip',
                'list_ids' => $this->normaliseIds($options['list_ids'] ?? ($import->list_id ? [$import->list_id] : [])),
                'tag_ids' => $this->normaliseIds($options['tag_ids'] ?? []),
            ],
        ]);
    }

    /**
     * Step 3: Preview data with mapping.
     */
    public function preview(ImportMappingRequest $request, Import $import): JsonResponse
    {
        $mapping = $request->input('column_mapping');
        $this->assertMappedColumnsExist($import, $mapping);
        $sampleRows = $this->getSampleRows($import->file_path, $import->type, 10);

        $preview = [];
        foreach ($sampleRows as $row) {
            $mapped = [];
            foreach ($mapping as $field => $column) {
                if ($column && isset($row[$column])) {
                    $mapped[$field] = $row[$column];
                }
            }
            $preview[] = $mapped;
        }

        return response()->json([
            'preview' => $preview,
            'total_rows' => $import->total_rows,
        ]);
    }

    /**
     * Step 4: Confirm and process import.
     */
    public function confirm(ImportMappingRequest $request, Import $import)
    {
        $this->assertMappedColumnsExist($import, $request->input('column_mapping'));

        $import = DB::transaction(function () use ($request, $import): Import {
            $import = Import::whereKey($import->id)->lockForUpdate()->firstOrFail();

            if (! in_array($import->status, ['uploaded', 'mapping'], true)) {
                abort(422, 'This import has already been confirmed.');
            }

            $existingOptions = $import->options ?? [];
            $requestOptions = $request->input('options', []);
            $listIds = $this->normaliseIds($request->input('list_ids', $existingOptions['list_ids'] ?? ($import->list_id ? [$import->list_id] : [])));
            $tagIds = $this->normaliseIds($request->input('tag_ids', $existingOptions['tag_ids'] ?? []));
            $options = array_merge($existingOptions, $requestOptions, [
                'duplicate_handling' => $this->normaliseDuplicateHandling($requestOptions['duplicate_handling'] ?? $existingOptions['duplicate_handling'] ?? 'skip'),
                'list_ids' => $listIds,
                'tag_ids' => $tagIds,
            ]);

            $import->update([
                'column_mapping' => $request->input('column_mapping'),
                'options' => $options,
                'list_id' => $request->input('list_id') ?? collect($listIds)->first(),
                'status' => 'pending',
            ]);

            $import->rows()->delete();
            $this->createImportRows($import);

            return $import->fresh();
        });

        // Dispatch the import job
        ProcessImport::dispatch($import);

        $this->activityLogger->logBulkImportStarted($import);

        if ($request->expectsJson()) {
            return response()->json(['import' => $import->fresh()]);
        }

        return redirect()->route('imports.show', $import)
            ->with('success', 'Import started. Processing in the background.');
    }

    /**
     * Show import progress.
     */
    public function show(Import $import, Request $request): Response|JsonResponse
    {
        $import->load('creator');
        $options = $import->options ?? [];
        $listIds = $this->normaliseIds($options['list_ids'] ?? ($import->list_id ? [$import->list_id] : []));
        $tagIds = $this->normaliseIds($options['tag_ids'] ?? []);
        $selectedLists = $listIds === []
            ? collect()
            : ListModel::whereIn('id', $listIds)->orderBy('name')->get(['id', 'name', 'colour']);
        $selectedTags = $tagIds === []
            ? collect()
            : Tag::whereIn('id', $tagIds)->orderBy('name')->get(['id', 'name', 'colour']);

        $failedRowsData = $import->failedRows()
            ->get()
            ->map(fn ($row) => [
                'row_number' => $row->row_number,
                'data' => $row->raw_data ?? [],
                'error' => $row->error_message ?? 'Unknown error',
            ]);

        $importData = [
            'id' => $import->id,
            'filename' => $import->filename,
            'original_filename' => $import->original_filename,
            'file_type' => $import->type,
            'status' => $import->status,
            'total_rows' => $import->total_rows,
            'processed_rows' => $import->processed_rows ?? 0,
            'successful_rows' => $import->successful_rows ?? 0,
            'failed_rows' => $import->failed_rows ?? 0,
            'duplicate_rows' => $import->duplicate_rows ?? 0,
            'invalid_rows' => $import->invalid_rows ?? 0,
            'progress' => $import->progress_percent,
            'column_mapping' => $import->column_mapping,
            'duplicate_handling' => $options['duplicate_handling'] ?? 'skip',
            'list_ids' => $listIds,
            'tag_ids' => $tagIds,
            'lists' => $selectedLists,
            'tags' => $selectedTags,
            'phone_column' => $import->column_mapping ? collect($import->column_mapping)->flip()->get('phone') : null,
            'error_message' => null,
            'failed_rows_data' => $failedRowsData,
            'created_by' => $import->created_by,
            'created_by_name' => $import->creator?->name,
            'started_at' => $import->started_at?->toISOString(),
            'completed_at' => $import->completed_at?->toISOString(),
            'created_at' => $import->created_at?->toISOString(),
            'updated_at' => $import->updated_at?->toISOString(),
        ];

        if ($request->expectsJson()) {
            return response()->json(['import' => $importData]);
        }

        return Inertia::render('Imports/Show', [
            'import' => $importData,
        ]);
    }

    /**
     * Download failed rows as CSV.
     */
    public function downloadFailed(Import $import): StreamedResponse|\Symfony\Component\HttpFoundation\Response
    {
        $failedRows = $import->failedRows()->get();

        if ($failedRows->isEmpty()) {
            return redirect()->route('imports.show', $import)
                ->with('info', 'No failed rows to download.');
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="failed_rows_'.$import->id.'.csv"',
        ];

        return response()->stream(function () use ($failedRows) {
            $handle = fopen('php://output', 'w');

            // Get all keys from raw_data
            $allKeys = [];
            foreach ($failedRows as $row) {
                if ($row->raw_data) {
                    $allKeys = array_merge($allKeys, array_keys($row->raw_data));
                }
            }
            $allKeys = array_unique($allKeys);

            fputcsv($handle, array_merge(['Row Number', 'Error Message'], $allKeys));

            foreach ($failedRows as $row) {
                $rowData = [$row->row_number, $row->error_message];
                foreach ($allKeys as $key) {
                    $rowData[] = $row->raw_data[$key] ?? '';
                }
                fputcsv($handle, $rowData);
            }

            fclose($handle);
        }, 200, $headers);
    }

    public function destroy(Request $request, Import $import)
    {
        $import->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Import deleted successfully.']);
        }

        return redirect()->route('imports.index')
            ->with('success', 'Import deleted successfully.');
    }

    /**
     * Count rows in a file.
     */
    protected function countRows(string $path, string $type): int
    {
        $fullPath = Storage::disk('local')->path($path);

        if ($type === 'csv') {
            $csv = Reader::createFromPath($fullPath, 'r');
            $csv->setHeaderOffset(0);

            return count($csv);
        }

        // XLSX
        $reader = new XLSXReader;
        $reader->open($fullPath);
        $count = 0;
        $firstRow = true;
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                if ($firstRow) {
                    $firstRow = false;

                    continue;
                }
                $count++;
            }
            break; // Only count first sheet
        }
        $reader->close();

        return $count;
    }

    /**
     * Get headers from a file.
     */
    protected function getHeaders(string $path, string $type): array
    {
        $fullPath = Storage::disk('local')->path($path);

        if ($type === 'csv') {
            $csv = Reader::createFromPath($fullPath, 'r');
            $csv->setHeaderOffset(0);

            return $csv->getHeader();
        }

        // XLSX
        $reader = new XLSXReader;
        $reader->open($fullPath);
        $headers = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                if ($rowIndex === 1) {
                    $headers = array_map(
                        fn ($cell) => (string) $cell,
                        $row->toArray(),
                    );
                }
                break;
            }
            break;
        }
        $reader->close();

        return $headers;
    }

    /**
     * Get sample rows from a file.
     */
    protected function getSampleRows(string $path, string $type, int $limit = 5): array
    {
        $fullPath = Storage::disk('local')->path($path);

        if ($type === 'csv') {
            $csv = Reader::createFromPath($fullPath, 'r');
            $csv->setHeaderOffset(0);
            $records = [];

            foreach ($csv->getRecords() as $record) {
                $records[] = $record;

                if (count($records) >= $limit) {
                    break;
                }
            }

            return $records;
        }

        // XLSX
        $reader = new XLSXReader;
        $reader->open($fullPath);
        $rows = [];
        $headers = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $cells = array_map(
                    fn ($cell) => (string) $cell,
                    $row->toArray(),
                );
                if ($rowIndex === 1) {
                    $headers = $cells;

                    continue;
                }
                $rowData = [];
                foreach ($headers as $i => $header) {
                    $rowData[$header] = $cells[$i] ?? '';
                }
                $rows[] = $rowData;
                if (count($rows) >= $limit) {
                    break 2;
                }
            }
        }
        $reader->close();

        return $rows;
    }

    /**
     * Create import rows from file data.
     */
    protected function createImportRows(Import $import): void
    {
        $fullPath = Storage::disk('local')->path($import->file_path);
        $batch = [];
        $rowNumber = 0;

        if ($import->type === 'csv') {
            $csv = Reader::createFromPath($fullPath, 'r');
            $csv->setHeaderOffset(0);
            foreach ($csv->getRecords() as $record) {
                $rowNumber++;
                $batch[] = [
                    'import_id' => $import->id,
                    'row_number' => $rowNumber,
                    'raw_data' => json_encode($record),
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= 500) {
                    ImportRow::insert($batch);
                    $batch = [];
                }
            }
        } else {
            $reader = new XLSXReader;
            $reader->open($fullPath);
            $headers = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    $cells = array_map(
                        fn ($cell) => (string) $cell,
                        $row->toArray(),
                    );
                    if ($rowIndex === 1) {
                        $headers = $cells;

                        continue;
                    }
                    $rowNumber++;
                    $rowData = [];
                    foreach ($headers as $i => $header) {
                        $rowData[$header] = $cells[$i] ?? '';
                    }
                    $batch[] = [
                        'import_id' => $import->id,
                        'row_number' => $rowNumber,
                        'raw_data' => json_encode($rowData),
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($batch) >= 500) {
                        ImportRow::insert($batch);
                        $batch = [];
                    }
                }
            }
            $reader->close();
        }

        if (! empty($batch)) {
            ImportRow::insert($batch);
        }

        $import->update(['total_rows' => $rowNumber]);
    }

    /**
     * Suggest column mapping based on header names.
     */
    protected function suggestMapping(array $headers): array
    {
        $mapping = [];
        $fieldPatterns = [
            'phone' => ['phone', 'mobile', 'cell', 'tel', 'telephone', 'number'],
            'first_name' => ['first_name', 'firstname', 'first name', 'fname'],
            'last_name' => ['last_name', 'lastname', 'last name', 'lname', 'surname'],
            'full_name' => ['full_name', 'fullname', 'full name', 'name'],
            'email' => ['email', 'e-mail', 'email_address'],
            'company' => ['company', 'organization', 'organisation', 'business'],
            'job_title' => ['job_title', 'jobtitle', 'job title', 'position', 'designation'],
            'district' => ['district', 'province', 'region'],
            'city' => ['city', 'town'],
            'gender' => ['gender', 'sex'],
            'date_of_birth' => ['date_of_birth', 'dob', 'birthday', 'birth_date'],
            'source' => ['source', 'origin', 'channel'],
            'notes' => ['notes', 'note', 'comment', 'comments', 'remarks'],
        ];

        foreach ($headers as $header) {
            $normalizedHeader = strtolower(trim($header));
            foreach ($fieldPatterns as $field => $patterns) {
                if (in_array($normalizedHeader, $patterns)) {
                    $mapping[$field] = $header;
                    break;
                }
            }
        }

        return $mapping;
    }

    /**
     * @return array<int, int>
     */
    private function normaliseIds(mixed $ids): array
    {
        return collect($ids ?? [])
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function normaliseDuplicateHandling(?string $duplicateHandling): string
    {
        return $duplicateHandling === 'add' ? 'add_to_list' : ($duplicateHandling ?: 'skip');
    }

    /**
     * @param  array<string, string|null>  $mapping
     */
    private function assertMappedColumnsExist(Import $import, array $mapping): void
    {
        $headers = $this->getHeaders($import->file_path, $import->type);
        $missing = collect($mapping)
            ->filter(fn ($column) => is_string($column) && $column !== '' && ! in_array($column, $headers, true))
            ->keys()
            ->mapWithKeys(fn ($field) => ["column_mapping.{$field}" => 'Selected column does not exist in the uploaded file.'])
            ->all();

        if ($missing !== []) {
            throw ValidationException::withMessages($missing);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function formatImportSummary(Import $import): array
    {
        $options = $import->options ?? [];
        $listIds = $this->normaliseIds($options['list_ids'] ?? ($import->list_id ? [$import->list_id] : []));
        $tagIds = $this->normaliseIds($options['tag_ids'] ?? []);

        return [
            'id' => $import->id,
            'filename' => $import->filename,
            'original_filename' => $import->original_filename,
            'file_type' => $import->type,
            'status' => $import->status,
            'total_rows' => $import->total_rows,
            'processed_rows' => $import->processed_rows ?? 0,
            'successful_rows' => $import->successful_rows ?? 0,
            'failed_rows' => $import->failed_rows ?? 0,
            'duplicate_rows' => $import->duplicate_rows ?? 0,
            'invalid_rows' => $import->invalid_rows ?? 0,
            'progress' => $import->progress_percent,
            'duplicate_handling' => $options['duplicate_handling'] ?? 'skip',
            'list_ids' => $listIds,
            'tag_ids' => $tagIds,
            'lists' => $listIds === [] ? [] : ListModel::whereIn('id', $listIds)->orderBy('name')->get(['id', 'name', 'colour']),
            'tags' => $tagIds === [] ? [] : Tag::whereIn('id', $tagIds)->orderBy('name')->get(['id', 'name', 'colour']),
            'created_by' => $import->created_by,
            'created_by_name' => $import->creator?->name,
            'started_at' => $import->started_at?->toISOString(),
            'completed_at' => $import->completed_at?->toISOString(),
            'created_at' => $import->created_at?->toISOString(),
            'updated_at' => $import->updated_at?->toISOString(),
        ];
    }
}
