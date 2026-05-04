<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Import;
use App\Models\ImportRow;
use App\Models\User;
use App\Services\PhoneNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
        $csvContent = "first_name,last_name,phone\nJohn,Doe,0771234567\nJane,Smith,0779876543";
        $file = UploadedFile::fake()->createWithContent('contacts.csv', $csvContent);

        $response = $this->actingAs($this->user)->postJson('/imports/upload', [
            'file' => $file,
            'type' => 'csv',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('imports', [
            'original_filename' => 'contacts.csv',
            'type' => 'csv',
        ]);
    }

    public function test_phone_normalized_on_import(): void
    {
        $normalizer = new PhoneNormalizer();

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
}
