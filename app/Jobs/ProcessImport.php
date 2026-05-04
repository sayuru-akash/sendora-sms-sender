<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Import;
use App\Models\ImportRow;
use App\Models\ListModel;
use App\Services\ActivityLogger;
use App\Services\PhoneNormalizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProcessImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 3600; // 1 hour

    public function __construct(
        public Import $import,
    ) {}

    public function handle(PhoneNormalizer $phoneNormalizer, ActivityLogger $activityLogger): void
    {
        $this->import->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);

        $mapping = $this->import->column_mapping ?? [];
        $options = $this->import->options ?? [];
        $duplicateHandling = $options['duplicate_handling'] ?? 'skip';
        $defaultStatus = $options['default_status'] ?? 'active';
        $defaultSource = $options['default_source'] ?? 'import';

        $successful = 0;
        $failed = 0;
        $duplicates = 0;
        $invalid = 0;
        $processed = 0;

        try {
            $rows = ImportRow::where('import_id', $this->import->id)
                ->where('status', 'pending')
                ->orderBy('id')
                ->cursor();

            foreach ($rows as $row) {
                $processed++;

                try {
                    $rawData = $row->raw_data ?? [];

                    // Map fields
                    $contactData = [];
                    foreach ($mapping as $field => $column) {
                        if ($column && isset($rawData[$column])) {
                            $contactData[$field] = trim($rawData[$column]);
                        }
                    }

                    // Validate phone
                    $phone = $contactData['phone'] ?? '';
                    if (empty($phone)) {
                        $row->update(['status' => 'failed', 'error_message' => 'Phone number is empty.']);
                        $invalid++;
                        $this->updateImportCounters($processed, $successful, $failed, $duplicates, $invalid);
                        continue;
                    }

                    $normalised = $phoneNormalizer->normalize($phone);

                    if (!$phoneNormalizer->validate($normalised)) {
                        $row->update([
                            'status' => 'failed',
                            'error_message' => $phoneNormalizer->getValidationError($phone) ?? 'Invalid phone number.',
                        ]);
                        $invalid++;
                        $this->updateImportCounters($processed, $successful, $failed, $duplicates, $invalid);
                        continue;
                    }

                    // Check duplicates
                    $existingContact = Contact::where('phone_normalised', $normalised)->first();

                    if ($existingContact) {
                        if ($duplicateHandling === 'skip') {
                            $row->update(['status' => 'skipped', 'error_message' => 'Duplicate phone number.']);
                            $duplicates++;
                            $this->updateImportCounters($processed, $successful, $failed, $duplicates, $invalid);
                            continue;
                        } elseif ($duplicateHandling === 'update') {
                            DB::transaction(function () use ($existingContact, $contactData, $defaultStatus, $defaultSource, $normalised) {
                                $existingContact->update(array_filter([
                                    'first_name' => $contactData['first_name'] ?? $existingContact->first_name,
                                    'last_name' => $contactData['last_name'] ?? $existingContact->last_name,
                                    'full_name' => $contactData['full_name'] ?? $existingContact->full_name,
                                    'email' => $contactData['email'] ?? $existingContact->email,
                                    'company' => $contactData['company'] ?? $existingContact->company,
                                    'job_title' => $contactData['job_title'] ?? $existingContact->job_title,
                                    'district' => $contactData['district'] ?? $existingContact->district,
                                    'city' => $contactData['city'] ?? $existingContact->city,
                                    'gender' => $contactData['gender'] ?? $existingContact->gender,
                                    'date_of_birth' => $contactData['date_of_birth'] ?? $existingContact->date_of_birth,
                                    'notes' => $contactData['notes'] ?? $existingContact->notes,
                                ], fn ($v) => $v !== null && $v !== ''));

                                // Add to list if configured
                                if ($this->import->list_id) {
                                    $existingContact->lists()->syncWithoutDetaching([$this->import->list_id]);
                                }
                            });

                            $row->update(['status' => 'processed', 'contact_id' => $existingContact->id]);
                            $successful++;
                            $duplicates++; // counted as duplicate but still updated
                        } elseif ($duplicateHandling === 'add_to_list') {
                            if ($this->import->list_id) {
                                $existingContact->lists()->syncWithoutDetaching([$this->import->list_id]);
                            }
                            $row->update(['status' => 'processed', 'contact_id' => $existingContact->id]);
                            $duplicates++;
                        }
                    } else {
                        // Create new contact
                        $contact = DB::transaction(function () use ($contactData, $normalised, $defaultStatus, $defaultSource) {
                            $contact = Contact::create([
                                'uuid' => Str::uuid(),
                                'first_name' => $contactData['first_name'] ?? null,
                                'last_name' => $contactData['last_name'] ?? null,
                                'full_name' => $contactData['full_name'] ?? trim(($contactData['first_name'] ?? '') . ' ' . ($contactData['last_name'] ?? '')),
                                'phone' => $contactData['phone'],
                                'phone_normalised' => $normalised,
                                'email' => $contactData['email'] ?? null,
                                'company' => $contactData['company'] ?? null,
                                'job_title' => $contactData['job_title'] ?? null,
                                'district' => $contactData['district'] ?? null,
                                'city' => $contactData['city'] ?? null,
                                'gender' => $contactData['gender'] ?? null,
                                'date_of_birth' => !empty($contactData['date_of_birth']) ? $contactData['date_of_birth'] : null,
                                'source' => $contactData['source'] ?? $defaultSource,
                                'status' => $defaultStatus,
                                'notes' => $contactData['notes'] ?? null,
                                'created_by' => $this->import->created_by,
                                'updated_by' => $this->import->created_by,
                            ]);

                            // Assign to import list
                            if ($this->import->list_id) {
                                $contact->lists()->syncWithoutDetaching([$this->import->list_id]);
                            }

                            return $contact;
                        });

                        $row->update(['status' => 'processed', 'contact_id' => $contact->id]);
                        $successful++;
                    }
                } catch (\Exception $e) {
                    $row->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
                    $failed++;
                }

                // Update counters every 100 rows
                if ($processed % 100 === 0) {
                    $this->updateImportCounters($processed, $successful, $failed, $duplicates, $invalid);
                }
            }

            $this->import->update([
                'status' => 'completed',
                'completed_at' => now(),
                'processed_rows' => $processed,
                'successful_rows' => $successful,
                'failed_rows' => $failed,
                'duplicate_rows' => $duplicates,
                'invalid_rows' => $invalid,
            ]);

            $activityLogger->logBulkImportCompleted($this->import);
        } catch (\Exception $e) {
            $this->import->update([
                'status' => 'failed',
                'completed_at' => now(),
                'processed_rows' => $processed,
                'successful_rows' => $successful,
                'failed_rows' => $failed,
                'duplicate_rows' => $duplicates,
                'invalid_rows' => $invalid,
            ]);

            \Illuminate\Support\Facades\Log::error('Import failed', [
                'import_id' => $this->import->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function updateImportCounters(int $processed, int $successful, int $failed, int $duplicates, int $invalid): void
    {
        $this->import->update([
            'processed_rows' => $processed,
            'successful_rows' => $successful,
            'failed_rows' => $failed,
            'duplicate_rows' => $duplicates,
            'invalid_rows' => $invalid,
        ]);
    }
}
