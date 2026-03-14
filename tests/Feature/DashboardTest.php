<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_renders_with_stats(): void
    {
        $user = User::factory()->create();
        $doc  = Document::factory()->create(['owner_id' => $user->id, 'status' => 'published']);
        DocumentVersion::factory()->create(['document_id' => $doc->id, 'uploaded_by' => $user->id, 'is_current' => true, 'size_bytes' => 2048]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Dashboard')
                ->has('stats')
                ->has('recentActivity')
                ->has('recentDocuments')
                ->where('stats.total_documents', 1)
                ->where('stats.published', 1)
                ->where('stats.draft', 0)
                ->where('stats.storage_bytes', 2048)
            );
    }

    public function test_dashboard_only_shows_own_documents(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        Document::factory()->create(['owner_id' => $owner->id]);
        Document::factory()->create(['owner_id' => $other->id]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.total_documents', 1)
            );
    }
}
