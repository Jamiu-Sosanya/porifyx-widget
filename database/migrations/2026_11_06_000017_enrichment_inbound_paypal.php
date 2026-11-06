<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $t) {
            if (! Schema::hasColumn('leads', 'enrichment')) $t->json('enrichment')->nullable(); // company info found from the email domain
        });
        Schema::table('plans', function (Blueprint $t) {
            if (! Schema::hasColumn('plans', 'paypal_monthly_id')) $t->string('paypal_monthly_id')->nullable();
            if (! Schema::hasColumn('plans', 'paypal_yearly_id')) $t->string('paypal_yearly_id')->nullable();
        });
        Schema::table('subscriptions', function (Blueprint $t) {
            if (! Schema::hasColumn('subscriptions', 'paypal_subscription_id')) $t->string('paypal_subscription_id')->nullable()->index();
        });

        foreach ([
            ['paypal_client_id', 'services.paypal.client_id', 'PayPal client ID', 'From developer.paypal.com > Apps & Credentials.'],
            ['paypal_secret', 'services.paypal.secret', 'PayPal secret', 'Kept private; used to create subscriptions.'],
            ['paypal_mode', 'services.paypal.mode', 'PayPal mode', 'sandbox while testing, live for real payments.'],
            ['paypal_webhook_id', 'services.paypal.webhook_id', 'PayPal webhook ID', 'Webhook URL: /api/paypal/webhook. Used to verify PayPal events.'],
        ] as [$key, $cfg, $label, $desc]) {
            if (! DB::table('system_settings')->where('key', $key)->exists()) {
                DB::table('system_settings')->insert(['key' => $key, 'config_key' => $cfg, 'label' => $label, 'description' => $desc, 'type' => 'string', 'group' => 'payment',
                    'value' => $key === 'paypal_mode' ? 'sandbox' : '', 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('leads', 'enrichment')) Schema::table('leads', fn (Blueprint $t) => $t->dropColumn('enrichment'));
        Schema::table('plans', fn (Blueprint $t) => $t->dropColumn(['paypal_monthly_id', 'paypal_yearly_id']));
        Schema::table('subscriptions', fn (Blueprint $t) => $t->dropColumn('paypal_subscription_id'));
        DB::table('system_settings')->whereIn('key', ['paypal_client_id', 'paypal_secret', 'paypal_mode', 'paypal_webhook_id'])->delete();
    }
};
