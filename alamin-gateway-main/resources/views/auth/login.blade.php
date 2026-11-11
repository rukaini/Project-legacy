@extends('layouts.portal')

@section('title', 'Login | ELIT Portal')
@section('body-class', 'login-body')

@section('content')
<section class="login-page" aria-labelledby="login-heading">
    <div class="auth-card">
        <div class="auth-card-brand">@include('auth.partials.brand')</div>
        <div class="auth-card-body">
            <div class="card-heading">
                <h1 id="login-heading">Login</h1>
                <p>Sign in to continue to your available systems.</p>
            </div>
            @if(session('status'))<div class="notice notice-info" role="status">{{ session('status') }}</div>@endif
            @if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('login.store') }}" data-loading>
                @csrf
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="username" inputmode="email" maxlength="255" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" autocomplete="current-password" maxlength="1024" required>
                        <button type="button" data-password-toggle="password" aria-label="Show password">Show</button>
                    </div>
                </div>
                <div class="forgot-password-link"><a href="{{ route('password.request') }}">Forgot password?</a></div>
                <button class="primary-button" type="submit" data-loading-text="Logging in...">Login</button>
            </form>
            <p class="auth-switch">Need a public account? <a href="{{ route('register') }}">Create account</a></p>
        </div>
    </div>
</section>
@endsection
