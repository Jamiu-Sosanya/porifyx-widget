@extends('layouts.app')
@section('page-title', 'Settings')
@section('content')
{{-- Settings sub-pages are listed under "Settings" in the sidebar. --}}
<div class="max-w-5xl space-y-6">
    @yield('settings')
</div>
@endsection
