<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DocumentVersion>
 */
class DocumentVersionFactory extends Factory
{
    protected $model = DocumentVersion::class;

    public function definition(): array
    {
        return [
            'document_id'     => Document::factory(),
            'version_number'  => 1,
            'storage_path'    => 'documents/' . fake()->uuid() . '.pdf',
            'storage_disk'    => 'local',
            'file_name'       => fake()->word() . '.pdf',
            'mime_type'       => 'application/pdf',
            'size_bytes'      => fake()->numberBetween(1_024, 10_485_760),
            'checksum_sha256' => hash('sha256', fake()->uuid()),
            'uploaded_by'     => User::factory(),
            'change_summary'  => null,
            'is_current'      => true,
            'created_at'      => now(),
        ];
    }
}
