<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ListModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lists';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'colour',
        'status',
        'created_by',
    ];

    protected $appends = ['color'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($list) {
            if (empty($list->slug)) {
                $list->slug = Str::slug($list->name);
            }
        });

        static::updating(function ($list) {
            if ($list->isDirty('name') && ! $list->isDirty('slug')) {
                $list->slug = Str::slug($list->name);
            }
        });
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'ilike', "%{$search}%")
            ->orWhere('description', 'ilike', "%{$search}%");
    }

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function contacts(): BelongsToMany
    {
        return $this->belongsToMany(Contact::class, 'contact_list', 'list_id', 'contact_id')->withTimestamps();
    }

    public function imports(): HasMany
    {
        return $this->hasMany(Import::class, 'list_id');
    }

    public function getColorAttribute(): string
    {
        return $this->colour;
    }

    public function getContactCountAttribute(): int
    {
        return $this->contacts()->count();
    }
}
