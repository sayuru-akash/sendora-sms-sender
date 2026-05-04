<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('sms_campaigns')->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('phone_normalised', 20);
            $table->text('personalised_message')->nullable();
            $table->enum('status', ['pending', 'queued', 'sent', 'failed', 'skipped'])->default('pending');
            $table->string('skip_reason')->nullable();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->string('provider_message_id')->nullable();
            $table->json('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('attempt_count')->default(0);
            $table->timestamps();

            $table->index(['campaign_id', 'status']);
            $table->index('phone_normalised');
            $table->index('sent_at');
            $table->unique(['campaign_id', 'phone_normalised'], 'campaign_phone_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_recipients');
    }
};
