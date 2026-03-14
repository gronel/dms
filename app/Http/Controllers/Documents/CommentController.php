<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentComment;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * Store a new comment on a document.
     * Requires view-level access (anyone who can see the document can comment).
     */
    public function store(Request $request, Document $document, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('view', $document);

        $validated = $request->validate([
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $document->comments()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        $audit->log($document, 'comment_add', null, null, "Commented on '{$document->title}'");

        return back()->with('status', 'Comment added.');
    }

    /**
     * Delete a comment (author or document owner only).
     */
    public function destroy(Document $document, DocumentComment $comment, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('delete', $comment);

        $comment->delete();

        $audit->log($document, 'comment_delete', null, null, "Deleted a comment on '{$document->title}'");

        return back()->with('status', 'Comment deleted.');
    }
}
