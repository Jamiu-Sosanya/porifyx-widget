<?php

use Database\Seeders\PlanSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->unsignedInteger('ai_credits')->default(0);
        });
        // New prices and limits; keeps any Stripe/PayPal IDs already set by the admin.
        if (Schema::hasTable('plans') && \App\Models\Plan::exists()) { // fresh installs use PlanSeeder
            foreach (PlanSeeder::PLANS as $p) {
                $row = \App\Models\Plan::firstOrNew(['slug' => $p['slug']]);
                $row->fill(collect($p)->except(['stripe_monthly_id', 'stripe_yearly_id'])->all())->save();
            }
        }
    }

    public function down(): void
    {
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('ai_credits'));
    }
};
