<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedSegment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'filters',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
