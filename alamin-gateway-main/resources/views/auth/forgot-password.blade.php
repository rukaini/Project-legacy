@extends('layouts.portal')

@section('title', 'Recover Account | ELIT Portal')
@section('body-class', 'login-body')

@section('content')
<section class="login-page" aria-labelledby="recovery-heading">
    <div class="auth-card">
        <div class="auth-card-brand">@include('auth.partials.brand')</div>
        <div class="auth-card-body">
            <div class="card-heading">
                <h1 id="recovery-heading">Recover account</h1>
                <p>Enter your IC number or email to recover your account.</p>
            </div>
            @if(isset($recoveryStatus))
                <div class="notice notice-info" role="status">{{ $recoveryStatus }}</div>
            @elseif(isset($recoveryError))
                <div class="notice notice-error" role="alert">{{ $recoveryError }}</div>
            @elseif($errors->any())
                <div class="notice notice-error" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('password.recovery.store') }}" data-loading>
                @csrf
                <div class="field">
                    <label for="identifier">IC Number or Email</label>
                    <input id="identifier" name="identifier" type="text" value="{{ $identifier ?? old('identifier') }}" autocomplete="username" maxlength="255" required autofocus>
                </div>
                <button class="primary-button" type="submit" data-loading-text="Recovering...">Recover password</button>
            </form>
            <p class="auth-switch"><a href="{{ route('login') }}">Back to Login</a></p>
        </div>
    </div>
</section>
@endsection
