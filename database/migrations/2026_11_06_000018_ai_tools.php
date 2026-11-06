<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // WooCommerce + MCP tool settings per widget (App\Support\AiTools).
        Schema::table('widgets', function (Blueprint $t) {
            if (! Schema::hasColumn('widgets', 'tools')) $t->json('tools')->nullable();
        });
        // Every tool call the AI makes, for the owner's activity view.
        Schema::create('tool_calls', function (Blueprint $t) {
            $t->id();
            $t->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $t->unsignedBigInteger('conversation_id')->nullable();
            $t->string('tool', 120);
            $t->json('arguments')->nullable();
            $t->boolean('ok')->default(true);
            $t->unsignedInteger('ms')->default(0);
            $t->string('summary', 300)->nullable();
            $t->timestamp('created_at')->useCurrent();
            $t->index(['widget_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tool_calls');
        if (Schema::hasColumn('widgets', 'tools')) Schema::table('widgets', fn (Blueprint $t) => $t->dropColumn('tools'));
    }
};
