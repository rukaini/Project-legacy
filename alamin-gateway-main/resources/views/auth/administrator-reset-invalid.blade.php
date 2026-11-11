@extends('layouts.portal')

@section('title', 'Reset Link Unavailable | ELIT Portal')
@section('body-class', 'login-body')

@section('content')
<section class="login-page" aria-labelledby="invalid-reset-heading">
    <div class="auth-card">
        <div class="auth-card-brand">@include('auth.partials.brand')</div>
        <div class="auth-card-body">
            <div class="card-heading">
                <h1 id="invalid-reset-heading">Reset link unavailable</h1>
                <p>This password reset link is invalid, expired, or has already been used.</p>
            </div>
            <a class="primary-button link-button" href="{{ route('password.request') }}">Request a new reset link</a>
        </div>
    </div>
</section>
@endsection
