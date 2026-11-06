<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Scripted multi-step conversations (App\Services\FlowEngine).
        Schema::create('chat_flows', function (Blueprint $t) {
            $t->id();
            $t->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->boolean('is_active')->default(true);
            $t->string('trigger_keywords', 500)->nullable();
            $t->string('match_mode', 10)->default('any'); // any | all | exact
            $t->json('steps');
            $t->unsignedInteger('runs')->default(0);
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_flows');
    }
};
