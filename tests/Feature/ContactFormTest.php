<?php

namespace Tests\Feature;

use App\Models\ContactRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_form_saves_the_request_and_rejects_bad_input(): void
    {
        $this->withoutVite()->get('/contact?topic=sales')->assertOk()->assertSee('Talk to sales');

        $this->post('/contact', ['topic' => 'sales', 'name' => 'Ada', 'email' => 'bad', 'message' => 'short'])
            ->assertSessionHasErrors(['email', 'message', 'consent']);

        $this->post('/contact', [
            'topic' => 'sales', 'name' => 'Ada Lovelace', 'email' => 'ada@example.com', 'company' => 'Analytical',
            'team_size' => '11-50', 'volume' => '1k-10k', 'message' => 'We want a demo for our shop.', 'consent' => '1',
        ])->assertRedirect('/contact')->assertSessionHas('contact_sent', 'Ada Lovelace');

        $this->assertDatabaseHas('contact_requests', ['email' => 'ada@example.com', 'topic' => 'sales', 'team_size' => '11-50']);

        // Honeypot submissions look successful but are not stored.
        $this->post('/contact', ['fax' => 'spam', 'name' => 'Bot'])->assertRedirect('/contact');
        $this->assertSame(1, ContactRequest::count());
    }
}
