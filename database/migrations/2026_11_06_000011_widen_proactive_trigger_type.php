<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Allow new behaviour triggers (idle) without an enum change per trigger type.
    public function up(): void
    {
        Schema::table('proactive_messages', fn (Blueprint $t) => $t->string('trigger_type', 30)->default('time_on_page')->change());
    }

    public function down(): void
    {
        //
    }
};
