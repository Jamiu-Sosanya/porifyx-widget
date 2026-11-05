<?php

namespace App\Services;

use App\Models\Widget;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;

/**
 * Abstraction layer for multiple AI providers.
 * Supports: OpenAI, Anthropic Claude, Google Gemini, Groq, OpenRouter.
 * Non-streamed calls fail over to config("ai.fallback_provider") on error.
 */
class MultiProviderAiService
{
    public function chat(Widget $widget, array $messages, int $maxTokens = 512): array
    {
        $provider = $widget->ai_provider ?? 'openai';
        $model    = $widget->ai_model ?: $this->defaultModel($provider);
        $temp     = $widget->temperature ?? 0.7;

        try {
            return $this->dispatch($provider, $model, $messages, $maxTokens, $temp);
        } catch (\Throwable $e) {
            // Failover: retry once on the configured backup provider.
            $fallback = config('ai.fallback_provider');
            if (! $fallback || $fallback === $provider || blank(config("ai.{$fallback}.key"))) {
                throw $e;
            }
            Log::warning("AI provider {$provider} failed, failing over to {$fallback}", ['error' => $e->getMessage()]);

            return $this->dispatch($fallback, $this->defaultModel($fallback), $messages, $maxTokens, $temp);
        }
    }

    protected function dispatch(string $provider, string $model, array $messages, int $maxTokens, float $temp): array
    {
        return match ($provider) {
            'anthropic'          => $this->anthropicChat($model, $messages, $maxTokens, $temp),
            'gemini'             => $this->geminiChat($model, $messages, $maxTokens, $temp),
            'groq', 'openrouter' => $this->compatChat($provider, $model, $messages, $maxTokens, $temp),
            default              => $this->openaiChat($model, $messages, $maxTokens, $temp),
        };
    }

    public function chatStreamed(Widget $widget, array $messages, int $maxTokens = 512): \Generator
    {
        $provider = $widget->ai_provider ?? 'openai';
        $model    = $widget->ai_model ?: $this->defaultModel($provider);
        $temp     = $widget->temperature ?? 0.7;

        return match ($provider) {
            'anthropic'          => $this->anthropicStream($model, $messages, $maxTokens, $temp),
            'gemini'             => $this->geminiStream($model, $messages, $maxTokens, $temp),
            'groq', 'openrouter' => $this->compatStream($provider, $model, $messages, $maxTokens, $temp),
            default              => $this->openaiStream($model, $messages, $maxTokens, $temp),
        };
    }

    // ── OpenAI ────────────────────────────────────────────────────────────────

    protected function openaiChat(string $model, array $messages, int $maxTokens, float $temp): array
    {
        $response = OpenAI::chat()->create([
            'model'       => $model,
            'messages'    => $messages,
            'max_tokens'  => $maxTokens,
            'temperature' => $temp,
        ]);

        $content = $response->choices[0]->message->content ?? '';

        if ($content === '' || $content === null) {
            Log::warning('OpenAI returned empty content', [
                'model'          => $model,
                'finish_reason'  => $response->choices[0]->finishReason ?? 'unknown',
            ]);
        }

        return [
            'content'      => (string) $content,
            'tokens_used'  => $response->usage->totalTokens ?? 0,
            'provider'     => 'openai',
            'model'        => $model,
        ];
    }

    protected function openaiStream(string $model, array $messages, int $maxTokens, float $temp): \Generator
    {
        $stream = OpenAI::chat()->createStreamed([
            'model'       => $model,
            'messages'    => $messages,
            'max_tokens'  => $maxTokens,
            'temperature' => $temp,
        ]);

        foreach ($stream as $response) {
            $delta = $response->choices[0]->delta->content ?? '';
            if ($delta !== '') {
                yield $delta;
            }
        }
    }

    // ── Anthropic Claude ──────────────────────────────────────────────────────

    protected function anthropicChat(string $model, array $messages, int $maxTokens, float $temp): array
    {
        $apiKey = config('ai.anthropic.key');
        if (!$apiKey) {
            throw new \RuntimeException('Anthropic API key not configured.');
        }

        // Extract system message and user/assistant turns
        $system = '';
        $turns  = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system = $msg['content'];
            } else {
                $role    = $msg['role'] === 'assistant' ? 'assistant' : 'user';
                $turns[] = ['role' => $role, 'content' => $msg['content']];
            }
        }

        $body = array_filter([
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'temperature'=> $temp,
            'system'     => $system ?: null,
            'messages'   => $turns,
        ]);

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', $body);

        if ($response->failed()) {
            throw new \RuntimeException('Anthropic API error: ' . $response->body());
        }

        $data = $response->json();

        return [
            'content'     => $data['content'][0]['text'] ?? '',
            'tokens_used' => ($data['usage']['input_tokens'] ?? 0) + ($data['usage']['output_tokens'] ?? 0),
            'provider'    => 'anthropic',
            'model'       => $model,
        ];
    }

    protected function anthropicStream(string $model, array $messages, int $maxTokens, float $temp): \Generator
    {
        $apiKey = config('ai.anthropic.key');
        if (!$apiKey) {
            throw new \RuntimeException('Anthropic API key not configured.');
        }

        $system = '';
        $turns  = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system = $msg['content'];
            } else {
                $role    = $msg['role'] === 'assistant' ? 'assistant' : 'user';
                $turns[] = ['role' => $role, 'content' => $msg['content']];
            }
        }

        $body = array_filter([
            'model'      => $model,
            'max_tokens' => $maxTokens,
            'temperature'=> $temp,
            'system'     => $system ?: null,
            'messages'   => $turns,
            'stream'     => true,
        ]);

        $response = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(90)->withOptions(['stream' => true])
          ->post('https://api.anthropic.com/v1/messages', $body);

        $body = $response->toPsrResponse()->getBody();

        $buffer = '';
        while (!$body->eof()) {
            $buffer .= $body->read(256);
            $lines   = explode("\n", $buffer);
            $buffer  = array_pop($lines);

            foreach ($lines as $line) {
                $line = trim($line);
                if (!str_starts_with($line, 'data:')) continue;
                $json = trim(substr($line, 5));
                if ($json === '[DONE]') break 2;
                $event = json_decode($json, true);
                if (($event['type'] ?? '') === 'content_block_delta') {
                    $text = $event['delta']['text'] ?? '';
                    if ($text !== '') yield $text;
                }
            }
        }
    }

    // ── Google Gemini ─────────────────────────────────────────────────────────

    protected function geminiChat(string $model, array $messages, int $maxTokens, float $temp): array
    {
        $apiKey = config('ai.gemini.key');
        if (!$apiKey) {
            throw new \RuntimeException('Gemini API key not configured.');
        }

        ['system' => $system, 'contents' => $contents] = $this->toGeminiFormat($messages);

        $body = [
            'contents'          => $contents,
            'systemInstruction' => $system ? ['parts' => [['text' => $system]]] : null,
            'generationConfig'  => [
                'maxOutputTokens' => $maxTokens,
                'temperature'     => $temp,
            ],
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

        $response = Http::timeout(60)->post($url, array_filter($body));

        if ($response->failed()) {
            throw new \RuntimeException('Gemini API error: ' . $response->body());
        }

        $data    = $response->json();
        $content = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $tokens  = ($data['usageMetadata']['totalTokenCount'] ?? 0);

        return ['content' => $content, 'tokens_used' => $tokens, 'provider' => 'gemini', 'model' => $model];
    }

    protected function geminiStream(string $model, array $messages, int $maxTokens, float $temp): \Generator
    {
        $apiKey = config('ai.gemini.key');
        if (!$apiKey) throw new \RuntimeException('Gemini API key not configured.');

        ['system' => $system, 'contents' => $contents] = $this->toGeminiFormat($messages);

        $body = [
            'contents'          => $contents,
            'systemInstruction' => $system ? ['parts' => [['text' => $system]]] : null,
            'generationConfig'  => ['maxOutputTokens' => $maxTokens, 'temperature' => $temp],
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:streamGenerateContent?alt=sse&key={$apiKey}";

        $response = Http::timeout(90)->withOptions(['stream' => true])
                         ->post($url, array_filter($body));

        $bodyStream = $response->toPsrResponse()->getBody();
        $buffer = '';
        while (!$bodyStream->eof()) {
            $buffer .= $bodyStream->read(256);
            $lines   = explode("\n", $buffer);
            $buffer  = array_pop($lines);

            foreach ($lines as $line) {
                $line = trim($line);
                if (!str_starts_with($line, 'data:')) continue;
                $json  = trim(substr($line, 5));
                $event = json_decode($json, true);
                $text  = $event['candidates'][0]['content']['parts'][0]['text'] ?? '';
                if ($text !== '') yield $text;
            }
        }
    }

    protected function toGeminiFormat(array $messages): array
    {
        $system   = '';
        $contents = [];
        foreach ($messages as $msg) {
            if ($msg['role'] === 'system') {
                $system = $msg['content'];
                continue;
            }
            $role      = $msg['role'] === 'assistant' ? 'model' : 'user';
            $contents[] = ['role' => $role, 'parts' => [['text' => $msg['content']]]];
        }
        return ['system' => $system, 'contents' => $contents];
    }

    // ── OpenAI-compatible endpoints (Groq, OpenRouter) ───────────────────────

    protected const COMPAT_URLS = [
        'groq'       => 'https://api.groq.com/openai/v1/chat/completions',
        'openrouter' => 'https://openrouter.ai/api/v1/chat/completions',
    ];

    protected function compatRequest(string $provider, int $timeout)
    {
        $apiKey = config("ai.{$provider}.key");
        if (! $apiKey) throw new \RuntimeException(ucfirst($provider) . ' API key not configured.');

        return Http::withToken($apiKey)->timeout($timeout)->withHeaders([
            'HTTP-Referer' => config('app.url'),   // OpenRouter attribution; ignored by Groq
            'X-Title'      => config('app.name'),
        ]);
    }

    protected function compatChat(string $provider, string $model, array $messages, int $maxTokens, float $temp): array
    {
        $response = $this->compatRequest($provider, 30)->post(self::COMPAT_URLS[$provider], [
            'model'       => $model,
            'messages'    => $messages,
            'max_tokens'  => $maxTokens,
            'temperature' => $temp,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException(ucfirst($provider) . ' API error: ' . $response->body());
        }

        $data = $response->json();
        return [
            'content'     => $data['choices'][0]['message']['content'] ?? '',
            'tokens_used' => $data['usage']['total_tokens'] ?? 0,
            'provider'    => $provider,
            'model'       => $model,
        ];
    }

    protected function compatStream(string $provider, string $model, array $messages, int $maxTokens, float $temp): \Generator
    {
        $response = $this->compatRequest($provider, 60)
            ->withOptions(['stream' => true])
            ->post(self::COMPAT_URLS[$provider], [
                'model'       => $model,
                'messages'    => $messages,
                'max_tokens'  => $maxTokens,
                'temperature' => $temp,
                'stream'      => true,
            ]);

        $bodyStream = $response->toPsrResponse()->getBody();
        $buffer = '';
        while (!$bodyStream->eof()) {
            $buffer .= $bodyStream->read(256);
            $lines   = explode("\n", $buffer);
            $buffer  = array_pop($lines);

            foreach ($lines as $line) {
                $line = trim($line);
                if (!str_starts_with($line, 'data:')) continue;
                $json = trim(substr($line, 5));
                if ($json === '[DONE]') break 2;
                $event = json_decode($json, true);
                $text  = $event['choices'][0]['delta']['content'] ?? '';
                if ($text !== '') yield $text;
            }
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function defaultModel(string $provider): string
    {
        return match ($provider) {
            'anthropic'  => config('ai.anthropic.model', 'claude-sonnet-4-5'),
            'gemini'     => config('ai.gemini.model', 'gemini-2.5-flash'),
            'groq'       => config('ai.groq.model', 'llama-3.3-70b-versatile'),
            'openrouter' => config('ai.openrouter.model', 'openai/gpt-4o-mini'),
            default      => config('ai.openai.model', 'gpt-4o-mini'),
        };
    }

    /** Single source of truth for providers and the widget editor's model pickers. */
    public static function providers(): array
    {
        return [
            'openai'     => ['OpenAI', 'GPT-4.1, GPT-4o', '#10a37f'],
            'anthropic'  => ['Anthropic', 'Claude Sonnet / Opus / Haiku', '#d97706'],
            'gemini'     => ['Google Gemini', 'Gemini 2.5 Pro / Flash', '#4285f4'],
            'groq'       => ['Groq', 'Llama, GPT-OSS (ultra-fast)', '#f55036'],
            'openrouter' => ['OpenRouter', '300+ models, one key', '#6467f2'],
        ];
    }

    public static function availableModels(): array
    {
        return [
            'openai' => [
                'gpt-4.1-mini' => 'GPT-4.1 Mini (Balanced)',
                'gpt-4.1'      => 'GPT-4.1 (Best quality)',
                'gpt-4o'       => 'GPT-4o',
                'gpt-4o-mini'  => 'GPT-4o Mini (Fast & economical)',
            ],
            'anthropic' => [
                'claude-sonnet-4-5' => 'Claude Sonnet 4.5 (Balanced)',
                'claude-opus-4-1'   => 'Claude Opus 4.1 (Most capable)',
                'claude-haiku-4-5'  => 'Claude Haiku 4.5 (Fastest)',
            ],
            'gemini' => [
                'gemini-2.5-flash'      => 'Gemini 2.5 Flash (Recommended)',
                'gemini-2.5-pro'        => 'Gemini 2.5 Pro (Best)',
                'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash-Lite (Economy)',
            ],
            'groq' => [
                'llama-3.3-70b-versatile' => 'Llama 3.3 70B (Best quality)',
                'llama-3.1-8b-instant'    => 'Llama 3.1 8B (Instant)',
                'openai/gpt-oss-120b'     => 'GPT-OSS 120B',
            ],
            'openrouter' => [
                'openai/gpt-4o-mini'                => 'OpenAI GPT-4o Mini',
                'anthropic/claude-sonnet-4.5'       => 'Claude Sonnet 4.5',
                'google/gemini-2.5-flash'           => 'Gemini 2.5 Flash',
                'meta-llama/llama-3.3-70b-instruct' => 'Llama 3.3 70B',
                'deepseek/deepseek-chat'            => 'DeepSeek V3',
            ],
        ];
    }
}
