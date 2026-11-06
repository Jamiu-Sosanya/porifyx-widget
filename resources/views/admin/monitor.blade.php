@extends('layouts.admin')
@section('title', 'Widget monitor')
@section('page-title', 'Widget monitor')
@section('content')
<p class="mb-5 text-sm text-gray-400">Errors reported by widgets running on customer websites (failed loads, failed replies). A spike usually means a broken embed or an AI provider problem.</p>
<div class="grid xl:grid-cols-[360px_minmax(0,1fr)] gap-6">
    <section class="rounded-2xl border border-white/5 bg-gray-900/50 p-5">
        <h3 class="text-sm font-semibold text-white">Last 7 days by widget</h3>
        <ul class="mt-3 divide-y divide-white/5">
            @forelse($byWidget as $w)
            <li class="py-2.5 flex items-center justify-between gap-3 text-sm"><span class="truncate text-gray-200">{{ $w->name }}</span><span class="shrink-0 text-xs"><strong class="text-rose-400">{{ $w->errors }}</strong> <span class="text-gray-500">· {{ \Carbon\Carbon::parse($w->last_at)->diffForHumans() }}</span></span></li>
            @empty
            <li class="py-6 text-center text-sm text-gray-500">No widget errors. 🎉</li>
            @endforelse
        </ul>
    </section>
    <section class="rounded-2xl border border-white/5 bg-gray-900/50 overflow-x-auto">
        <table class="w-full text-sm">
            <thead><tr class="text-left"><th class="px-4 py-3">When</th><th class="px-4 py-3">Widget</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Message</th><th class="px-4 py-3">Page</th></tr></thead>
            <tbody>
                @forelse($recent as $e)
                <tr class="border-t border-white/5 text-gray-300 align-top">
                    <td class="px-4 py-2.5 whitespace-nowrap text-xs text-gray-500">{{ \Carbon\Carbon::parse($e->created_at)->diffForHumans() }}</td>
                    <td class="px-4 py-2.5">{{ $e->widget_name }}</td>
                    <td class="px-4 py-2.5"><span class="px-2 py-0.5 rounded-full bg-rose-500/15 text-rose-400 text-[11px] font-semibold">{{ $e->type }}</span></td>
                    <td class="px-4 py-2.5 text-xs break-words max-w-xs">{{ $e->message }}</td>
                    <td class="px-4 py-2.5 text-xs break-all max-w-xs text-gray-500">{{ $e->page_url }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">Nothing reported.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
