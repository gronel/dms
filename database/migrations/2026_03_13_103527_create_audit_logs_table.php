<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Audit logs are intended to be append-only (immutable).
     * No updated_at is needed — rows are never modified after insert.
     */
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Who performed the action (null = system / unauthenticated)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // What type of thing was acted on, and which one
            $table->string('auditable_type');   // e.g. App\Models\Document
            $table->unsignedBigInteger('auditable_id');

            // The action name (upload, update, delete, download, restore, tag_sync, ...)
            $table->string('action', 64);

            // Optional human-readable description
            $table->string('description')->nullable();

            // Changed attributes: before/after snapshots (nullable for create/delete events)
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Request metadata for forensics
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();

            // Immutable timestamp — no updated_at
            $table->timestamp('created_at')->useCurrent();

            $table->index(['auditable_type', 'auditable_id'], 'auditable_index');
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
