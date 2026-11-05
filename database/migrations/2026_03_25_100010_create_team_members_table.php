<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['admin', 'agent', 'viewer'])->default('agent');
            $table->json('widget_access')->nullable(); // null = all widgets
            $table->boolean('can_view_leads')->default(true);
            $table->boolean('can_manage_training')->default(false);
            $table->boolean('can_manage_settings')->default(false);
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->string('invite_token')->nullable()->unique();
            $table->timestamp('invite_expires_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['owner_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
