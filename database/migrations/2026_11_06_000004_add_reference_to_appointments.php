<?php

use App\Models\Appointment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Short booking number visitors quote in the chat, e.g. BK-7F3K9Q.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', fn (Blueprint $table) => $table->string('reference', 12)->nullable()->unique()->after('id'));
        Appointment::whereNull('reference')->each(fn ($a) => $a->forceFill(['reference' => Appointment::newReference()])->saveQuietly());
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->dropColumn('reference');
        });
    }
};
