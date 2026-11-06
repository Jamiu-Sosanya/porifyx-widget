<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('widget_events', function (Blueprint $t) {
            if (! Schema::hasColumn('widget_events', 'severity')) $t->string('severity', 10)->default('error');
            if (! Schema::hasColumn('widget_events', 'resolved_at')) $t->timestamp('resolved_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('widget_events', fn (Blueprint $t) => $t->dropColumn(['severity', 'resolved_at']));
    }
};
