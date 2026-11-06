<?php

namespace App\Support;

use App\Models\Widget;
use App\Services\MultiProviderAiService;

/**
 * Drop-in for OpenAI::chat()->create([...]) that runs on the admin-chosen provider
 * (with failover). Returns the same shape callers read: ->choices[0]->message->content.
 */
class Ai
{
    public static function create(array $params): object
    {
        $r = app(MultiProviderAiService::class)->chat(
            new Widget(['temperature' => $params['temperature'] ?? 0.3]),
            $params['messages'] ?? [],
            (int) ($params['max_tokens'] ?? 300),
        );

        return (object) ['choices' => [(object) ['message' => (object) ['content' => (string) ($r['content'] ?? '')]]]];
    }
}
