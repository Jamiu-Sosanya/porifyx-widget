<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Team roles a workspace owner defines from the permission list in App\Support\Workspace.
        Schema::create('custom_roles', function (Blueprint $t) {
            $t->id();
            $t->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $t->string('name', 60);
            $t->json('permissions');
            $t->timestamps();
        });
        // Workspace accounts created by a person (they log in as themselves and switch in).
        Schema::table('users', function (Blueprint $t) {
            if (! Schema::hasColumn('users', 'is_workspace')) $t->boolean('is_workspace')->default(false);
        });
        // Team roles can now be custom ("custom:12"); widen any fixed column.
        Schema::table('team_members', fn (Blueprint $t) => $t->string('role', 40)->default('agent')->change());
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_roles');
        if (Schema::hasColumn('users', 'is_workspace')) Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_workspace'));
    }
};
