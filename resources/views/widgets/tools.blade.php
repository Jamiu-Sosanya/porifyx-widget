@extends('layouts.app')
@section('title', 'AI tools: ' . $widget->name)
@section('page-title', 'AI tools')
@section('content')
@php $field = 'w-full bg-white/5 border border-white/10 rounded-xl px-3.5 py-2.5 text-sm text-white'; $woo = $s['woo']; @endphp
<p class="mb-5 text-sm text-gray-400 max-w-3xl">Let the assistant take actions during a chat: look up orders, give a discount code, or call your own systems through MCP. It only uses the tools you switch on, and every call is logged below.</p>
@if($errors->any())<p class="mb-4 rounded-xl bg-rose-500/10 border border-rose-500/20 p-3 text-sm text-rose-300">{{ $errors->first() }}</p>@endif
<div class="grid xl:grid-cols-2 gap-6">
    <form method="POST" action="{{ route('widgets.tools.woo', $widget) }}" class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 space-y-3">
        @csrf @method('PUT')
        <h3 class="text-base font-semibold text-white">🛒 WooCommerce</h3>
        <p class="text-xs text-gray-500">Needs the PoriFyx WordPress plugin (Embed code page) on your shop. Requests are signed with this widget's token.</p>
        <input name="url" type="url" value="{{ $woo['url'] }}" placeholder="https://yourshop.com" class="{{ $field }}">
        <label class="flex items-start gap-2.5 text-sm text-gray-200"><input type="checkbox" name="orders" value="1" @checked($woo['orders']) class="mt-1">
            <span>Order lookup<span class="block text-xs text-gray-500">“Where is my order?” The assistant asks for the order number and the email on the order, and only answers when both match.</span></span></label>
        <label class="flex items-start gap-2.5 text-sm text-gray-200"><input type="checkbox" name="coupons" value="1" @checked($woo['coupons']) class="mt-1">
            <span>Discount codes<span class="block text-xs text-gray-500">Creates a one-time code when a visitor hesitates on price. Amount and limits are set in the plugin (one code per email).</span></span></label>
        <input name="coupon_note" value="{{ $woo['coupon_note'] }}" maxlength="120" placeholder="How you describe the discount, e.g. 10% off your first order" class="{{ $field }}">
        <button class="px-5 py-2.5 rounded-xl bg-brand-500 text-white text-sm font-semibold">Save</button>
    </form>

    <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5 sm:p-6 space-y-4">
        <h3 class="text-base font-semibold text-white">🔌 MCP servers</h3>
        @if(! $mcpAllowed)
        <p class="text-sm text-amber-300">Connecting your own systems through MCP is included in Business and Enterprise plans. <a href="{{ route('settings.billing') }}" class="underline">Upgrade</a></p>
        @else
        <p class="text-xs text-gray-500">Connect any Model Context Protocol server (your CRM, order system or internal API). Tools stay off until you allow them.</p>
        <form method="POST" action="{{ route('widgets.tools.mcp', $widget) }}" class="grid sm:grid-cols-2 gap-2">
            @csrf
            <input name="name" required maxlength="60" value="{{ old('name') }}" placeholder="Name, e.g. Orders API" class="{{ $field }}">
            <input name="url" type="url" required value="{{ old('url') }}" placeholder="https://mcp.yourcompany.com/mcp" class="{{ $field }}">
            <input name="auth" maxlength="500" placeholder="Authorization header (optional), e.g. Bearer sk_…" class="{{ $field }} sm:col-span-2">
            <button class="sm:col-span-2 justify-self-start px-5 py-2.5 rounded-xl bg-brand-500 text-white text-sm font-semibold">Connect and discover tools</button>
        </form>
        @foreach($s['mcp'] as $srv)
        <form method="POST" action="{{ route('widgets.tools.mcp.save', [$widget, $srv['id']]) }}" class="rounded-xl border border-white/5 bg-white/[0.02] p-4 space-y-2">
            @csrf @method('PUT')
            <p class="text-sm font-semibold text-white">{{ $srv['name'] }} <span class="text-xs font-normal text-gray-500 break-all">{{ $srv['url'] }}</span></p>
            @forelse($srv['tools'] as $t)
            <label class="flex items-start gap-2.5 text-sm text-gray-200"><input type="checkbox" name="enabled[]" value="{{ $t['name'] }}" @checked($t['enabled']) class="mt-1">
                <span class="min-w-0"><span class="font-mono text-xs text-brand-300">{{ $t['name'] }}</span><span class="block text-xs text-gray-500">{{ $t['description'] }}</span></span></label>
            @empty
            <p class="text-xs text-gray-500">This server has no tools.</p>
            @endforelse
            <div class="flex gap-2 pt-1">
                <button class="px-4 py-2 rounded-lg bg-white/5 hover:bg-white/10 text-xs font-semibold text-white">Save permissions</button>
                <button name="remove" value="1" onclick="return confirm('Remove this server?')" class="px-4 py-2 rounded-lg bg-rose-500/10 text-rose-400 text-xs font-semibold">Remove</button>
            </div>
        </form>
        @endforeach
        @endif
    </section>
</div>

<section class="mt-6 rounded-2xl border border-white/5 bg-gray-900/50 overflow-x-auto">
    <h3 class="px-5 pt-5 text-sm font-semibold text-white">Recent tool calls</h3>
    <table class="w-full text-sm mt-3">
        <thead><tr class="text-left"><th class="px-5 py-2.5">When</th><th class="px-5 py-2.5">Tool</th><th class="px-5 py-2.5">Result</th><th class="px-5 py-2.5">Time</th></tr></thead>
        <tbody>
            @forelse($calls as $c)
            <tr class="border-t border-white/5 text-gray-300 align-top">
                <td class="px-5 py-2.5 text-xs text-gray-500 whitespace-nowrap">{{ \Carbon\Carbon::parse($c->created_at)->diffForHumans() }}</td>
                <td class="px-5 py-2.5 font-mono text-xs">{{ $c->tool }}</td>
                <td class="px-5 py-2.5 text-xs"><span class="{{ $c->ok ? 'text-emerald-400' : 'text-rose-400' }}">{{ $c->ok ? 'OK' : 'Failed' }}</span> <span class="text-gray-500 break-words">{{ \Illuminate\Support\Str::limit($c->summary, 120) }}</span></td>
                <td class="px-5 py-2.5 text-xs text-gray-500">{{ $c->ms }} ms</td>
            </tr>
            @empty
            <tr><td colspan="4" class="px-5 py-8 text-center text-gray-500">No tool calls yet.</td></tr>
            @endforelse
        </tbody>
    </table>
</section>
@endsection
