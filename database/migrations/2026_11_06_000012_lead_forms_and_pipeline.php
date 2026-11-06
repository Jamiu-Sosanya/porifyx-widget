<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lead form builder (App\Support\LeadForm) and pipeline settings (App\Support\LeadPipeline).
        Schema::table('widgets', function (Blueprint $t) {
            if (! Schema::hasColumn('widgets', 'lead_form')) $t->json('lead_form')->nullable();
        });
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'lead_settings')) $t->json('lead_settings')->nullable();
        });
        Schema::table('leads', function (Blueprint $t) {
            if (! Schema::hasColumn('leads', 'form_data')) $t->json('form_data')->nullable();
            $t->string('status', 40)->default('new')->change(); // custom statuses
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('widgets', 'lead_form')) Schema::table('widgets', fn (Blueprint $t) => $t->dropColumn('lead_form'));
        if (Schema::hasColumn('users', 'lead_settings')) Schema::table('users', fn (Blueprint $t) => $t->dropColumn('lead_settings'));
        if (Schema::hasColumn('leads', 'form_data')) Schema::table('leads', fn (Blueprint $t) => $t->dropColumn('form_data'));
    }
};
