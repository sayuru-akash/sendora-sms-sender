<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactImportRequest;
use App\Http\Requests\ImportMappingRequest;
use App\Jobs\ProcessImport;
use App\Models\Import;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $imports = Import::with('creator')
            ->when($request->search, fn ($q, $search) => $q->where('original_filename', 'ilike', "%{$search}%"))
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate($request->get('per_page', 25));

        return Inertia::render('Imports/Index', [
            'imports' => $imports,
            'filters' => $request->only(['search', 'status', 'per_page']),
        ]);
    }

    /**
     * Step 1: Upload file.
     */
    public function upload(ContactImportRequest $request)
    {
        $file = $request->file('file');
        $type = $request->input('type', 'csv');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('imports', $filename, 'local');

        // Count rows
        $totalRows = $this->countRows($path, $type);

        $import = Import::create([
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $path,
            'type' => $type,
            'status' => 'uploaded',
            'total_rows' => $totalRows,
            'list_id' => $request->input('list_id'),
            'created_by' => $request->user()->id,
        ]);

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

        return Inertia::render('Imports/Mapping', [
            'import' => $import,
            'headers' => $headers,
            'sample_rows' => $sampleRows,
            'suggested_mapping' => $suggestedMapping,
        ]);
    }

    /**
     * Step 3: Preview data with mapping.
     */
    public function preview(ImportMappingRequest $request, Import $import): JsonResponse
    {
        $mapping = $request->input('column_mapping');
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
        $import->update([
            'column_mapping' => $request->input('column_mapping'),
            'options' => $request->input('options', []),
            'list_id' => $request->input('list_id', $import->list_id),
            'status' => 'pending',
        ]);

        // Create import rows
        $this->createImportRows($import);

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

        if ($request->expectsJson()) {
            return response()->json(['import' => $import]);
        }

        return Inertia::render('Imports/Show', [
            'import' => $import,
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
            'Content-Disposition' => 'attachment; filename="failed_rows_' . $import->id . '.csv"',
        ];

        return response()->stream(function () use ($failedRows, $import) {
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
        $reader = new XLSXReader();
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
        $reader = new XLSXReader();
        $reader->open($fullPath);
        $headers = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                if ($rowIndex === 1) {
                    $headers = $row->getCells();
                    $headers = array_map(fn ($cell) => (string) $cell, $headers);
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
            $records = iterator_to_array($csv->getRecords());
            return array_slice($records, 0, $limit);
        }

        // XLSX
        $reader = new XLSXReader();
        $reader->open($fullPath);
        $rows = [];
        $headers = [];
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                $cells = $row->getCells();
                $cells = array_map(fn ($cell) => (string) $cell, $cells);
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
                    'raw_data' => $record,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($batch) >= 500) {
                    \App\Models\ImportRow::insert($batch);
                    $batch = [];
                }
            }
        } else {
            $reader = new XLSXReader();
            $reader->open($fullPath);
            $headers = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $rowIndex => $row) {
                    $cells = $row->getCells();
                    $cells = array_map(fn ($cell) => (string) $cell, $cells);
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
                        'raw_data' => $rowData,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    if (count($batch) >= 500) {
                        \App\Models\ImportRow::insert($batch);
                        $batch = [];
                    }
                }
            }
            $reader->close();
        }

        if (!empty($batch)) {
            \App\Models\ImportRow::insert($batch);
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
}
