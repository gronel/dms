<?php

namespace App\Http\Controllers\Documents;

use App\Actions\Documents\CreateDocumentVersionAction;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\Audit\AuditLogger;
use App\Services\Storage\DocumentStorageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirect;

class DocumentVersionController extends Controller
{
    /**
     * Restore a specific version (creates a new current version pointing to the same file).
     */
    public function restore(
        Request $request,
        Document $document,
        DocumentVersion $version,
        CreateDocumentVersionAction $versionAction,
        AuditLogger $audit
    ): RedirectResponse {
        $this->authorize('update', $document);

        abort_if($version->document_id !== $document->id, 404);

        // Re-use the existing storage path — no re-upload needed
        \Illuminate\Support\Facades\DB::transaction(function () use ($document, $version, $request) {
            $nextVersion = ($document->versions()->max('version_number') ?? 0) + 1;

            DocumentVersion::where('document_id', $document->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            DocumentVersion::create([
                'document_id'     => $document->id,
                'version_number'  => $nextVersion,
                'storage_path'    => $version->storage_path,
                'storage_disk'    => $version->storage_disk,
                'file_name'       => $version->file_name,
                'mime_type'       => $version->mime_type,
                'size_bytes'      => $version->size_bytes,
                'checksum_sha256' => $version->checksum_sha256,
                'uploaded_by'     => $request->user()->id,
                'change_summary'  => "Restored from version {$version->version_number}",
                'is_current'      => true,
                'created_at'      => now(),
            ]);
        });

        $audit->log($document, 'restore', ['version' => $version->version_number], null, "Restored version {$version->version_number} of '{$document->title}'");

        return to_route('documents.show', $document->ulid)->with('status', "Restored to version {$version->version_number}.");
    }

    /**
     * Generate and redirect to a signed temporary download URL.
     */
    public function download(
        Document $document,
        DocumentVersion $version,
        DocumentStorageService $storage,
        AuditLogger $audit
    ): SymfonyRedirect {
        $this->authorize('download', $document);

        abort_if($version->document_id !== $document->id, 404);

        $audit->log($document, 'download', null, ['version' => $version->version_number], "Downloaded version {$version->version_number} of '{$document->title}'");

        $url = $storage->temporaryUrl($version->storage_path);

        return redirect($url);
    }
}
