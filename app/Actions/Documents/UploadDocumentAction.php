<?php

namespace App\Actions\Documents;

use App\Models\Document;
use App\Models\User;
use App\Services\Storage\DocumentStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UploadDocumentAction
{
    public function __construct(
        private readonly CreateDocumentVersionAction $versionAction,
    ) {}

    /**
     * Create a new Document record and upload its first version.
     */
    public function execute(
        User $owner,
        string $title,
        UploadedFile $file,
        array $options = [],
    ): Document {
        return DB::transaction(function () use ($owner, $title, $file, $options) {
            $document = Document::create([
                'ulid'        => (string) Str::ulid(),
                'title'       => $title,
                'description' => $options['description'] ?? null,
                'owner_id'    => $owner->id,
                'folder_id'   => $options['folder_id'] ?? null,
                'status'      => $options['status'] ?? 'draft',
            ]);

            $this->versionAction->execute(
                document: $document,
                file: $file,
                uploader: $owner,
                changeSummary: $options['change_summary'] ?? 'Initial upload',
            );

            return $document->fresh(['currentVersion', 'owner']);
        });
    }
}
