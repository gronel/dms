<?php

namespace App\Policies;

use App\Models\DocumentComment;
use App\Models\User;

class DocumentCommentPolicy
{
    /**
     * Any user who can view the parent document may leave a comment.
     * This is enforced at the controller level via `authorize('view', $document)`.
     * Here we only gate deletion.
     */
    public function delete(User $user, DocumentComment $comment): bool
    {
        // Comment author OR document owner may delete
        return $user->id === $comment->user_id
            || $user->id === $comment->document->owner_id;
    }
}
