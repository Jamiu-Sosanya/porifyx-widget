@php $pp = \App\Support\PrivacyPolicy::get(); @endphp
@extends('layouts.doc')
@section('title', $pp['title'])
@section('description', 'How ' . config('branding.name') . ' collects, uses and protects personal data.')
@section('eyebrow', $pp['eyebrow'])
@section('heading', $pp['title'])
@section('meta', $pp['effective_date'] ? 'Effective: ' . $pp['effective_date'] : '')

@section('body')
@if($pp['contact_email'])
<div class="not-prose rounded-2xl border border-slate-200 dark:border-white/10 p-5 mb-8">
    <p class="font-semibold">{{ $pp['contact_team'] }}</p>
    <p class="mt-1"><a href="mailto:{{ $pp['contact_email'] }}">{{ $pp['contact_email'] }}</a></p>
    @if($pp['response_promise'])<p class="mt-2 text-sm opacity-80">{{ $pp['response_promise'] }}</p>@endif
</div>
@endif
{!! \App\Support\PrivacyPolicy::html($pp['summary']) !!}
@if($pp['collection'])<h2 id="collection">What we collect and how we use it</h2>{!! \App\Support\PrivacyPolicy::html($pp['collection']) !!}@endif
@if($pp['rights'])<h2 id="rights">Your rights</h2>{!! \App\Support\PrivacyPolicy::html($pp['rights']) !!}@endif
@if($pp['retention_days'])<h2 id="retention">How long we keep chats</h2><p>Chat conversations are deleted automatically after {{ $pp['retention_days'] }} days.</p>@endif
@include('pages.partials.privacy-request')
@endsection
