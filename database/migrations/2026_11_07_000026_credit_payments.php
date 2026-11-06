<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('manual_payments', function (Blueprint $t) {
            $t->unsignedInteger('credits')->nullable();
            $t->unsignedBigInteger('plan_id')->nullable()->change();
            $t->string('billing_cycle')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('manual_payments', fn (Blueprint $t) => $t->dropColumn('credits'));
    }
};