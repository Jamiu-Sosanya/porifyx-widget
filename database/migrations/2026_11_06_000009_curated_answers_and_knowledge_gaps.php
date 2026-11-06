<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hand-written replies used instead of the AI for sensitive topics.
        Schema::create('curated_answers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $t->string('question');
            $t->string('keywords')->nullable(); // comma-separated phrases; any match triggers
            $t->text('answer');
            $t->boolean('is_active')->default(true);
            $t->unsignedInteger('hits')->default(0);
            $t->timestamps();
        });

        // Questions the AI could not answer from the knowledge base.
        Schema::create('knowledge_gaps', function (Blueprint $t) {
            $t->id();
            $t->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $t->string('normalized', 191);
            $t->string('question', 500);
            $t->unsignedInteger('times_asked')->default(1);
            $t->string('status', 20)->default('open'); // open | answered | dismissed
            $t->timestamp('last_asked_at')->nullable();
            $t->timestamps();
            $t->unique(['widget_id', 'normalized']);
        });

        Schema::table('widgets', function (Blueprint $t) {
            if (! Schema::hasColumn('widgets', 'show_sources')) $t->boolean('show_sources')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_gaps');
        Schema::dropIfExists('curated_answers');
        if (Schema::hasColumn('widgets', 'show_sources')) Schema::table('widgets', fn (Blueprint $t) => $t->dropColumn('show_sources'));
    }
};
