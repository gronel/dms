<?php

namespace App\Services\Storage;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

class DocumentStorageService
{
    public string $disk = 'documents';

    /**
     * Store an uploaded file on the configured disk and return its storage path.
     * The file is stored under documents/{year}/{month}/{ulid}-v{version}.{ext}
     */
    public function store(UploadedFile $file, string $documentUlid, int $versionNumber): string
    {
        $extension = $file->getClientOriginalExtension();
        $year = now()->format('Y');
        $month = now()->format('m');
        $filename = "{$documentUlid}-v{$versionNumber}.{$extension}";
        $path = "documents/{$year}/{$month}/{$filename}";

        Storage::disk($this->disk)->putFileAs(
            "documents/{$year}/{$month}",
            $file,
            $filename,
            ['visibility' => 'private']
        );

        return $path;
    }

    /**
     * Generate a short-lived signed URL for a stored file (default 5 minutes).
     */
    public function temporaryUrl(string $storagePath, int $minutes = 5): string
    {
        return Storage::disk($this->disk)->temporaryUrl(
            $storagePath,
            now()->addMinutes($minutes)
        );
    }

    /**
     * Delete a file from storage.
     */
    public function delete(string $storagePath): bool
    {
        return Storage::disk($this->disk)->delete($storagePath);
    }

    /**
     * Compute a SHA-256 checksum from an uploaded file.
     */
    public function checksum(UploadedFile $file): string
    {
        return hash_file('sha256', $file->getRealPath());
    }

    /**
     * Generate a signed Laravel route URL for sharing a document version.
     * The recipient does not need to be authenticated — the signature proves validity.
     * Expires after $minutes (default 60 minutes).
     */
    public function signedShareUrl(string $documentUlid, int $versionId, int $minutes = 60): string
    {
        return URL::temporarySignedRoute(
            'document-versions.download',
            now()->addMinutes($minutes),
            ['document' => $documentUlid, 'version' => $versionId]
        );
    }
}
