<?php

namespace Tests\Feature;

use App\Support\CookieConsent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CookieConsentTest extends TestCase
{
    use RefreshDatabase;

    public function test_banner_follows_admin_settings(): void
    {
        $this->withoutVite()->get('/')->assertSee(CookieConsent::DEFAULTS['title']);

        CookieConsent::save(['layout' => 'modal', 'title' => 'Cookies, briefly', 'show_reject' => false]);
        $this->withoutVite()->get('/')->assertSee('Cookies, briefly')->assertSee('place-items-center', false)
            ->assertDontSee(CookieConsent::DEFAULTS['reject_label']);

        CookieConsent::save(['enabled' => false]);
        $this->withoutVite()->get('/')->assertDontSee('pfx-consent-title', false);
        $this->withoutVite()->get('/?cookie_preview=1')->assertSee('pfx-consent-title', false);
    }
}
