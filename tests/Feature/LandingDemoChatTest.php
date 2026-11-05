<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LandingDemoChatTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_chat_uses_ai_when_a_key_exists_and_faq_otherwise(): void
    {
        foreach (['openrouter', 'openai', 'anthropic', 'gemini', 'groq'] as $p) {
            config(["ai.{$p}.key" => null]);
        }

        // No AI key: falls back to the built-in FAQ.
        $this->postJson('/demo-chat', ['message' => 'What is the pricing?'])
            ->assertOk()->assertJson(['ai' => false])
            ->assertJsonPath('reply', fn ($r) => str_contains($r, 'free trial'));

        // With a key: real model reply.
        config(['ai.openrouter.key' => 'k']);
        Http::fake(['openrouter.ai/*' => Http::response(['choices' => [['message' => ['content' => 'From the model']]]])]);
        $this->postJson('/demo-chat', ['message' => 'hi'])->assertOk()->assertJson(['reply' => 'From the model', 'ai' => true]);

        $this->postJson('/demo-chat', ['message' => ''])->assertStatus(422);
        $this->withoutVite()->get('/')->assertOk()->assertSee('Live demo');
    }
}
