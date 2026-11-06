<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Scheduled analytics emails: {enabled, days, every, recipients[], next_at}
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'report_settings')) $t->json('report_settings')->nullable();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'report_settings')) Schema::table('users', fn (Blueprint $t) => $t->dropColumn('report_settings'));
    }
};