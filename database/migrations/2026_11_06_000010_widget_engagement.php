<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AI notice, hidden pages and action cards (see App\Support\Engagement).
        Schema::table('widgets', function (Blueprint $t) {
            if (! Schema::hasColumn('widgets', 'engagement')) $t->json('engagement')->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('widgets', 'engagement')) Schema::table('widgets', fn (Blueprint $t) => $t->dropColumn('engagement'));
    }
};
