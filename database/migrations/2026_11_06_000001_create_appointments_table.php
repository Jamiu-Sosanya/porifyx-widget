<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('widgets', function (Blueprint $table) {
            $table->json('booking')->nullable();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conversation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('service_id', 40);
            $table->string('service_name');
            $table->unsignedSmallInteger('duration');
            $table->string('name');
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->text('notes')->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->string('visitor_timezone', 64)->nullable();
            $table->string('status', 20)->default('confirmed');
            $table->string('token', 64)->unique();
            $table->dateTime('reminder_sent_at')->nullable();
            $table->timestamps();
            $table->index(['widget_id', 'starts_at']);
            $table->index(['user_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
        Schema::table('widgets', fn (Blueprint $table) => $table->dropColumn('booking'));
    }
};
