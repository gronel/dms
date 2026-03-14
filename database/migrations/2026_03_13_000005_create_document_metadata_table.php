<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
            $table->string('key', 100);
            $table->text('value');
            $table->enum('value_type', ['string', 'date', 'number', 'boolean'])->default('string');
            $table->timestamps();

            $table->unique(['document_id', 'key']);
            $table->index(['document_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_metadata');
    }
};
