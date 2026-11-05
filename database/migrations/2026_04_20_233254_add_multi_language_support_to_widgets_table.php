<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->json('welcome_messages')->nullable()->after('welcome_message'); // {'en': 'Hello', 'es': 'Hola'}
            $table->json('placeholder_texts')->nullable()->after('placeholder_text'); // {'en': 'Type...', 'es': 'Escribe...'}
            $table->json('conversation_starters_multi')->nullable()->after('conversation_starters'); // {'en': [...], 'es': [...]}
            $table->json('offline_form_messages')->nullable()->after('offline_form_message'); // {'en': 'Message', 'es': 'Mensaje'}
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->dropColumn(['welcome_messages', 'placeholder_texts', 'conversation_starters_multi', 'offline_form_messages']);
        });
    }
};
