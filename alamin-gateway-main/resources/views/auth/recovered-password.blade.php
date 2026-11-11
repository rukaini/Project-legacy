@extends('layouts.portal')

@section('title', 'Password Recovered | ELIT Portal')
@section('body-class', 'login-body')

@section('content')
<section class="login-page" aria-labelledby="recovery-result-heading">
    <div class="auth-card">
        <div class="auth-card-brand">@include('auth.partials.brand')</div>
        <div class="auth-card-body">
            <div class="card-heading">
                <h1 id="recovery-result-heading">Your new password</h1>
                <p>Your account password has been replaced successfully.</p>
            </div>
            <div class="notice notice-warning" role="alert">
                <strong>Save this password now. It will not be shown again.</strong>
            </div>
            <div class="recovery-password-row">
                <output id="generated-password" class="recovery-password" data-generated-password>{{ $password }}</output>
                <button class="copy-button" type="button" data-copy-target="generated-password">Copy</button>
            </div>
            <p class="copy-status" data-copy-status aria-live="polite"></p>
            <a class="primary-button link-button" href="{{ route('login') }}">Back to Login</a>
        </div>
    </div>
</section>
@endsection
