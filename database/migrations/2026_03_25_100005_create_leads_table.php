<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visitor_id')->constrained()->cascadeOnDelete();

            // Contact Info
            $table->string('name')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('website')->nullable();

            // Qualification
            $table->integer('score')->default(0); // 0-100
            $table->enum('temperature', ['hot', 'warm', 'cold'])->default('cold');
            $table->string('service_interest')->nullable();
            $table->string('budget_range')->nullable();
            $table->string('timeline')->nullable();
            $table->text('pain_point')->nullable();
            $table->text('notes')->nullable();

            // Tags & Status
            $table->json('tags')->nullable();
            $table->enum('status', ['new', 'contacted', 'qualified', 'proposal', 'won', 'lost'])->default('new');
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');

            // Qualification answers from AI conversation
            $table->json('qualification_answers')->nullable();

            $table->timestamp('qualified_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'temperature']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('leads');
    }
};
