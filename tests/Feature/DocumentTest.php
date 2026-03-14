<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentPermission;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }
    // ────────────────────────────────────────────────────────────────────────
    // Access control
    // ────────────────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_documents_index(): void
    {
        $this->get(route('documents.index'))->assertRedirect(route('login'));
    }

    public function test_owner_can_view_document_show_page(): void
    {
        $owner = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->get(route('documents.show', $document->ulid))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Documents/Show'));
    }

    public function test_other_user_cannot_view_document(): void
    {
        $document = Document::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($other)
            ->get(route('documents.show', $document->ulid))
            ->assertForbidden();
    }

    public function test_user_with_view_permission_can_view_document(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);

        DocumentPermission::create([
            'document_id' => $document->id,
            'user_id'     => $viewer->id,
            'granted_by'  => $owner->id,
            'permission'  => 'view',
            'expires_at'  => null,
        ]);

        $this->actingAs($viewer)
            ->get(route('documents.show', $document->ulid))
            ->assertOk();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Store (upload)
    // ────────────────────────────────────────────────────────────────────────

    public function test_owner_can_upload_document(): void
    {
        Storage::fake('documents');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('report.pdf', 100, 'application/pdf');

        $response = $this->actingAs($user)->post(route('documents.store'), [
            'title'          => 'My Report',
            'description'    => 'Test description',
            'file'           => $file,
            'status'         => 'draft',
            'change_summary' => 'Initial upload',
            'tags'           => [],
            'metadata'       => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', ['title' => 'My Report', 'owner_id' => $user->id]);
        $this->assertDatabaseHas('document_versions', ['file_name' => 'report.pdf']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Update
    // ────────────────────────────────────────────────────────────────────────

    public function test_owner_can_update_document(): void
    {
        Storage::fake('documents');

        $owner = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);
        DocumentVersion::factory()->create([
            'document_id' => $document->id,
            'uploaded_by' => $owner->id,
            'is_current'  => true,
        ]);

        $response = $this->actingAs($owner)->patch(route('documents.update', $document->ulid), [
            'title'       => 'Updated Title',
            'description' => null,
            'status'      => 'published',
            'folder_id'   => null,
            'tags'        => [],
            'metadata'    => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', ['title' => 'Updated Title', 'status' => 'published']);
    }

    public function test_non_owner_cannot_update_document(): void
    {
        Storage::fake('documents');

        $document = Document::factory()->create();
        $other = User::factory()->create();
        DocumentVersion::factory()->create([
            'document_id' => $document->id,
            'uploaded_by' => $document->owner_id,
            'is_current'  => true,
        ]);

        $this->actingAs($other)
            ->patch(route('documents.update', $document->ulid), [
                'title'       => 'Hacked',
                'description' => null,
                'status'      => 'published',
                'folder_id'   => null,
                'tags'        => [],
                'metadata'    => [],
            ])
            ->assertForbidden();
    }

    public function test_locked_document_cannot_be_updated(): void
    {
        Storage::fake('documents');

        $owner = User::factory()->create();
        $document = Document::factory()->locked()->create(['owner_id' => $owner->id]);
        DocumentVersion::factory()->create([
            'document_id' => $document->id,
            'uploaded_by' => $owner->id,
            'is_current'  => true,
        ]);

        $this->actingAs($owner)
            ->patch(route('documents.update', $document->ulid), [
                'title'       => 'Should Fail',
                'description' => null,
                'status'      => 'draft',
                'folder_id'   => null,
                'tags'        => [],
                'metadata'    => [],
            ])
            ->assertForbidden();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Delete (soft)
    // ────────────────────────────────────────────────────────────────────────

    public function test_owner_can_soft_delete_document(): void
    {
        $owner = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->delete(route('documents.destroy', $document->ulid))
            ->assertRedirect();

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }

    public function test_non_owner_cannot_delete_document(): void
    {
        $document = Document::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($other)
            ->delete(route('documents.destroy', $document->ulid))
            ->assertForbidden();
    }
}
