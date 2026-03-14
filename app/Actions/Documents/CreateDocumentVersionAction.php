<?php

namespace App\Actions\Documents;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\Storage\DocumentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateDocumentVersionAction
{
    public function __construct(
        private readonly DocumentStorageService $storage,
    ) {}

    public function execute(
        Document $document,
        UploadedFile $file,
        User $uploader,
        ?string $changeSummary = null,
    ): DocumentVersion {
        return DB::transaction(function () use ($document, $file, $uploader, $changeSummary) {
            // Determine next version number
            $nextVersion = ($document->versions()->max('version_number') ?? 0) + 1;

            // Compute checksum before moving the file
            $checksum = $this->storage->checksum($file);

            // Store on configured disk
            $storagePath = $this->storage->store($file, $document->ulid, $nextVersion);

            // Mark all previous versions as not current
            DocumentVersion::where('document_id', $document->id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            return DocumentVersion::create([
                'document_id'     => $document->id,
                'version_number'  => $nextVersion,
                'storage_path'    => $storagePath,
                'storage_disk'    => $this->storage->disk,
                'file_name'       => $file->getClientOriginalName(),
                'mime_type'       => $file->getMimeType() ?? $file->getClientMimeType(),
                'size_bytes'      => $file->getSize(),
                'checksum_sha256' => $checksum,
                'uploaded_by'     => $uploader->id,
                'change_summary'  => $changeSummary,
                'is_current'      => true,
                'created_at'      => now(),
            ]);
        });
    }
}
