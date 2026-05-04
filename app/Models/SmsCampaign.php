<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SmsCampaign extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $table = 'sms_campaigns';

    protected $fillable = [
        'uuid',
        'name',
        'message_body',
        'sender_id',
        'target_type',
        'target_filters',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'total_recipients',
        'queued_count',
        'sent_count',
        'failed_count',
        'skipped_count',
        'pending_count',
        'created_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'target_filters' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'status', 'target_type', 'total_recipients', 'sent_count', 'failed_count'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Status transition methods
    public function markQueued(): bool
    {
        return $this->update(['status' => 'queued']);
    }

    public function markSending(): bool
    {
        return $this->update([
            'status' => 'sending',
            'started_at' => now(),
        ]);
    }

    public function markPaused(): bool
    {
        return $this->update(['status' => 'paused']);
    }

    public function markCompleted(): bool
    {
        return $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markFailed(?string $reason = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);
    }

    public function markCancelled(): bool
    {
        return $this->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);
    }

    // Status checks
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isScheduled(): bool
    {
        return $this->status === 'scheduled';
    }

    public function isQueued(): bool
    {
        return $this->status === 'queued';
    }

    public function isSending(): bool
    {
        return $this->status === 'sending';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeSent(): bool
    {
        return in_array($this->status, ['draft', 'scheduled', 'queued']);
    }

    public function canBePaused(): bool
    {
        return in_array($this->status, ['queued', 'sending']);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['draft', 'scheduled', 'queued', 'paused', 'sending']);
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->total_recipients === 0) return 0;
        return round(($this->sent_count / $this->total_recipients) * 100, 1);
    }

    public function getFailureRateAttribute(): float
    {
        if ($this->total_recipients === 0) return 0;
        return round(($this->failed_count / $this->total_recipients) * 100, 1);
    }

    public function getSmsSegmentEstimateAttribute(): int
    {
        $length = mb_strlen($this->message_body);
        if ($length <= 160) return 1;
        if ($length <= 306) return 2;
        return (int) ceil($length / 153);
    }

    // Scopes
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'ilike', "%{$search}%");
    }

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class, 'campaign_id');
    }

    public function sentRecipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class, 'campaign_id')
            ->where('status', 'sent');
    }

    public function failedRecipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class, 'campaign_id')
            ->where('status', 'failed');
    }

    public function skippedRecipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class, 'campaign_id')
            ->where('status', 'skipped');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SmsMessage::class, 'campaign_id');
    }
}
