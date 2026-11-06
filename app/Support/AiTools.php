<?php

namespace App\Support;

use App\Models\Widget;
use App\Services\McpClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * Tools the assistant may call mid-conversation (works with every AI provider: the model replies
 * with a [[TOOL name {json}]] line, we run it and ask the model again with the result).
 *  - WooCommerce order lookup / coupon, through the PoriFyx WordPress plugin (signed with the widget token).
 *  - Tools from MCP servers the owner registered and allowed.
 */
class AiTools
{
    public static function settings(Widget $w): array
    {
        $t = is_array($w->tools) ? $w->tools : [];

        return [
            'woo' => array_merge(['url' => '', 'orders' => false, 'coupons' => false, 'coupon_note' => ''], (array) ($t['woo'] ?? [])),
            'mcp' => array_values(array_filter((array) ($t['mcp'] ?? []), 'is_array')),
        ];
    }

    /** [name => [description, arguments]] available to this widget. */
    public static function definitions(Widget $w): array
    {
        $s = self::settings($w);
        $d = [];
        if ($s['woo']['url'] && $s['woo']['orders']) {
            $d['woo_order_status'] = ['Look up a WooCommerce order. Ask the visitor for BOTH their order number and the email used on the order first.', '{"order_number": "string", "email": "string"}'];
        }
        if ($s['woo']['url'] && $s['woo']['coupons']) {
            $d['woo_coupon'] = ['Create a one-time discount code for the visitor (' . ($s['woo']['coupon_note'] ?: 'small discount') . '). Only when they hesitate on price or ask for a discount. Needs their email.', '{"email": "string"}'];
        }
        foreach ($s['mcp'] as $srv) {
            foreach ((array) ($srv['tools'] ?? []) as $tool) {
                if (! empty($tool['enabled'])) {
                    $d['mcp__' . $srv['id'] . '__' . $tool['name']] = [mb_substr((string) ($tool['description'] ?? ''), 0, 300), json_encode($tool['schema']['properties'] ?? (object) [])];
                }
            }
        }

        return $d;
    }

    public static function prompt(Widget $w): string
    {
        $defs = self::definitions($w);
        if (! $defs) return '';
        $lines = collect($defs)->map(fn ($d, $n) => "- {$n}: {$d[0]} Arguments: {$d[1]}")->implode("\n");

        return "\n\nTools you can use:\n{$lines}\nTo use a tool, reply with ONLY one line: [[TOOL tool_name {\"arg\": \"value\"}]] and nothing else. "
            . "You will then get the result and must answer the visitor using it. Never invent tool results, never show tool syntax to the visitor.";
    }

    /** Parse a tool request from a model reply. */
    public static function parse(string $reply): ?array
    {
        if (! preg_match('/\[\[TOOL\s+([a-z0-9_\-]+)\s*(\{.*?\})?\s*\]\]/is', $reply, $m)) return null;

        return [$m[1], (array) json_decode($m[2] ?? '{}', true)];
    }

    /** Run a tool; always returns text for the model (errors included), and logs the call. */
    public static function run(Widget $w, ?int $conversationId, string $name, array $args): string
    {
        $start = microtime(true);
        $ok = true;
        try {
            if (! isset(self::definitions($w)[$name])) throw new \RuntimeException('Tool not available.');
            $out = match (true) {
                $name === 'woo_order_status' => self::woo($w, 'order', ['number' => (string) ($args['order_number'] ?? ''), 'email' => strtolower(trim((string) ($args['email'] ?? '')))]),
                $name === 'woo_coupon'       => self::woo($w, 'coupon', ['email' => strtolower(trim((string) ($args['email'] ?? '')))]),
                str_starts_with($name, 'mcp__') => self::mcp($w, $name, $args),
            };
        } catch (\Throwable $e) {
            $ok = false;
            $out = 'Tool failed: ' . mb_substr($e->getMessage(), 0, 200);
        }
        $out = mb_substr((string) $out, 0, 4000); // keep prompts bounded
        DB::table('tool_calls')->insert([
            'widget_id' => $w->id, 'conversation_id' => $conversationId, 'tool' => mb_substr($name, 0, 120),
            'arguments' => json_encode(array_map(fn ($v) => is_string($v) ? mb_substr($v, 0, 200) : $v, $args)), 'ok' => $ok,
            'ms' => (int) ((microtime(true) - $start) * 1000), 'summary' => mb_substr($out, 0, 300), 'created_at' => now(),
        ]);

        return $out;
    }

    /** Signed call to the WordPress plugin: sig = HMAC-SHA256(action|values|ts, widget token). */
    private static function woo(Widget $w, string $action, array $params): string
    {
        foreach ($params as $k => $v) if ($v === '') throw new \RuntimeException("Missing {$k}.");
        $base = rtrim(self::settings($w)['woo']['url'], '/');
        if (! SafeUrl::isPublic($base)) throw new \RuntimeException('Shop address is not reachable.');
        $ts = (string) time();
        $sig = hash_hmac('sha256', $action . '|' . implode('|', $params) . '|' . $ts, (string) $w->token);
        $r = Http::timeout(10)->withOptions(SafeUrl::httpOptions())->acceptJson()->post($base . '/wp-json/porifyx/v1/' . $action, $params + ['ts' => $ts, 'sig' => $sig]);
        if (! $r->successful()) throw new \RuntimeException($r->json('message') ?: 'Shop returned ' . $r->status());

        return json_encode($r->json(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function mcp(Widget $w, string $name, array $args): string
    {
        [, $id, $tool] = explode('__', $name, 3);
        $srv = collect(self::settings($w)['mcp'])->firstWhere('id', $id);
        if (! $srv) throw new \RuntimeException('Server removed.');

        return (new McpClient($srv['url'], filled($srv['auth'] ?? null) ? decrypt($srv['auth']) : null))->callTool($tool, $args);
    }
}
