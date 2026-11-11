@extends('layouts.portal')
@section('title', 'Session expired | ELIT Portal')
@section('content')
<section class="error-card"><span class="error-code">419</span><h1>Your portal session has expired.</h1><p>Sign in again to safely continue.</p><a class="primary-button link-button" href="{{ route('login') }}">Return to login</a></section>
@endsection
