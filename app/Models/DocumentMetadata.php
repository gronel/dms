<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentMetadata extends Model
{
    protected $table = 'document_metadata';

    protected $fillable = [
        'document_id',
        'key',
        'value',
        'value_type',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * Return the value cast to its declared type.
     */
    public function getCastValueAttribute(): mixed
    {
        return match ($this->value_type) {
            'number'  => is_numeric($this->value) && str_contains($this->value, '.') ? (float) $this->value : (int) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            default   => $this->value,
        };
    }
}
