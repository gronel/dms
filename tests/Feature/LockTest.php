<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LockTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_lock_document(): void
    {
        $owner    = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id, 'is_locked' => false]);

        $this->actingAs($owner)
            ->post(route('documents.lock', $document->ulid))
            ->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'id'        => $document->id,
            'is_locked' => true,
            'locked_by' => $owner->id,
        ]);
    }

    public function test_owner_can_unlock_document(): void
    {
        $owner    = User::factory()->create();
        $document = Document::factory()->locked()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)
            ->delete(route('documents.unlock', $document->ulid))
            ->assertRedirect();

        $this->assertDatabaseHas('documents', [
            'id'        => $document->id,
            'is_locked' => false,
            'locked_by' => null,
        ]);
    }

    public function test_non_owner_cannot_lock_document(): void
    {
        $document = Document::factory()->create(['is_locked' => false]);
        $other    = User::factory()->create();

        $this->actingAs($other)
            ->post(route('documents.lock', $document->ulid))
            ->assertForbidden();
    }

    public function test_non_owner_cannot_unlock_document(): void
    {
        $document = Document::factory()->locked()->create();
        $other    = User::factory()->create();

        $this->actingAs($other)
            ->delete(route('documents.unlock', $document->ulid))
            ->assertForbidden();
    }

    public function test_lock_creates_audit_log(): void
    {
        $owner    = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)->post(route('documents.lock', $document->ulid));

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Document::class,
            'auditable_id'   => $document->id,
            'action'         => 'lock',
            'user_id'        => $owner->id,
        ]);
    }

    public function test_unlock_creates_audit_log(): void
    {
        $owner    = User::factory()->create();
        $document = Document::factory()->locked()->create(['owner_id' => $owner->id]);

        $this->actingAs($owner)->delete(route('documents.unlock', $document->ulid));

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => Document::class,
            'auditable_id'   => $document->id,
            'action'         => 'unlock',
            'user_id'        => $owner->id,
        ]);
    }
}
