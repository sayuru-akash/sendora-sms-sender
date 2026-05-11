<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    public const EDITABLE_SETTINGS = [
        'company_name' => ['type' => 'string', 'group' => 'general'],
        'timezone' => ['type' => 'string', 'group' => 'general'],
        'date_format' => ['type' => 'string', 'group' => 'general'],
        'default_country_code' => ['type' => 'string', 'group' => 'general'],
        'max_import_file_size' => ['type' => 'integer', 'group' => 'imports'],
        'default_duplicate_handling' => ['type' => 'string', 'group' => 'imports'],
    ];

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'is_sensitive',
    ];

    protected $casts = [
        'is_sensitive' => 'boolean',
    ];

    // Static helpers
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => (bool) $setting->value,
            'json' => json_decode($setting->value, true),
            default => $setting->value,
        };
    }

    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general', bool $isSensitive = false): static
    {
        $stringValue = match ($type) {
            'json' => is_string($value) ? $value : json_encode($value),
            'boolean' => $value ? '1' : '0',
            default => (string) $value,
        };

        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type' => $type,
                'group' => $group,
                'is_sensitive' => $isSensitive,
            ]
        );
    }

    /**
     * @return array<int, string>
     */
    public static function editableKeys(): array
    {
        return array_keys(self::EDITABLE_SETTINGS);
    }

    public function getDecryptedValueAttribute(): mixed
    {
        return match ($this->type) {
            'integer' => (int) $this->value,
            'boolean' => (bool) $this->value,
            'json' => json_decode($this->value, true),
            default => $this->value,
        };
    }

    // Scopes
    public function scopeByGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    public function scopeNotSensitive(Builder $query): Builder
    {
        return $query->where('is_sensitive', false);
    }
}
