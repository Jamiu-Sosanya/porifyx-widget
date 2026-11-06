@extends('layouts.app')
@section('title', 'Chat flows')
@section('content')
<p class="mb-4 text-sm text-gray-400">Chat flows belong to a widget. Pick the widget to build flows for.</p>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
    @foreach($widgets as $w)
    <a href="{{ route('widgets.flows', $w) }}" class="rounded-2xl border border-white/5 bg-gray-900/50 p-4 hover:border-brand-500/40">
        <p class="text-sm font-semibold text-white">{{ $w->name }}</p>
        <p class="mt-1 text-xs {{ $w->is_active ? 'text-emerald-400' : 'text-gray-500' }}">{{ $w->is_active ? 'Live' : 'Off' }} · Open flow builder →</p>
    </a>
    @endforeach
</div>
@endsection