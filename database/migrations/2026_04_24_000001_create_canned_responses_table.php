<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('canned_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('shortcut')->nullable();   // e.g. /refund — typed in chat to trigger
            $table->string('category')->default('general');
            $table->text('content');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedBigInteger('use_count')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('canned_responses');
    }
};
