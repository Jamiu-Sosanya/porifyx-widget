<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['marketing_theme', 'Marketing site theme', 'aurora, midnight, daylight, sunset or prism.', 'aurora'],
            ['hero_title', 'Homepage headline', 'First part of the big headline, e.g. "Turn website visitors into".', ''],
            ['hero_highlight', 'Headline highlight', 'Coloured end of the headline, e.g. "customers, 24/7."', ''],
            ['hero_subtitle', 'Homepage subtitle', 'Sentence under the headline.', ''],
        ] as [$key, $label, $desc, $value]) {
            if (! DB::table('system_settings')->where('key', $key)->exists()) {
                DB::table('system_settings')->insert(['key' => $key, 'label' => $label, 'description' => $desc, 'type' => 'string', 'group' => 'branding', 'value' => $value, 'is_public' => true, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        DB::table('system_settings')->whereIn('key', ['marketing_theme', 'hero_title', 'hero_highlight', 'hero_subtitle'])->delete();
    }
};