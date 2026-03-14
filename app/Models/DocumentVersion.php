<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'version_number',
        'storage_path',
        'storage_disk',
        'file_name',
        'mime_type',
        'size_bytes',
        'checksum_sha256',
        'uploaded_by',
        'change_summary',
        'is_current',
    ];

    protected function casts(): array
    {
        return [
            'is_current'  => 'boolean',
            'created_at'  => 'datetime',
            'size_bytes'  => 'integer',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Human-readable file size (e.g., "2.4 MB").
     */
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->size_bytes;

        if ($bytes < 1_024) {
            return "{$bytes} B";
        } elseif ($bytes < 1_048_576) {
            return round($bytes / 1_024, 1).' KB';
        } elseif ($bytes < 1_073_741_824) {
            return round($bytes / 1_048_576, 1).' MB';
        }

        return round($bytes / 1_073_741_824, 2).' GB';
    }
}
