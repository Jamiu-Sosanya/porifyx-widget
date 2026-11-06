<?php

namespace Database\Seeders;

use App\Models\Automation;
use App\Models\Conversation;
use App\Models\Lead;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Visitor;
use App\Models\Widget;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin ──────────────────────────────────────────────
        $admin = User::updateOrCreate(
            ['email' => 'admin@demo.com'],
            [
                'name'               => 'Platform Admin',
                'password'           => Hash::make('demo1234'),
                'role'               => 'admin',
                'is_active'          => true,
                'email_verified_at'  => now(),
                'company_name'       => config('branding.app_name', 'PoriFyx Widget'),
                'company_website'    => config('app.url'),
            ]
        );

        // ── Regular user ───────────────────────────────────────
        $proPlan = Plan::where('slug', 'pro')->first() ?? Plan::first();
        $starterPlan = Plan::where('slug', 'starter')->first() ?? Plan::first();

        $user = User::updateOrCreate(
            ['email' => 'user@demo.com'],
            [
                'name'              => 'Demo User',
                'password'          => Hash::make('demo1234'),
                'role'              => 'user',
                'is_active'         => true,
                'email_verified_at' => now(),
                'company_name'      => 'Acme Corp',
                'company_website'   => 'https://acme.example.com',
                'phone'             => '+1 555 000 1234',
            ]
        );

        // Subscription for demo user
        if ($proPlan) {
            Subscription::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan_id'              => $proPlan->id,
                    'billing_cycle'        => 'monthly',
                    'status'               => 'active',
                    'current_period_start' => now()->subDays(5),
                    'current_period_end'   => now()->addDays(25),
                ]
            );
        }

        // ── Widget 1 ───────────────────────────────────────────
        $widget1 = Widget::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Acme Support Bot'],
            [
                'uuid'             => Str::uuid(),
                'ai_name'          => 'Aria',
                'tone'             => 'friendly',
                'primary_color'    => '#ff6a2b',
                'welcome_message'  => 'Hi there! How can I help you today?',
                'is_active'        => true,
                'lead_qualification_enabled' => true,
            ]
        );

        // ── Widget 2 ───────────────────────────────────────────
        $widget2 = Widget::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Sales Assistant'],
            [
                'uuid'            => Str::uuid(),
                'ai_name'         => 'Max',
                'tone'            => 'sales',
                'primary_color'   => '#10b981',
                'welcome_message' => 'Hello! Ready to find your perfect solution?',
                'is_active'       => true,
                'lead_qualification_enabled' => true,
            ]
        );

        // ── Sample conversations + leads ───────────────────────
        $visitors = [
            ['name' => 'Sarah Johnson',  'email' => 'sarah@example.com',  'phone' => '+1 555 100 2001'],
            ['name' => 'Mike Chen',      'email' => 'mike@techcorp.io',   'phone' => '+1 555 100 2002'],
            ['name' => 'Emma Williams',  'email' => 'emma@startup.co',    'phone' => null],
            ['name' => 'James Wilson',   'email' => 'james@biz.net',      'phone' => '+1 555 100 2004'],
            ['name' => 'Lisa Davis',     'email' => 'lisa@agency.com',    'phone' => '+1 555 100 2005'],
            ['name' => 'Robert Brown',   'email' => 'robert@corp.com',    'phone' => null],
            ['name' => 'Maria Garcia',   'email' => 'maria@design.io',    'phone' => '+1 555 100 2007'],
            ['name' => 'Tom Martinez',   'email' => null,                 'phone' => null],
        ];

        $statuses = ['resolved', 'resolved', 'ai_active', 'open', 'resolved', 'human_active', 'resolved', 'open'];
        $messages = [
            ["I need help integrating your API into my website.", "Sure, I can help with that! What framework are you using?", "We use React on the frontend."],
            ["What plans do you offer?", "We have Starter, Pro and Enterprise. Which best fits your needs?", "Tell me about the Pro plan."],
            ["Can I get a refund?", "I understand. Let me check your account details.", "I purchased 3 days ago."],
            ["How do I add the widget to my site?", "Just paste the embed snippet into your HTML. Want me to send you a quick guide?"],
            ["Does the widget support multiple languages?", "Yes! We support 40+ languages with automatic detection.", "Great, we need Spanish and French."],
            ["I'm having trouble logging in.", "Sorry to hear that! Let me help you reset your access."],
            ["What AI model powers the chatbot?", "Our widget supports GPT-4, Claude, and Gemini — you choose!", "Claude sounds great, how do I switch?"],
            ["Pricing page seems broken."],
        ];

        foreach ($visitors as $i => $vData) {
            $visitor = Visitor::firstOrCreate(
                ['email' => $vData['email'] ?? null, 'widget_id' => ($i % 2 === 0 ? $widget1->id : $widget2->id)],
                [
                    'fingerprint'  => Str::random(32),
                    'name'         => $vData['name'],
                    'phone'        => $vData['phone'],
                    'widget_id'    => ($i % 2 === 0 ? $widget1->id : $widget2->id),
                    'ip_address'   => '10.0.0.' . ($i + 1),
                    'browser'      => 'Chrome',
                    'device_type'  => 'desktop',
                    'country'      => 'US',
                    'first_seen_at'=> now()->subDays(rand(1, 30)),
                    'last_seen_at' => now()->subHours(rand(1, 48)),
                ]
            );

            $conv = Conversation::firstOrCreate(
                ['visitor_id' => $visitor->id],
                [
                    'uuid'            => Str::uuid(),
                    'widget_id'       => $visitor->widget_id,
                    'user_id'         => $user->id,
                    'status'          => $statuses[$i],
                    'is_read'         => $i % 3 !== 0,
                    'message_count'   => count($messages[$i]),
                    'last_message_at' => now()->subHours(rand(1, 120)),
                    'csat_score'      => $statuses[$i] === 'resolved' ? rand(3, 5) : null,
                ]
            );

            // Messages
            foreach ($messages[$i] as $j => $content) {
                \App\Models\Message::firstOrCreate(
                    ['conversation_id' => $conv->id, 'content' => $content],
                    [
                        'uuid'       => Str::uuid(),
                        'role'       => $j % 2 === 0 ? 'visitor' : 'ai',
                        'created_at' => now()->subHours(rand(1, 100)),
                    ]
                );
            }

            // Leads for some conversations
            if ($i < 5 && $vData['email']) {
                Lead::firstOrCreate(
                    ['conversation_id' => $conv->id],
                    [
                        'user_id'         => $user->id,
                        'widget_id'       => $visitor->widget_id,
                        'visitor_id'      => $visitor->id,
                        'name'            => $vData['name'],
                        'email'           => $vData['email'],
                        'phone'           => $vData['phone'],
                        'score'           => rand(30, 95),
                        'temperature'     => ['hot', 'warm', 'cold', 'hot', 'warm'][$i],
                        'status'          => ['new', 'contacted', 'qualified', 'won', 'new'][$i],
                        'company'         => ['Acme', 'TechCorp', 'Startup Co', null, 'Agency Inc'][$i],
                    ]
                );
            }
        }

        // ── Automation ─────────────────────────────────────────
        Automation::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Notify on Hot Lead'],
            [
                'trigger'       => 'hot_lead',
                'action'        => 'send_email',
                'action_config' => ['email' => 'user@demo.com', 'subject' => 'New hot lead from chat!'],
                'is_active'     => true,
            ]
        );

        Automation::firstOrCreate(
            ['user_id' => $user->id, 'name' => 'Webhook on Lead Captured'],
            [
                'trigger'       => 'new_lead',
                'action'        => 'webhook',
                'action_config' => ['url' => 'https://hooks.example.com/leads'],
                'is_active'     => false,
            ]
        );

        // ── Second demo user (free plan) ───────────────────────
        $user2 = User::updateOrCreate(
            ['email' => 'free@demo.com'],
            [
                'name'              => 'Free User Demo',
                'password'          => Hash::make('demo1234'),
                'role'              => 'user',
                'is_active'         => true,
                'email_verified_at' => now(),
                'company_name'      => 'Small Biz LLC',
            ]
        );

        if ($starterPlan) {
            Subscription::updateOrCreate(
                ['user_id' => $user2->id],
                [
                    'plan_id'              => $starterPlan->id,
                    'billing_cycle'        => 'monthly',
                    'status'               => 'trialing',
                    'trial_ends_at'        => now()->addDays(11),
                    'current_period_start' => now(),
                    'current_period_end'   => now()->addMonth(),
                ]
            );
        }

        // ── Bookings demo ────────────────────────────────────────────────────
        $weekday = [['09:00', '12:30'], ['13:30', '17:30']];
        $widget1->update(['booking' => [
            'enabled' => true, 'business_name' => 'Acme Wellness Clinic', 'button_label' => 'Book an appointment',
            'timezone' => 'Europe/London', 'slot_step' => 30, 'buffer' => 10, 'min_notice' => 2, 'max_days_ahead' => 30,
            'daily_limit' => 0, 'auto_confirm' => true, 'require_phone' => false, 'reminder_hours' => 24, 'blocked_dates' => [],
            'services' => [
                ['id' => 'consult', 'name' => 'First consultation', 'duration' => 30, 'price' => 'Free', 'description' => 'Talk through what you need'],
                ['id' => 'physio', 'name' => 'Physiotherapy session', 'duration' => 60, 'price' => '£55', 'description' => 'One-to-one treatment'],
                ['id' => 'followup', 'name' => 'Follow-up call', 'duration' => 15, 'price' => '£15', 'description' => 'Phone or video'],
            ],
            'hours' => ['mon' => $weekday, 'tue' => $weekday, 'wed' => $weekday, 'thu' => $weekday, 'fri' => [['09:00', '15:00']], 'sat' => [['10:00', '13:00']], 'sun' => []],
            'staff' => [
                ['id' => 'priya', 'name' => 'Dr Priya Shah', 'email' => null, 'services' => [], 'days' => []],
                ['id' => 'tom', 'name' => 'Tom Reed', 'email' => null, 'services' => ['physio', 'followup'], 'days' => ['mon', 'tue', 'wed', 'thu']],
            ],
            'questions' => [
                ['id' => 'first', 'label' => 'Is this your first visit?', 'type' => 'select', 'options' => 'Yes, No', 'required' => true],
            ],
            'allow_reschedule' => true, 'cancel_notice' => 12, 'ai_booking' => true,
        ]]);
        if (! \App\Models\Appointment::where('widget_id', $widget1->id)->exists()) {
            $tz = 'Europe/London';
            foreach ([
                [0, '15:00', 'physio', 'Sarah Johnson', 'sarah@example.com', 'confirmed', 'Lower back pain after running'],
                [1, '09:30', 'consult', 'Mike Chen', 'mike@techcorp.io', 'pending', null],
                [1, '14:00', 'followup', 'Emma Williams', 'emma@startup.co', 'confirmed', null],
                [3, '10:00', 'physio', 'James Wilson', 'james@biz.net', 'confirmed', 'Shoulder, second session'],
                [-2, '11:00', 'consult', 'Lisa Davis', 'lisa@agency.com', 'completed', null],
                [-3, '16:00', 'followup', 'Robert Brown', 'robert@corp.com', 'no_show', null],
            ] as [$day, $time, $sid, $name, $email, $status, $notes]) {
                $svc = collect($widget1->fresh()->booking['services'])->firstWhere('id', $sid);
                $start = \Carbon\Carbon::parse(now($tz)->addDays($day)->toDateString() . ' ' . $time, $tz)->utc();
                \App\Models\Appointment::create([
                    'widget_id' => $widget1->id, 'user_id' => $widget1->user_id, 'service_id' => $sid, 'service_name' => $svc['name'],
                    'duration' => $svc['duration'], 'name' => $name, 'email' => $email, 'notes' => $notes, 'status' => $status,
                    'lead_id' => \App\Models\Lead::where('email', $email)->value('id'),
                    'starts_at' => $start, 'ends_at' => $start->copy()->addMinutes($svc['duration']),
                ]);
            }
        }

        $this->command->info('Demo accounts created:');
        $this->command->info('  Admin  → admin@demo.com / demo1234');
        $this->command->info('  User   → user@demo.com  / demo1234  (Pro plan, 2 widgets, 8 convs, 5 leads)');
        $this->command->info('  Free   → free@demo.com  / demo1234  (Starter trial)');
    }
}
