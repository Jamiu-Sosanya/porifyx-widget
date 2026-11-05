<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['key' => 'email_verification_required'],
            [
                'config_key'  => 'app.email_verification_required',
                'label'       => 'Require Email Verification',
                'description' => 'Force new users to verify their email address before accessing the dashboard. Disable for instant access.',
                'type'        => 'boolean',
                'group'       => 'security',
                'value'       => '1',
                'is_public'   => false,
                'is_sensitive'=> false,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'email_verification_required')->delete();
    }
};
