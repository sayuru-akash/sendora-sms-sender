<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('full_name')->nullable();
            $table->string('phone', 30);
            $table->string('phone_normalised', 20)->unique();
            $table->string('email')->nullable();
            $table->string('company')->nullable();
            $table->string('job_title')->nullable();
            $table->string('country')->nullable()->default('Sri Lanka');
            $table->string('district')->nullable();
            $table->string('city')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('source')->nullable();
            $table->enum('status', ['active', 'inactive', 'unsubscribed', 'blocked', 'invalid', 'bounced'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('phone_normalised');
            $table->index('email');
            $table->index('status');
            $table->index('source');
            $table->index('company');
            $table->index('district');
            $table->index('city');
            $table->index('created_at');
            $table->index('last_contacted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
