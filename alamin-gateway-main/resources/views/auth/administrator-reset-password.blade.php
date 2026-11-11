@extends('layouts.portal')

@section('title', 'Reset Administrator Password | ELIT Portal')
@section('body-class', 'login-body')

@section('content')
<section class="login-page" aria-labelledby="reset-heading">
    <div class="auth-card">
        <div class="auth-card-brand">@include('auth.partials.brand')</div>
        <div class="auth-card-body">
            <div class="card-heading">
                <h1 id="reset-heading">Reset Administrator password</h1>
                <p>Choose a new password for your protected Administrator account.</p>
            </div>
            @if(isset($resetError))
                <div class="notice notice-error" role="alert">{{ $resetError }}</div>
            @elseif($errors->any())
                <div class="notice notice-error" role="alert">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('password.admin.update', ['token' => $token]) }}" data-loading>
                @csrf
                <div class="field">
                    <label for="password">New Password</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="1024" required autofocus>
                        <button type="button" data-password-toggle="password" aria-label="Show password">Show</button>
                    </div>
                    <p class="field-help">Use at least 12 characters.</p>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm New Password</label>
                    <div class="password-field">
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="1024" required>
                        <button type="button" data-password-toggle="password_confirmation" aria-label="Show password confirmation">Show</button>
                    </div>
                </div>
                <button class="primary-button" type="submit" data-loading-text="Resetting...">Reset password</button>
            </form>
        </div>
    </div>
</section>
@endsection
