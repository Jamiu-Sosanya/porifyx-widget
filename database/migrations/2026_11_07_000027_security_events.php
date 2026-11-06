<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_events', function (Blueprint $t) {
            $t->id();
            $t->string('type', 30)->index();
            $t->string('ip', 45)->nullable()->index();
            $t->string('email', 190)->nullable();
            $t->string('detail', 500)->nullable();
            $t->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_events');
    }
};
