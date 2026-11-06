<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GrowthSeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_try_on_your_site_reads_public_pages_only(): void
    {
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
        $this->postJson(route('demo.site'), ['url' => 'http://127.0.0.1/admin'])->assertStatus(422);
        $this->postJson(route('demo.site'), ['url' => 'http://169.254.169.254/latest'])->assertStatus(422);

        Http::fake(['93.184.216.34/*' => Http::response('<html><head><title>Bloom Florist</title></head><body><nav>menu</nav><p>We deliver fresh flowers across Leeds every day before 6pm.</p><p>Bouquets start at £25 and weddings are our speciality.</p></body></html>', 200, ['Content-Type' => 'text/html'])]);
        $this->postJson(route('demo.site'), ['url' => 'https://93.184.216.34/'])->assertOk()->assertJsonPath('title', 'Bloom Florist');
        $site = \App\Http\Controllers\LandingController::siteText('https://93.184.216.34/');
        $this->assertStringContainsString('Bouquets start at £25', $site['text']);
        $this->assertStringNotContainsString('menu', $site['text']);
    }

    public function test_sitemap_robots_and_structured_data(): void
    {
        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml')->assertSee('<loc>' . url('/changelog') . '</loc>', false);
        $this->assertStringContainsString('Disallow: /admin', file_get_contents(public_path('robots.txt')));
        $this->get('/')->assertOk()->assertSee('"@type":"FAQPage"', false)->assertSee('Try it on your own website');
    }
}
