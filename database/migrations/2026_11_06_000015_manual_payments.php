<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bank transfer payments: the customer submits a reference, the platform admin approves it.
        Schema::create('manual_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $t->string('billing_cycle', 10);
            $t->decimal('amount', 10, 2);
            $t->string('reference', 120);
            $t->string('status', 20)->default('pending'); // pending | approved | rejected
            $t->string('note', 500)->nullable();
            $t->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamp('reviewed_at')->nullable();
            $t->timestamps();
        });

        if (Schema::hasTable('system_settings')) {
            foreach ([
                ['bank_transfer_enabled', 'Accept bank transfers', 'Show "Pay by bank transfer" for paid plans. You approve each payment under Admin > Payments.', 'boolean', '0'],
                ['bank_transfer_details', 'Bank details', 'Shown to customers paying by transfer (account name, number, sort code/IBAN, bank).', 'string', ''],
                ['money_back_text', 'Guarantee text', 'Shown under the pricing plans, e.g. "30-day money-back guarantee". Leave empty to hide.', 'string', ''],
            ] as [$key, $label, $desc, $type, $value]) {
                if (! DB::table('system_settings')->where('key', $key)->exists()) {
                    DB::table('system_settings')->insert(['key' => $key, 'label' => $label, 'description' => $desc, 'type' => $type, 'group' => 'payment', 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_payments');
        DB::table('system_settings')->whereIn('key', ['bank_transfer_enabled', 'bank_transfer_details', 'money_back_text'])->delete();
    }
};
