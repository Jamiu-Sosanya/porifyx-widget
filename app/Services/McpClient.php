<?php

namespace App\Services;

use App\Support\SafeUrl;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Minimal Model Context Protocol client (Streamable HTTP transport, JSON-RPC 2.0).
 * Each use does initialize -> request; works with servers answering JSON or SSE.
 */
class McpClient
{
    public function __construct(private string $url, private ?string $auth = null) {}

    public function listTools(): array
    {
        return (array) ($this->request('tools/list', [])['tools'] ?? []);
    }

    public function callTool(string $name, array $args): string
    {
        $r = $this->request('tools/call', ['name' => $name, 'arguments' => (object) $args]);
        $text = collect($r['content'] ?? [])->map(fn ($c) => ($c['type'] ?? '') === 'text' ? ($c['text'] ?? '') : json_encode($c))->implode("\n");
        if (! empty($r['isError'])) throw new \RuntimeException('Tool error: ' . mb_substr($text, 0, 300));

        return $text;
    }

    private function request(string $method, array $params): array
    {
        if (! SafeUrl::isPublic($this->url, false)) throw new \RuntimeException('MCP server must be a public https URL.');
        $breaker = 'mcp:fail:' . md5($this->url);
        if (Cache::get($breaker, 0) >= 3) throw new \RuntimeException('MCP server is paused after repeated failures (retry in a few minutes).');

        try {
            $headers = array_filter(['Accept' => 'application/json, text/event-stream', 'Authorization' => $this->auth ?: null]);
            $init = $this->post($headers, ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'initialize', 'params' => [
                'protocolVersion' => '2025-03-26', 'capabilities' => (object) [], 'clientInfo' => ['name' => 'porifyx-widget', 'version' => '1.0'],
            ]]);
            if ($session = $init['session']) $headers['Mcp-Session-Id'] = $session;
            $this->post($headers, ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], true);
            $res = $this->post($headers, ['jsonrpc' => '2.0', 'id' => 2, 'method' => $method, 'params' => (object) $params]);
            if (isset($res['body']['error'])) throw new \RuntimeException('MCP error: ' . ($res['body']['error']['message'] ?? 'unknown'));
            Cache::forget($breaker);

            return (array) ($res['body']['result'] ?? []);
        } catch (\Throwable $e) {
            Cache::put($breaker, Cache::get($breaker, 0) + 1, 300);
            throw $e;
        }
    }

    private function post(array $headers, array $payload, bool $notification = false): array
    {
        $r = Http::timeout(15)->withOptions(SafeUrl::httpOptions())->withHeaders($headers)->asJson()->post($this->url, $payload);
        if ($notification) return ['session' => null, 'body' => []];
        $r->throw();
        $raw = $r->body();
        if (str_contains((string) $r->header('Content-Type'), 'text/event-stream')) {
            // Last "data:" line carries the JSON-RPC response.
            preg_match_all('/^data:\s*(.+)$/m', $raw, $m);
            $raw = end($m[1]) ?: '{}';
        }

        return ['session' => $r->header('Mcp-Session-Id') ?: null, 'body' => json_decode($raw, true) ?: []];
    }
}
