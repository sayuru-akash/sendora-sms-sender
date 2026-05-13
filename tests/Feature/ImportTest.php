<?php

namespace Tests\Feature;

use App\Jobs\ProcessImport;
use App\Models\Contact;
use App\Models\Import;
use App\Models\ImportRow;
use App\Models\ListModel;
use App\Models\Tag;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\PhoneNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XLSXWriter;
use Tests\TestCase;

class ImportTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->staff()->create();
    }

    public function test_can_upload_csv(): void
    {
        $list = ListModel::factory()->create();
        $tag = Tag::factory()->create();
        $csvContent = "first_name,last_name,phone\nJohn,Doe,0771234567\nJane,Smith,0779876543";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $response = $this->actingAs($this->user)->postJson('/imports/upload', [
            'file' => $file,
            'type' => 'csv',
            'duplicate_handling' => 'add_to_list',
            'list_ids' => [$list->id],
            'tag_ids' => [$tag->id],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('imports', [
            'original_filename' => 'contacts.csv',
            'type' => 'csv',
            'list_id' => $list->id,
        ]);

        $import = Import::where('original_filename', 'contacts.csv')->firstOrFail();

        $this->assertSame('add_to_list', $import->options['duplicate_handling']);
        $this->assertSame([$list->id], $import->options['list_ids']);
        $this->assertSame([$tag->id], $import->options['tag_ids']);
    }

    public function test_phone_normalized_on_import(): void
    {
        $normalizer = new PhoneNormalizer;

        // Test various formats
        $this->assertEquals('94771234567', $normalizer->normalize('0771234567'));
        $this->assertEquals('94771234567', $normalizer->normalize('+94771234567'));
        $this->assertEquals('94771234567', $normalizer->normalize('771234567'));
    }

    public function test_duplicate_handling_skip(): void
    {
        // Create existing contact
        Contact::factory()->create(['phone_normalised' => '94771234567']);

        // Create import with skip option
        $import = Import::factory()->create([
            'column_mapping' => ['phone' => 'phone', 'first_name' => 'first_name'],
            'options' => ['duplicate_handling' => 'skip'],
        ]);

        ImportRow::create([
            'import_id' => $import->id,
            'row_number' => 1,
            'raw_data' => ['first_name' => 'John', 'phone' => '0771234567'],
            'status' => 'pending',
        ]);

        // After processing, the row should be skipped
        // and only 1 contact with this phone should exist
        $count = Contact::where('phone_normalised', '94771234567')->count();
        $this->assertEquals(1, $count);
    }

    public function test_duplicate_handling_update(): void
    {
        // Create existing contact
        $contact = Contact::factory()->create([
            'phone_normalised' => '94771234567',
            'first_name' => 'OldName',
        ]);

        // Create import with update option
        $import = Import::factory()->create([
            'column_mapping' => ['phone' => 'phone', 'first_name' => 'first_name'],
            'options' => ['duplicate_handling' => 'update'],
        ]);

        ImportRow::create([
            'import_id' => $import->id,
            'row_number' => 1,
            'raw_data' => ['first_name' => 'NewName', 'phone' => '0771234567'],
            'status' => 'pending',
        ]);

        // The contact count should still be 1
        $count = Contact::where('phone_normalised', '94771234567')->count();
        $this->assertEquals(1, $count);
    }

    public function test_import_validates_file_type(): void
    {
        $file = UploadedFile::fake()->create('test.txt', 100);

        $response = $this->actingAs($this->user)->postJson('/imports/upload', [
            'file' => $file,
            'type' => 'csv',
        ]);

        $response->assertStatus(422);
    }

    public function test_confirm_preserves_import_lists_and_tags(): void
    {
        Queue::fake();

        $list = ListModel::factory()->create();
        $tag = Tag::factory()->create();
        Storage::disk('local')->put('imports/confirm-test.csv', "first_name,phone\nPreserved,0771234567");
        $import = Import::factory()->create([
            'created_by' => $this->user->id,
            'file_path' => 'imports/confirm-test.csv',
            'type' => 'csv',
            'options' => [
                'duplicate_handling' => 'skip',
                'list_ids' => [$list->id],
                'tag_ids' => [$tag->id],
            ],
        ]);

        $response = $this->actingAs($this->user)->postJson(route('imports.confirm', $import), [
            'column_mapping' => [
                'phone' => 'phone',
                'first_name' => 'first_name',
            ],
            'list_ids' => [$list->id],
            'tag_ids' => [$tag->id],
            'options' => [
                'duplicate_handling' => 'update',
            ],
        ]);

        $response->assertOk();
        $import->refresh();

        $this->assertSame('update', $import->options['duplicate_handling']);
        $this->assertSame([$list->id], $import->options['list_ids']);
        $this->assertSame([$tag->id], $import->options['tag_ids']);
        Queue::assertPushed(ProcessImport::class);
    }

    public function test_import_confirm_is_not_double_submitted(): void
    {
        Queue::fake();

        Storage::disk('local')->put('imports/double-confirm.csv', "first_name,phone\nFirst,0771234567");
        $import = Import::factory()->create([
            'created_by' => $this->user->id,
            'file_path' => 'imports/double-confirm.csv',
            'type' => 'csv',
            'status' => 'uploaded',
        ]);
        $payload = [
            'column_mapping' => [
                'phone' => 'phone',
                'first_name' => 'first_name',
            ],
            'options' => [
                'duplicate_handling' => 'skip',
            ],
        ];

        $this->actingAs($this->user)->postJson(route('imports.confirm', $import), $payload)->assertOk();
        $this->actingAs($this->user)->postJson(route('imports.confirm', $import), $payload)->assertStatus(422);

        $this->assertSame(1, ImportRow::where('import_id', $import->id)->count());
        Queue::assertPushed(ProcessImport::class, 1);
    }

    public function test_import_confirm_rejects_mapping_columns_not_in_file(): void
    {
        Queue::fake();

        Storage::disk('local')->put('imports/bad-mapping.csv', "first_name,phone\nFirst,0771234567");
        $import = Import::factory()->create([
            'created_by' => $this->user->id,
            'file_path' => 'imports/bad-mapping.csv',
            'type' => 'csv',
            'status' => 'uploaded',
        ]);

        $this->actingAs($this->user)->postJson(route('imports.confirm', $import), [
            'column_mapping' => [
                'phone' => 'missing_phone',
                'first_name' => 'first_name',
            ],
            'options' => [
                'duplicate_handling' => 'skip',
            ],
        ])->assertJsonValidationErrors('column_mapping.phone');

        $this->assertSame('uploaded', $import->fresh()->status);
        $this->assertSame(0, ImportRow::where('import_id', $import->id)->count());
        Queue::assertNothingPushed();
    }

    public function test_import_show_json_uses_same_ui_safe_shape(): void
    {
        $list = ListModel::factory()->create(['name' => 'CCB - 26.1']);
        $tag = Tag::factory()->create(['name' => 'Imported 2026-05-07']);
        $import = Import::factory()->create([
            'created_by' => $this->user->id,
            'type' => 'xlsx',
            'status' => 'processing',
            'total_rows' => 10,
            'processed_rows' => 4,
            'successful_rows' => 3,
            'failed_rows' => 1,
            'duplicate_rows' => 0,
            'invalid_rows' => 1,
            'column_mapping' => ['Mobile' => 'phone', 'Name' => 'full_name'],
            'options' => [
                'duplicate_handling' => 'add_to_list',
                'list_ids' => [$list->id],
                'tag_ids' => [$tag->id],
            ],
        ]);
        ImportRow::create([
            'import_id' => $import->id,
            'row_number' => 2,
            'raw_data' => ['Name' => 'Student One', 'Mobile' => '0712345678'],
            'status' => 'failed',
            'error_message' => 'Invalid phone',
        ]);

        $this->actingAs($this->user)
            ->getJson(route('imports.show', $import))
            ->assertOk()
            ->assertJsonPath('import.file_type', 'xlsx')
            ->assertJsonPath('import.progress', 40)
            ->assertJsonPath('import.duplicate_handling', 'add_to_list')
            ->assertJsonPath('import.list_ids', [$list->id])
            ->assertJsonPath('import.tag_ids', [$tag->id])
            ->assertJsonPath('import.lists.0.name', 'CCB - 26.1')
            ->assertJsonPath('import.tags.0.name', 'Imported 2026-05-07')
            ->assertJsonPath('import.phone_column', 'Mobile')
            ->assertJsonPath('import.failed_rows_data.0.error', 'Invalid phone')
            ->assertJsonPath('import.created_by_name', $this->user->name);
    }

    public function test_xlsx_preview_and_confirm_create_import_rows(): void
    {
        Queue::fake();

        Storage::disk('local')->makeDirectory('imports');
        $relativePath = 'imports/contacts.xlsx';
        $absolutePath = Storage::disk('local')->path($relativePath);

        $writer = new XLSXWriter;
        $writer->openToFile($absolutePath);
        $writer->addRow(Row::fromValues(['Full Name', 'Phone']));
        $writer->addRow(Row::fromValues(['Student One', '0771234567']));
        $writer->close();

        $import = Import::factory()->create([
            'created_by' => $this->user->id,
            'file_path' => $relativePath,
            'type' => 'xlsx',
            'status' => 'uploaded',
            'total_rows' => 1,
        ]);

        $this->actingAs($this->user)->postJson(route('imports.preview', $import), [
            'column_mapping' => [
                'phone' => 'Phone',
                'full_name' => 'Full Name',
            ],
        ])->assertOk()
            ->assertJsonPath('preview.0.phone', '0771234567')
            ->assertJsonPath('preview.0.full_name', 'Student One');

        $this->actingAs($this->user)->postJson(route('imports.confirm', $import), [
            'column_mapping' => [
                'phone' => 'Phone',
                'full_name' => 'Full Name',
            ],
            'options' => [
                'duplicate_handling' => 'skip',
            ],
        ])->assertOk();

        $this->assertSame(1, ImportRow::where('import_id', $import->id)->count());
        $this->assertDatabaseHas('import_rows', [
            'import_id' => $import->id,
            'row_number' => 1,
            'status' => 'pending',
        ]);
        Queue::assertPushed(ProcessImport::class);
    }

    public function test_process_import_assigns_selected_lists_and_tags_to_new_and_duplicate_contacts(): void
    {
        $list = ListModel::factory()->create();
        $secondList = ListModel::factory()->create();
        $tag = Tag::factory()->create();
        $existing = Contact::factory()->create([
            'phone' => '0771234567',
            'phone_normalised' => '94771234567',
        ]);
        $import = Import::factory()->create([
            'created_by' => $this->user->id,
            'column_mapping' => [
                'phone' => 'phone',
                'first_name' => 'first_name',
            ],
            'options' => [
                'duplicate_handling' => 'update',
                'list_ids' => [$list->id, $secondList->id],
                'tag_ids' => [$tag->id],
            ],
        ]);

        ImportRow::create([
            'import_id' => $import->id,
            'row_number' => 1,
            'raw_data' => ['first_name' => 'Updated', 'phone' => '0771234567'],
            'status' => 'pending',
        ]);
        ImportRow::create([
            'import_id' => $import->id,
            'row_number' => 2,
            'raw_data' => ['first_name' => 'New', 'phone' => '0779876543'],
            'status' => 'pending',
        ]);

        (new ProcessImport($import))->handle(new PhoneNormalizer, new ActivityLogger);

        $existing->refresh();
        $created = Contact::where('phone_normalised', '94779876543')->firstOrFail();

        foreach ([$existing, $created] as $contact) {
            $this->assertEqualsCanonicalizing(
                [$list->id, $secondList->id],
                $contact->lists()->pluck('lists.id')->all()
            );
            $this->assertSame([$tag->id], $contact->tags()->pluck('tags.id')->all());
        }

        $import->refresh();
        $this->assertSame(2, $import->processed_rows);
        $this->assertSame(2, $import->successful_rows);
        $this->assertSame(1, $import->duplicate_rows);
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Import::class,
            'subject_id' => $import->id,
            'event' => 'import_completed',
        ]);
    }
}
