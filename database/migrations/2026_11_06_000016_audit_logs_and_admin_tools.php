<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Errors reported by widgets running on customer sites.
        Schema::create('widget_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $t->string('type', 40);
            $t->string('message', 500)->nullable();
            $t->string('page_url', 500)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['created_at']);
        });

        Schema::create('changelog_entries', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('tag', 20)->default('new'); // new | improved | fixed
            $t->text('body');
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'changelog_seen_at')) $t->timestamp('changelog_seen_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('changelog_entries');
        Schema::dropIfExists('widget_events');
        if (Schema::hasColumn('users', 'changelog_seen_at')) Schema::table('users', fn (Blueprint $t) => $t->dropColumn('changelog_seen_at'));
    }
};
