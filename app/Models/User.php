<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class User extends Authenticatable
{
    use HasFactory, Notifiable, LogsActivity;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'role', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    // Role checks
    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    public function isManager(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'manager']);
    }

    public function isStaff(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'manager', 'staff']);
    }

    public function isViewer(): bool
    {
        return $this->role === 'viewer';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    // Permission checks
    public function canManageUsers(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    public function canManageSettings(): bool
    {
        return in_array($this->role, ['owner', 'admin']);
    }

    public function canSendCampaigns(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'manager']);
    }

    public function canImportContacts(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'manager', 'staff']);
    }

    public function canCreateCampaigns(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'manager', 'staff']);
    }

    // Relationships
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class, 'created_by');
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(SmsCampaign::class, 'created_by');
    }

    public function imports(): HasMany
    {
        return $this->hasMany(Import::class, 'created_by');
    }

    public function tags(): HasMany
    {
        return $this->hasMany(Tag::class, 'created_by');
    }

    public function lists(): HasMany
    {
        return $this->hasMany(ListModel::class, 'created_by');
    }
}
