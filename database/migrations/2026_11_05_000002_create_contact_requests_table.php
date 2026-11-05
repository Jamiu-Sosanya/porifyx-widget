<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_requests', function (Blueprint $table) {
            $table->id();
            $table->string('topic', 20)->index();
            $table->string('name');
            $table->string('email');
            $table->string('company')->nullable();
            $table->string('website')->nullable();
            $table->string('team_size', 20)->nullable();
            $table->string('volume', 20)->nullable();
            $table->text('message');
            $table->string('ip', 45)->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_requests');
    }
};
