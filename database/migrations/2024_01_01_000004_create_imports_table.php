<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('imports', function (Blueprint $table) {
            $table->id();
            $table->string('filename');
            $table->string('original_filename');
            $table->string('file_path');
            $table->enum('type', ['csv', 'xlsx'])->default('csv');
            $table->enum('status', ['uploaded', 'mapping', 'pending', 'processing', 'completed', 'failed', 'cancelled'])->default('uploaded');
            $table->integer('total_rows')->default(0);
            $table->integer('processed_rows')->default(0);
            $table->integer('successful_rows')->default(0);
            $table->integer('failed_rows')->default(0);
            $table->integer('duplicate_rows')->default(0);
            $table->integer('invalid_rows')->default(0);
            $table->json('column_mapping')->nullable();
            $table->json('options')->nullable();
            $table->foreignId('list_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained()->cascadeOnDelete();
            $table->integer('row_number');
            $table->json('raw_data')->nullable();
            $table->enum('status', ['pending', 'processed', 'failed', 'skipped'])->default('pending');
            $table->text('error_message')->nullable();
            $table->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['import_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
        Schema::dropIfExists('imports');
    }
};
