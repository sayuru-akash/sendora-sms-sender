<?php

namespace App\Models;

use App\Support\SmsText;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    protected $appends = ['character_count', 'sms_segments', 'sms_encoding', 'usage_count', 'created_by_name'];

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
            $body = str_replace('{'.$key.'}', (string) $value, $body);
        }

        return $body;
    }

    public function getCharacterCountAttribute(): int
    {
        return SmsText::metrics($this->body)['character_count'];
    }

    public function getSmsSegmentsAttribute(): int
    {
        return SmsText::metrics($this->body)['sms_segments'];
    }

    public function getSmsEncodingAttribute(): string
    {
        return SmsText::metrics($this->body)['encoding'];
    }

    public function getUsageCountAttribute(): int
    {
        return SmsCampaign::where('template_id', $this->id)->count();
    }

    public function getCreatedByNameAttribute(): ?string
    {
        return $this->creator?->name;
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
