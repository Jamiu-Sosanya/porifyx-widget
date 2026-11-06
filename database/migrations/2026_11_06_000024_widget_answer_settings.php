<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('widgets', function (Blueprint $t) {
            if (! Schema::hasColumn('widgets', 'min_confidence')) $t->decimal('min_confidence', 3, 2)->nullable();   // per-widget answer confidence
            if (! Schema::hasColumn('widgets', 'fallback_message')) $t->string('fallback_message', 500)->nullable(); // reply when unsure (strict mode)
        });
    }

    public function down(): void
    {
        Schema::table('widgets', fn (Blueprint $t) => $t->dropColumn(['min_confidence', 'fallback_message']));
    }
};