<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Leads can now come from a booking made without chatting first.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->unsignedBigInteger('conversation_id')->nullable()->change();
            $table->unsignedBigInteger('visitor_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Left nullable: rows without a conversation may exist by now.
    }
};
