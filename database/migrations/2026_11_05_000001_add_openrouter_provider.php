<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ai_provider was an enum; a plain string lets new providers (OpenRouter, ...)
 * ship without a schema change. Validation lives in WidgetController.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->string('ai_provider', 30)->default('openai')->change();
        });
    }

    public function down(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->enum('ai_provider', ['openai', 'anthropic', 'gemini', 'groq'])->default('openai')->change();
        });
    }
};
