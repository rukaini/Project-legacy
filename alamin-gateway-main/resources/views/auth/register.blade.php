@extends('layouts.portal')

@section('title', 'Register | ELIT Portal')
@section('body-class', 'login-body')

@section('content')
<section class="login-page" aria-labelledby="register-heading">
    <div class="auth-card auth-card-register">
        <div class="auth-card-brand">@include('auth.partials.brand')</div>
        <div class="auth-card-body">
            <div class="card-heading">
                <h1 id="register-heading">Create account</h1>
                <p>Register for an account.</p>
            </div>
            @if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif
            <form method="POST" action="{{ route('register.store') }}" data-loading>
                @csrf
                <div class="field">
                    <label for="name">Full Name</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" maxlength="255" required autofocus>
                </div>
                <div class="field">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" maxlength="255" required>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="1024" aria-describedby="password-guidance" data-registration-password required>
                        <button type="button" data-password-toggle="password" aria-label="Show password">Show</button>
                    </div>
                    <p id="password-guidance" class="field-help" data-password-guidance aria-live="polite">Use at least 8 characters.</p>
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm password</label>
                    <div class="password-field">
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="1024" aria-describedby="confirmation-guidance" data-password-confirmation required>
                        <button type="button" data-password-toggle="password_confirmation" aria-label="Show password confirmation">Show</button>
                    </div>
                    <p id="confirmation-guidance" class="field-help" data-confirmation-guidance aria-live="polite">Re-enter your password to confirm it.</p>
                </div>
                <button class="primary-button" type="submit" data-loading-text="Creating account...">Register</button>
            </form>
            <p class="auth-switch">Already have an account? <a href="{{ route('login') }}">Back to login</a></p>
        </div>
    </div>
</section>
@endsection
