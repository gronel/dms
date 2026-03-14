<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }
    public function test_guest_is_redirected_from_search(): void
    {
        $this->get(route('search'))->assertRedirect(route('login'));
    }

    public function test_empty_query_returns_recent_documents(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->create(['owner_id' => $user->id, 'title' => 'Recent Doc']);
        DocumentVersion::factory()->create([
            'document_id' => $document->id,
            'uploaded_by' => $user->id,
            'is_current'  => true,
        ]);

        $this->actingAs($user)
            ->get(route('search'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Search/Results')
                ->has('documents')
            );
    }

    public function test_search_does_not_return_other_users_documents(): void
    {
        $owner  = User::factory()->create();
        $other  = User::factory()->create();

        $ownedDoc = Document::factory()->create(['owner_id' => $owner->id, 'title' => 'Owned Doc']);
        $otherDoc = Document::factory()->create(['owner_id' => $other->id, 'title' => 'Other Doc']);

        DocumentVersion::factory()->create([
            'document_id' => $ownedDoc->id,
            'uploaded_by' => $owner->id,
            'is_current'  => true,
        ]);
        DocumentVersion::factory()->create([
            'document_id' => $otherDoc->id,
            'uploaded_by' => $other->id,
            'is_current'  => true,
        ]);

        // Eloquent fallback (empty query) — must only return the caller's documents
        $this->actingAs($owner)
            ->get(route('search'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Search/Results')
                ->where('documents.total', 1)
                ->where('documents.data.0.title', 'Owned Doc')
            );
    }

    public function test_status_filter_is_applied(): void
    {
        $user = User::factory()->create();

        $draft     = Document::factory()->create(['owner_id' => $user->id, 'status' => 'draft',     'title' => 'Draft Doc']);
        $published = Document::factory()->create(['owner_id' => $user->id, 'status' => 'published', 'title' => 'Published Doc']);

        foreach ([$draft, $published] as $doc) {
            DocumentVersion::factory()->create([
                'document_id' => $doc->id,
                'uploaded_by' => $user->id,
                'is_current'  => true,
            ]);
        }

        $this->actingAs($user)
            ->get(route('search', ['status' => 'draft']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Search/Results')
                ->where('documents.total', 1)
                ->where('documents.data.0.title', 'Draft Doc')
            );
    }
}
