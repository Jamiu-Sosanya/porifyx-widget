<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('version')->default(1);
            $table->enum('status', ['pending', 'processing', 'ready', 'failed'])->default('pending');
            $table->integer('total_chunks')->default(0);
            $table->integer('total_tokens')->default(0);
            $table->timestamp('last_trained_at')->nullable();
            $table->json('auto_detect_topics')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('training_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_base_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['url', 'pdf', 'text', 'faq', 'sitemap']);
            $table->string('name');
            $table->text('content')->nullable();         // raw text content
            $table->string('source_url')->nullable();    // for URL type
            $table->string('file_path')->nullable();     // for PDF/file type
            $table->integer('pages_crawled')->default(0);
            $table->integer('chunks_generated')->default(0);
            $table->integer('tokens_used')->default(0);
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('version')->default(1);
            $table->timestamp('last_processed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('knowledge_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_base_id')->constrained()->cascadeOnDelete();
            $table->foreignId('training_source_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->string('source_url')->nullable();
            $table->integer('chunk_index')->default(0);
            $table->integer('token_count')->default(0);
            $table->json('embedding')->nullable(); // stored as JSON array
            $table->string('embedding_model')->nullable();
            $table->json('metadata')->nullable(); // page title, section, etc.
            $table->timestamps();

            $table->index(['knowledge_base_id', 'chunk_index']);
        });

        Schema::create('knowledge_base_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_base_id')->constrained()->cascadeOnDelete();
            $table->integer('version');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->integer('total_chunks')->default(0);
            $table->boolean('is_active')->default(false);
            $table->json('snapshot_metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_versions');
        Schema::dropIfExists('knowledge_chunks');
        Schema::dropIfExists('training_sources');
        Schema::dropIfExists('knowledge_bases');
    }
};
