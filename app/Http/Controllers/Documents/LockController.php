<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LockController extends Controller
{
    /**
     * Lock a document so it cannot be edited.
     */
    public function lock(Request $request, Document $document, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('lock', $document);

        $document->update([
            'is_locked' => true,
            'locked_by' => $request->user()->id,
        ]);

        $audit->log($document, 'lock', null, ['locked_by' => $request->user()->id], "Locked '{$document->title}'");

        return back()->with('status', 'Document locked.');
    }

    /**
     * Unlock a previously-locked document.
     */
    public function unlock(Document $document, AuditLogger $audit): RedirectResponse
    {
        $this->authorize('lock', $document);

        $previousLockedBy = $document->locked_by;

        $document->update([
            'is_locked' => false,
            'locked_by' => null,
        ]);

        $audit->log($document, 'unlock', ['locked_by' => $previousLockedBy], null, "Unlocked '{$document->title}'");

        return back()->with('status', 'Document unlocked.');
    }
}
