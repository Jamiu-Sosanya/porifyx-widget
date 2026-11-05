<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Seed plans and system settings first
        $this->call([
            PlanSeeder::class,
            SystemSettingsSeeder::class,
        ]);

        // Create admin user
        $admin = User::updateOrCreate(
            ['email' => 'admin@porifyxwidget.com'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'is_active'=> true,
                'email_verified_at' => now(),
            ]
        );

        // Give admin enterprise plan
        $enterprisePlan = Plan::where('slug', 'enterprise')->first();
        if ($enterprisePlan) {
            Subscription::updateOrCreate(
                ['user_id' => $admin->id],
                [
                    'plan_id'              => $enterprisePlan->id,
                    'status'               => 'active',
                    'billing_cycle'        => 'yearly',
                    'current_period_start' => now(),
                    'current_period_end'   => now()->addYear(),
                ]
            );
        }

        // Create demo user
        $demo = User::updateOrCreate(
            ['email' => 'demo@porifyxwidget.com'],
            [
                'name'         => 'Demo User',
                'password'     => Hash::make('password'),
                'role'         => 'user',
                'is_active'    => true,
                'company_name' => 'Acme Corp',
                'email_verified_at' => now(),
            ]
        );

        // Give demo user Pro plan trial
        $proPlan = Plan::where('slug', 'pro')->first();
        if ($proPlan) {
            Subscription::updateOrCreate(
                ['user_id' => $demo->id],
                [
                    'plan_id'              => $proPlan->id,
                    'status'               => 'trialing',
                    'billing_cycle'        => 'monthly',
                    'trial_ends_at'        => now()->addDays(14),
                    'current_period_start' => now(),
                    'current_period_end'   => now()->addDays(14),
                ]
            );
        }
    }
}
