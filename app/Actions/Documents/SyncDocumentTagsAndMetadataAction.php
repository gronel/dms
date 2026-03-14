<?php

namespace App\Actions\Documents;

use App\Models\Document;
use Illuminate\Support\Facades\DB;

class SyncDocumentTagsAndMetadataAction
{
    /**
     * Sync the document's tags (replace all) and upsert its metadata rows.
     *
     * @param  array<int>  $tagIds     IDs from the tags table
     * @param  array<array{key: string, value: string, value_type: string}>  $metadata
     */
    public function execute(Document $document, array $tagIds, array $metadata): void
    {
        DB::transaction(function () use ($document, $tagIds, $metadata) {
            // Sync tags (detach removed, attach new)
            $document->tags()->sync($tagIds);

            // Delete old metadata rows and re-insert — cleanest for an EAV
            $document->metadata()->delete();

            if (! empty($metadata)) {
                $rows = array_map(fn ($m) => [
                    'document_id' => $document->id,
                    'key'         => $m['key'],
                    'value'       => $m['value'],
                    'value_type'  => $m['value_type'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ], $metadata);

                $document->metadata()->insert($rows);
            }
        });
    }
}
