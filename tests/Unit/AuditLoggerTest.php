<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLoggerTest extends TestCase
{
    use RefreshDatabase;

    private AuditLogger $logger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = app(AuditLogger::class);
    }

    public function test_log_creates_record_with_user_id(): void
    {
        $user = User::factory()->create();
        $this->app['request']->setUserResolver(fn () => $user);

        $document = Document::factory()->create(['owner_id' => $user->id]);

        $log = $this->logger->log($document, 'test_action', null, ['title' => 'foo'], 'Test entry');

        $this->assertInstanceOf(AuditLog::class, $log);
        $this->assertDatabaseHas('audit_logs', [
            'action'         => 'test_action',
            'user_id'        => $user->id,
            'auditable_type' => Document::class,
            'auditable_id'   => $document->id,
        ]);
    }

    public function test_log_change_only_records_changed_keys(): void
    {
        $user = User::factory()->create();
        $this->app['request']->setUserResolver(fn () => $user);

        $document = Document::factory()->create(['owner_id' => $user->id]);

        $original = ['title' => 'Old Title', 'status' => 'draft', 'folder_id' => null];
        $updated  = ['title' => 'New Title', 'status' => 'draft', 'folder_id' => null];

        $log = $this->logger->logChange($document, 'update', $original, $updated);

        // Only 'title' changed — status and folder_id must not appear in the diff
        $this->assertArrayHasKey('title', $log->old_values);
        $this->assertArrayNotHasKey('status', $log->old_values ?? []);
        $this->assertArrayNotHasKey('folder_id', $log->old_values ?? []);

        $this->assertEquals('Old Title', $log->old_values['title']);
        $this->assertEquals('New Title', $log->new_values['title']);
    }

    public function test_log_change_with_no_changes_stores_null_values(): void
    {
        $user = User::factory()->create();
        $this->app['request']->setUserResolver(fn () => $user);

        $document = Document::factory()->create(['owner_id' => $user->id]);

        $attributes = ['title' => 'Same', 'status' => 'draft'];

        $log = $this->logger->logChange($document, 'noop', $attributes, $attributes);

        // When nothing changed, old_values and new_values should both be null (empty diff)
        $this->assertEmpty($log->old_values ?? []);
        $this->assertEmpty($log->new_values ?? []);
    }

    public function test_log_records_ip_address_from_request(): void
    {
        $user = User::factory()->create();
        $this->app['request']->setUserResolver(fn () => $user);

        $document = Document::factory()->create(['owner_id' => $user->id]);

        $log = $this->logger->log($document, 'ping');

        $this->assertNotNull($log->ip_address);
    }
}
