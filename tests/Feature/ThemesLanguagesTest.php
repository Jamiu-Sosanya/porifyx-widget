<?php

namespace Tests\Feature;

use App\Jobs\TranslateLocale;
use App\Models\Lead;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Widget;
use App\Services\MultiProviderAiService;
use App\Support\I18n;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class ThemesLanguagesTest extends TestCase
{
    use RefreshDatabase;

    private function tr(string $locale, array $map): void
    {
        foreach ($map as $src => $val) DB::table('translations')->insert(['locale' => $locale, 'hash' => md5($src), 'source' => $src, 'value' => $val, 'created_at' => now(), 'updated_at' => now()]);
        I18n::forget($locale);
    }

    public function test_marketing_theme_and_hero_text(): void
    {
        SystemSetting::updateOrCreate(['key' => 'marketing_theme'], ['label' => 't', 'type' => 'string', 'group' => 'branding', 'value' => 'sunset']);
        SystemSetting::updateOrCreate(['key' => 'hero_title'], ['label' => 't', 'type' => 'string', 'group' => 'branding', 'value' => 'Never miss a']);
        SystemSetting::updateOrCreate(['key' => 'hero_highlight'], ['label' => 't', 'type' => 'string', 'group' => 'branding', 'value' => 'customer again.']);
        Cache::flush();
        $this->get('/')->assertOk()->assertSee("var forced = 'dark'", false)->assertSee('linear-gradient(160deg,#ff6a2b', false)->assertSee('Never miss a')->assertSee('customer again.');
    }

    public function test_pages_show_in_the_chosen_language_without_touching_customer_data(): void
    {
        Queue::fake();
        $u = $this->subscribed(User::factory()->create());
        $w = Widget::create(['user_id' => $u->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true]);
        Lead::create(['user_id' => $u->id, 'widget_id' => $w->id, 'name' => 'Search leads...', 'email' => 'a@b.test', 'status' => 'new']);
        $this->tr('fr', ['Search leads…' => 'Rechercher des prospects…', 'Export' => 'Exporter']);

        $this->actingAs($u)->get('/lang/fr')->assertRedirect()->assertCookie('pfx_lang', 'fr', false);
        $this->assertSame('fr', $u->fresh()->locale);
        $page = $this->actingAs($u)->get(route('leads.index'))->assertOk()->getContent();
        $this->assertStringContainsString('<html lang="fr"', $page);
        $this->assertStringContainsString('Exporter', $page);
        $this->assertStringContainsString('placeholder="Rechercher des prospects…"', $page);
        // Inside a script the same text stays untouched.
        $this->assertSame('<script>var a = ">Export<";</script>', \App\Http\Middleware\TranslatePage::translate('<script>var a = ">Export<";</script>', ['Export' => 'Exporter']));

        $this->actingAs($u)->get('/lang/ar');
        $this->assertStringContainsString('dir="rtl"', $this->actingAs($u)->get(route('leads.index'))->getContent());
        Queue::assertPushed(TranslateLocale::class);
    }

    public function test_widget_text_follows_the_visitor_language(): void
    {
        $u = $this->subscribed(User::factory()->create());
        $w = Widget::create(['user_id' => $u->id, 'name' => 'Shop', 'uuid' => (string) Str::uuid(), 'token' => Str::random(32), 'is_active' => true, 'auto_language_detection' => true]);
        $this->tr('de', ['Accept' => 'Akzeptieren', 'Leads' => 'Leads-DE']);
        $s = $this->getJson("/api/widget/{$w->uuid}/config", ['Accept-Language' => 'de-DE,de;q=0.9'])->json('widget.strings');
        $this->assertSame(['Accept' => 'Akzeptieren'], $s, 'Only widget strings are sent');
    }

    public function test_ai_translates_a_language_and_admin_overrides_are_kept(): void
    {
        $this->app->instance(MultiProviderAiService::class, new class extends MultiProviderAiService {
            public function __construct() {}
            public function chat(Widget $widget, array $messages, int $maxTokens = 512): array
            {
                $in = json_decode(end($messages)['content'], true);
                return ['content' => json_encode(array_combine($in, array_map(fn ($s) => 'ES:' . $s, $in)))];
            }
        });
        (new TranslateLocale('es'))->handle();
        $this->assertSame(count(I18n::sources()), DB::table('translations')->where('locale', 'es')->count());
        $this->assertSame('ES:Accept', I18n::dict('es')['Accept']);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.translations', ['locale' => 'es', 'q' => 'accept']))->assertOk()->assertSee('ES:Accept');
        $this->actingAs($admin)->put(route('admin.translations.save'), ['locale' => 'es', 'values' => [md5('Accept') => 'Aceptar'], 'sources' => [md5('Accept') => 'Accept']])->assertRedirect();
        $this->assertSame('Aceptar', I18n::dict('es')['Accept']);
        $this->assertTrue((bool) DB::table('translations')->where('locale', 'es')->where('hash', md5('Accept'))->value('is_override'));
        $this->assertGreaterThanOrEqual(133, count(I18n::LOCALES));
    }
}
