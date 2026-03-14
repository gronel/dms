<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentComment;
use App\Models\DocumentPermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Store
    // ────────────────────────────────────────────────────────────────────────

    public function test_guest_cannot_comment(): void
    {
        $document = Document::factory()->create();

        $this->post(route('document-comments.store', $document->ulid), ['content' => 'Hello'])
            ->assertRedirect(route('login'));
    }

    public function test_owner_can_add_comment(): void
    {
        $owner    = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->post(route('document-comments.store', $document->ulid), ['content' => 'Owner comment'])
            ->assertRedirect();

        $this->assertDatabaseHas('document_comments', [
            'document_id' => $document->id,
            'user_id'     => $owner->id,
            'content'     => 'Owner comment',
        ]);
    }

    public function test_viewer_can_add_comment(): void
    {
        $owner    = User::factory()->create();
        $viewer   = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);

        DocumentPermission::create([
            'document_id' => $document->id,
            'user_id'     => $viewer->id,
            'granted_by'  => $owner->id,
            'permission'  => 'view',
            'expires_at'  => null,
        ]);

        $this->actingAs($viewer)
            ->post(route('document-comments.store', $document->ulid), ['content' => 'Viewer comment'])
            ->assertRedirect();

        $this->assertDatabaseHas('document_comments', [
            'document_id' => $document->id,
            'user_id'     => $viewer->id,
        ]);
    }

    public function test_non_viewer_cannot_comment(): void
    {
        $document = Document::factory()->create();
        $other    = User::factory()->create();

        $this->actingAs($other)
            ->post(route('document-comments.store', $document->ulid), ['content' => 'Sneaky comment'])
            ->assertForbidden();
    }

    public function test_comment_content_is_required(): void
    {
        $owner    = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->post(route('document-comments.store', $document->ulid), ['content' => ''])
            ->assertSessionHasErrors('content');
    }

    public function test_comment_content_max_2000_chars(): void
    {
        $owner    = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->post(route('document-comments.store', $document->ulid), ['content' => str_repeat('a', 2001)])
            ->assertSessionHasErrors('content');
    }

    public function test_adding_comment_creates_audit_log(): void
    {
        $owner    = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->post(route('document-comments.store', $document->ulid), ['content' => 'Audit test']);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Document::class,
            'auditable_id'   => $document->id,
            'action'         => 'comment_add',
            'user_id'        => $owner->id,
        ]);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Destroy
    // ────────────────────────────────────────────────────────────────────────

    public function test_author_can_delete_own_comment(): void
    {
        $owner    = User::factory()->create();
        $viewer   = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);

        DocumentPermission::create([
            'document_id' => $document->id,
            'user_id'     => $viewer->id,
            'granted_by'  => $owner->id,
            'permission'  => 'view',
            'expires_at'  => null,
        ]);

        $comment = DocumentComment::factory()->create([
            'document_id' => $document->id,
            'user_id'     => $viewer->id,
        ]);

        $this->actingAs($viewer)
            ->delete(route('document-comments.destroy', [$document->ulid, $comment->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('document_comments', ['id' => $comment->id]);
    }

    public function test_document_owner_can_delete_any_comment(): void
    {
        $owner    = User::factory()->create();
        $viewer   = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);

        $comment = DocumentComment::factory()->create([
            'document_id' => $document->id,
            'user_id'     => $viewer->id,
        ]);

        $this->actingAs($owner)
            ->delete(route('document-comments.destroy', [$document->ulid, $comment->id]))
            ->assertRedirect();

        $this->assertDatabaseMissing('document_comments', ['id' => $comment->id]);
    }

    public function test_other_user_cannot_delete_comment(): void
    {
        $owner    = User::factory()->create();
        $author   = User::factory()->create();
        $other    = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);

        $comment = DocumentComment::factory()->create([
            'document_id' => $document->id,
            'user_id'     => $author->id,
        ]);

        $this->actingAs($other)
            ->delete(route('document-comments.destroy', [$document->ulid, $comment->id]))
            ->assertForbidden();
    }

    public function test_deleting_comment_creates_audit_log(): void
    {
        $owner    = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);
        $comment  = DocumentComment::factory()->create([
            'document_id' => $document->id,
            'user_id'     => $owner->id,
        ]);

        $this->actingAs($owner)
            ->delete(route('document-comments.destroy', [$document->ulid, $comment->id]));

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Document::class,
            'auditable_id'   => $document->id,
            'action'         => 'comment_delete',
            'user_id'        => $owner->id,
        ]);
    }
}
