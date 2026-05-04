<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class SmsTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'category',
        'body',
        'variables',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
        ];
    }

    // Template processing
    public function render(array $data = []): string
    {
        $body = $this->body;
        foreach ($data as $key => $value) {
            $body = str_replace('{' . $key . '}', (string) $value, $body);
        }
        return $body;
    }

    public function getCharacterCountAttribute(): int
    {
        return mb_strlen($this->body);
    }

    public function getSmsSegmentCountAttribute(): int
    {
        $length = $this->character_count;
        if ($length <= 160) return 1;
        if ($length <= 306) return 2;
        return (int) ceil($length / 153);
    }

    public function getAvailableVariablesAttribute(): array
    {
        if ($this->variables) {
            return $this->variables;
        }
        preg_match_all('/\{(\w+)\}/', $this->body, $matches);
        return $matches[1] ?? [];
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where('name', 'ilike', "%{$search}%")
            ->orWhere('body', 'ilike', "%{$search}%");
    }

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
