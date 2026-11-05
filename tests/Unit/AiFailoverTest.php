<?php

namespace Tests\Unit;

use App\Models\Widget;
use App\Services\MultiProviderAiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiFailoverTest extends TestCase
{
    public function test_openrouter_chat_and_failover_to_backup_provider(): void
    {
        config([
            'ai.groq.key' => 'g', 'ai.openrouter.key' => 'o',
            'ai.fallback_provider' => 'openrouter',
        ]);
        Http::fake([
            'api.groq.com/*'   => Http::response(['error' => 'down'], 500),
            'openrouter.ai/*'  => Http::response(['choices' => [['message' => ['content' => 'hi']]], 'usage' => ['total_tokens' => 3]]),
        ]);

        $widget = new Widget(['ai_provider' => 'groq', 'ai_model' => 'llama-3.3-70b-versatile']);
        $result = app(MultiProviderAiService::class)->chat($widget, [['role' => 'user', 'content' => 'hey']]);

        $this->assertSame('hi', $result['content']);
        $this->assertSame('openrouter', $result['provider']);
        $this->assertSame('openai/gpt-4o-mini', $result['model']);

        // No backup configured: the original error surfaces.
        config(['ai.fallback_provider' => null]);
        $this->expectException(\RuntimeException::class);
        app(MultiProviderAiService::class)->chat($widget, [['role' => 'user', 'content' => 'hey']]);
    }
}
