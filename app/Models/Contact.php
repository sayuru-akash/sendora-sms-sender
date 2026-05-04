<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Contact extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'first_name',
        'last_name',
        'full_name',
        'phone',
        'phone_normalised',
        'email',
        'company',
        'job_title',
        'country',
        'district',
        'city',
        'gender',
        'date_of_birth',
        'source',
        'status',
        'notes',
        'last_contacted_at',
        'unsubscribed_at',
        'blocked_at',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'last_contacted_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
            'blocked_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['first_name', 'last_name', 'phone', 'email', 'status', 'company'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        if ($this->full_name) {
            return $this->full_name;
        }
        if ($this->first_name || $this->last_name) {
            return trim($this->first_name . ' ' . $this->last_name);
        }
        return $this->phone;
    }

    // Status helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isUnsubscribed(): bool
    {
        return $this->status === 'unsubscribed';
    }

    public function isBlocked(): bool
    {
        return $this->status === 'blocked';
    }

    public function isInvalid(): bool
    {
        return $this->status === 'invalid';
    }

    public function canReceiveSms(): bool
    {
        return in_array($this->status, ['active', 'inactive']);
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeCanReceiveSms(Builder $query): Builder
    {
        return $query->whereIn('status', ['active', 'inactive']);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeBySource(Builder $query, string $source): Builder
    {
        return $query->where('source', $source);
    }

    public function scopeByDistrict(Builder $query, string $district): Builder
    {
        return $query->where('district', $district);
    }

    public function scopeByCity(Builder $query, string $city): Builder
    {
        return $query->where('city', $city);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('full_name', 'ilike', "%{$search}%")
              ->orWhere('first_name', 'ilike', "%{$search}%")
              ->orWhere('last_name', 'ilike', "%{$search}%")
              ->orWhere('phone', 'ilike', "%{$search}%")
              ->orWhere('phone_normalised', 'ilike', "%{$search}%")
              ->orWhere('email', 'ilike', "%{$search}%")
              ->orWhere('company', 'ilike', "%{$search}%");
        });
    }

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    public function lists(): BelongsToMany
    {
        return $this->belongsToMany(ListModel::class, 'contact_list')->withTimestamps();
    }

    public function campaignRecipients(): HasMany
    {
        return $this->hasMany(CampaignRecipient::class);
    }

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    public function importRows(): HasMany
    {
        return $this->hasMany(ImportRow::class);
    }
}
