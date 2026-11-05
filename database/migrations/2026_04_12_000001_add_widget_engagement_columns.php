<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->json('suggested_replies')->nullable()->after('welcome_message');
            $table->boolean('show_handoff_button')->default(true)->after('message_streaming');
            $table->string('handoff_button_label', 80)->default('Talk to a human')->after('show_handoff_button');
            $table->string('handoff_acknowledgment', 500)->default('Thanks! A team member will get back to you as soon as possible.')->after('handoff_button_label');
        });
    }

    public function down(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->dropColumn([
                'suggested_replies',
                'show_handoff_button',
                'handoff_button_label',
                'handoff_acknowledgment',
            ]);
        });
    }
};
