<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('staff_id', 40)->nullable()->after('service_id');
            $table->string('staff_name')->nullable()->after('staff_id');
            $table->json('answers')->nullable()->after('notes');
            $table->string('payment_status', 20)->default('none')->after('status'); // none, unpaid, paid, refunded
            $table->unsignedInteger('amount')->default(0)->after('payment_status'); // minor units
            $table->string('currency', 3)->nullable()->after('amount');
            $table->string('payment_ref')->nullable()->after('currency');
            $table->json('external_refs')->nullable(); // {"google:12": "eventId", "hubspot:3": {...}}
            $table->string('source', 20)->default('widget'); // widget, ai, dashboard, api
            $table->index(['widget_id', 'staff_id', 'starts_at']);
        });

        // Google / Microsoft calendars, per widget or per staff member.
        Schema::create('calendar_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->string('staff_id', 40)->nullable();
            $table->string('provider', 20); // google, microsoft
            $table->string('account_email')->nullable();
            $table->string('calendar_id')->default('primary');
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('check_busy')->default(true);
            $table->boolean('push_events')->default(true);
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['widget_id', 'staff_id', 'provider']);
        });

        // CRM connections (HubSpot, Pipedrive, GoHighLevel, Zoho, Salesforce, Zapier/Make).
        Schema::create('crm_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('widget_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);
            $table->text('credentials'); // encrypted JSON
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
            $table->unique(['widget_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_connections');
        Schema::dropIfExists('calendar_connections');
        Schema::table('appointments', function (Blueprint $table) {
            $table->dropIndex(['widget_id', 'staff_id', 'starts_at']);
            $table->dropColumn(['staff_id', 'staff_name', 'answers', 'payment_status', 'amount', 'currency', 'payment_ref', 'external_refs', 'source']);
        });
    }
};
