<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }
    public function test_upload_creates_audit_log(): void
    {
        Storage::fake('documents');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');

        $this->actingAs($user)->post(route('documents.store'), [
            'title'          => 'Audited Upload',
            'file'           => $file,
            'status'         => 'draft',
            'change_summary' => 'Initial upload',
            'tags'           => [],
            'metadata'       => [],
        ]);

        $document = Document::where('title', 'Audited Upload')->firstOrFail();

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Document::class,
            'auditable_id'   => $document->id,
            'action'         => 'upload',
            'user_id'        => $user->id,
        ]);
    }

    public function test_update_creates_audit_log_with_diff(): void
    {
        Storage::fake('documents');

        $owner = User::factory()->create();
        $document = Document::factory()->create([
            'owner_id' => $owner->id,
            'title'    => 'Original Title',
            'status'   => 'draft',
        ]);
        DocumentVersion::factory()->create([
            'document_id' => $document->id,
            'uploaded_by' => $owner->id,
            'is_current'  => true,
        ]);

        $this->actingAs($owner)->patch(route('documents.update', $document->ulid), [
            'title'       => 'Updated Title',
            'description' => null,
            'status'      => 'published',
            'folder_id'   => null,
            'tags'        => [],
            'metadata'    => [],
        ]);

        $log = AuditLog::where('auditable_type', Document::class)
            ->where('auditable_id', $document->id)
            ->where('action', 'update')
            ->firstOrFail();

        $this->assertEquals('Original Title', $log->old_values['title'] ?? null);
        $this->assertEquals('Updated Title', $log->new_values['title'] ?? null);

        // Status change is also captured
        $this->assertEquals('draft', $log->old_values['status'] ?? null);
        $this->assertEquals('published', $log->new_values['status'] ?? null);
    }

    public function test_delete_creates_audit_log(): void
    {
        $owner = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)->delete(route('documents.destroy', $document->ulid));

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Document::class,
            'auditable_id'   => $document->id,
            'action'         => 'delete',
            'user_id'        => $owner->id,
        ]);
    }

    public function test_guest_cannot_access_audit_log_index(): void
    {
        $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
    }

    public function test_owner_can_view_document_audit_trail(): void
    {
        $owner = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);

        AuditLog::create([
            'user_id'        => $owner->id,
            'auditable_type' => Document::class,
            'auditable_id'   => $document->id,
            'action'         => 'upload',
            'description'    => "Uploaded '{$document->title}'",
            'old_values'     => null,
            'new_values'     => null,
            'ip_address'     => '127.0.0.1',
            'user_agent'     => 'test',
        ]);

        $this->actingAs($owner)
            ->get(route('audit-logs.show', $document->ulid))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('AuditLogs/Show'));
    }
}
