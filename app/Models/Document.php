<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Document extends Model
{
    use HasFactory, HasUlids, Searchable, SoftDeletes;

    protected $fillable = [
        'ulid',
        'title',
        'description',
        'owner_id',
        'folder_id',
        'status',
        'is_locked',
        'locked_by',
    ];

    protected function casts(): array
    {
        return [
            'is_locked' => 'boolean',
        ];
    }

    /**
     * The column used as the primary ULID.
     */
    public function uniqueIds(): array
    {
        return ['ulid'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class)->orderByDesc('version_number');
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(DocumentVersion::class)->where('is_current', true);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'document_tag');
    }

    public function metadata(): HasMany
    {
        return $this->hasMany(DocumentMetadata::class)->orderBy('key');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->orderByDesc('created_at');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(DocumentPermission::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(DocumentComment::class)->orderByDesc('created_at');
    }

    /**
     * Get the indexable data array for the model.
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing(['owner', 'folder', 'currentVersion', 'tags', 'metadata']);

        $version = $this->currentVersion;

        return [
            'id'                    => $this->id,
            'ulid'                  => $this->ulid,
            'title'                 => $this->title,
            'description'           => $this->description,
            'owner_id'              => $this->owner_id,
            'owner_name'            => $this->owner?->name,
            'folder_id'             => $this->folder_id,
            'folder_name'           => $this->folder?->name,
            'status'                => $this->status,
            'is_locked'             => (bool) $this->is_locked,
            'file_name'             => $version?->file_name,
            'mime_type'             => $version?->mime_type,
            'tag_ids'               => $this->tags->pluck('id')->all(),
            'tag_names'             => $this->tags->pluck('name')->all(),
            'metadata_values'       => $this->metadata->pluck('value')->all(),
            'created_at_timestamp'  => $this->created_at?->timestamp,
            'updated_at_timestamp'  => $this->updated_at?->timestamp,
        ];
    }

    /**
     * Scope search results to documents owned by a given user.
     */
    public function scopeOwnedBy(\Illuminate\Database\Eloquent\Builder $query, int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('owner_id', $userId);
    }
}
