<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\User;

class DocumentPolicy
{
    public function view(User $user, Document $document): bool
    {
        if ($document->owner_id === $user->id) {
            return true;
        }

        return $this->hasActivePermission($user->id, $document->id, ['view', 'download', 'edit']);
    }

    public function download(User $user, Document $document): bool
    {
        if ($document->owner_id === $user->id) {
            return true;
        }

        return $this->hasActivePermission($user->id, $document->id, ['download', 'edit']);
    }

    public function update(User $user, Document $document): bool
    {
        if ($document->owner_id === $user->id) {
            return ! $document->is_locked;
        }

        return ! $document->is_locked
            && $this->hasActivePermission($user->id, $document->id, ['edit']);
    }

    public function delete(User $user, Document $document): bool
    {
        return $document->owner_id === $user->id;
    }

    public function share(User $user, Document $document): bool
    {
        // Only the owner can manage share permissions
        return $document->owner_id === $user->id;
    }

    public function lock(User $user, Document $document): bool
    {
        // Only the owner can lock or unlock a document
        return $document->owner_id === $user->id;
    }

    /** ── Helpers ──────────────────────────────────────────────────── */

    private function hasActivePermission(int $userId, int $documentId, array $levels): bool
    {
        return DocumentPermission::query()
            ->where('document_id', $documentId)
            ->where('user_id', $userId)
            ->whereIn('permission', $levels)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }
}
