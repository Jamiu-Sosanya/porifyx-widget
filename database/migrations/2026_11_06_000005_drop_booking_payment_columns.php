<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Booking payments were removed: the business takes payment its own way.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', fn (Blueprint $table) => $table->dropColumn(['payment_status', 'amount', 'currency', 'payment_ref']));
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('payment_status', 20)->default('none');
            $table->unsignedInteger('amount')->default(0);
            $table->string('currency', 3)->nullable();
            $table->string('payment_ref')->nullable();
        });
    }
};
