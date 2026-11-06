<?php

namespace App\Http\Controllers;

use App\Models\Widget;
use App\Services\McpClient;
use App\Support\AiTools;
use App\Support\SafeUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Per-widget AI tools: WooCommerce order lookup / coupons and MCP servers. */
class ToolsController extends Controller
{
    public function show(Widget $widget)
    {
        $this->authorize('update', $widget);

        return view('widgets.tools', [
            'widget' => $widget, 's' => AiTools::settings($widget),
            'calls'  => DB::table('tool_calls')->where('widget_id', $widget->id)->latest('id')->limit(30)->get(),
            'mcpAllowed' => $widget->user->hasFeature('api_access'),
        ]);
    }

    public function saveWoo(Request $request, Widget $widget)
    {
        $this->authorize('update', $widget);
        $d = $request->validate(['url' => 'nullable|url:https|max:255', 'coupon_note' => 'nullable|string|max:120']);
        if (filled($d['url'] ?? null) && ! SafeUrl::isPublic($d['url'], false)) return back()->withErrors(['url' => 'Use your shop\'s public https address.']);
        $this->store($widget, 'woo', ['url' => rtrim((string) ($d['url'] ?? ''), '/'), 'orders' => $request->boolean('orders'), 'coupons' => $request->boolean('coupons'), 'coupon_note' => $d['coupon_note'] ?? '']);

        return back()->with('success', 'WooCommerce tools saved.');
    }

    public function addMcp(Request $request, Widget $widget)
    {
        $this->authorize('update', $widget);
        abort_unless($widget->user->hasFeature('api_access'), 403, 'MCP servers are included in Business and Enterprise plans.');
        $d = $request->validate(['name' => 'required|string|max:60', 'url' => 'required|url:https|max:500', 'auth' => 'nullable|string|max:500']);
        try {
            $tools = (new McpClient($d['url'], $d['auth'] ?? null))->listTools();
        } catch (\Throwable $e) {
            return back()->withErrors(['url' => 'Could not connect: ' . mb_substr($e->getMessage(), 0, 160)])->withInput();
        }
        $servers = AiTools::settings($widget)['mcp'];
        $servers[] = ['id' => Str::lower(Str::random(6)), 'name' => $d['name'], 'url' => $d['url'], 'auth' => filled($d['auth'] ?? null) ? encrypt($d['auth']) : null,
            'tools' => collect($tools)->take(40)->map(fn ($t) => ['name' => (string) $t['name'], 'description' => mb_substr((string) ($t['description'] ?? ''), 0, 300), 'schema' => $t['inputSchema'] ?? [], 'enabled' => false])->all()];
        $this->store($widget, 'mcp', $servers);

        return back()->with('success', count($tools) . ' tools found. Choose which ones the assistant may use.');
    }

    public function saveMcp(Request $request, Widget $widget, string $id)
    {
        $this->authorize('update', $widget);
        $allowed = (array) $request->input('enabled', []);
        $servers = collect(AiTools::settings($widget)['mcp'])->map(function ($srv) use ($id, $allowed, $request) {
            if ($srv['id'] !== $id) return $srv;
            if ($request->boolean('remove')) return null;
            $srv['tools'] = collect($srv['tools'])->map(fn ($t) => ['enabled' => in_array($t['name'], $allowed, true)] + $t)->all();

            return $srv;
        })->filter()->values()->all();
        $this->store($widget, 'mcp', $servers);

        return back()->with('success', $request->boolean('remove') ? 'Server removed.' : 'Tool permissions saved.');
    }

    private function store(Widget $widget, string $key, array $value): void
    {
        $t = is_array($widget->tools) ? $widget->tools : [];
        $t[$key] = $value;
        $widget->update(['tools' => $t]);
    }
}
