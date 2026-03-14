<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_permissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('document_id')->constrained()->cascadeOnDelete();

            // The user being granted access (null = anyone with the share token)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Who granted this permission
            $table->foreignId('granted_by')->constrained('users')->cascadeOnDelete();

            // Permission level: view | download | edit
            $table->string('permission', 16)->default('view');

            // Optional expiry — null means permanent
            $table->timestamp('expires_at')->nullable();

            // Signed share token for anonymous/link sharing (nullable — only set for
            // link-based shares, i.e. user_id IS NULL or as an extra layer)
            $table->string('share_token', 64)->nullable()->unique();

            $table->timestamps();

            // A user can only have one permission record per document
            $table->unique(['document_id', 'user_id']);
            $table->index(['document_id', 'permission']);
            $table->index('share_token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_permissions');
    }
};
