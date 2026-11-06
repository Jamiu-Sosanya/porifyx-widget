<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('translations', function (Blueprint $t) {
            $t->id();
            $t->string('locale', 10);
            $t->string('hash', 32);       // md5 of the English source
            $t->text('source');
            $t->text('value');
            $t->boolean('is_override')->default(false); // edited by an admin: never overwritten by AI
            $t->timestamps();
            $t->unique(['locale', 'hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('translations');
    }
};
