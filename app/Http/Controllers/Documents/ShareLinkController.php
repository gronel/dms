<?php

namespace App\Http\Controllers\Documents;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentPermission;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class ShareLinkController extends Controller
{
    /**
     * Create (or refresh) a tokenised share link for a document version.
     * Returns { share_url, expires_at } as JSON.
     */
    public function store(Request $request, Document $document, AuditLogger $audit): JsonResponse
    {
        $this->authorize('share', $document);

        $validated = $request->validate([
            'expires_in_hours' => ['nullable', 'integer', 'min:1', 'max:720'], // max 30 days
        ]);

        $hours     = (int) ($validated['expires_in_hours'] ?? 24);
        $expiresAt = now()->addHours($hours);
        $token     = Str::random(48);

        // One share-link record per document (overwrite the previous one)
        $permission = DocumentPermission::updateOrCreate(
            ['document_id' => $document->id, 'user_id' => null],
            [
                'granted_by'  => $request->user()->id,
                'permission'  => 'download',
                'expires_at'  => $expiresAt,
                'share_token' => $token,
            ]
        );

        $shareUrl = route('share.show', ['token' => $token]);

        $audit->log(
            $document,
            'share_link_created',
            null,
            ['expires_at' => $expiresAt->toIso8601String()],
            "Created share link expiring in {$hours}h",
        );

        return response()->json([
            'share_url'  => $shareUrl,
            'expires_at' => $expiresAt->toIso8601String(),
            'token'      => $token,
        ], 201);
    }

    /**
     * Revoke the anonymous share link for a document.
     */
    public function destroy(Document $document, AuditLogger $audit): JsonResponse
    {
        $this->authorize('share', $document);

        DocumentPermission::where('document_id', $document->id)
            ->whereNull('user_id')
            ->delete();

        $audit->log($document, 'share_link_revoked', null, null, 'Revoked anonymous share link');

        return response()->json(null, 204);
    }

    /**
     * Public share-link landing page — validates token, redirects to signed download URL.
     * No auth required.
     */
    public function show(Request $request, string $token): SymfonyRedirect
    {
        $record = DocumentPermission::query()
            ->whereNull('user_id')
            ->where('share_token', $token)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->with('document.currentVersion')
            ->firstOrFail();

        $document = $record->document;
        $version  = $document->currentVersion;

        abort_unless($version !== null, 404, 'Document has no current version.');

        /** @var \App\Services\Storage\DocumentStorageService $storage */
        $storage    = app(\App\Services\Storage\DocumentStorageService::class);
        $signedUrl  = $storage->temporaryUrl($version->storage_path, minutes: 10);

        return redirect($signedUrl);
    }
}
