@extends('layouts.portal')

@section('title', 'Change Password | ELIT Portal')
@section('body-class', 'account-page-body')

@section('content')
<section class="account-page" aria-labelledby="change-password-heading">
    <div class="account-form-card">
        <div class="card-heading">
            <h1 id="change-password-heading">Change Password</h1>
            <p>Confirm your current password, then choose a new password with at least 12 characters.</p>
        </div>

        @if($errors->any())
            <div class="notice notice-error" role="alert">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.change.update') }}" data-loading>
            @csrf
            <div class="field">
                <label for="current_password">Current Password</label>
                <div class="password-control">
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" maxlength="1024" required autofocus>
                    <button type="button" class="password-toggle" data-password-toggle="current_password" aria-label="Show password">Show</button>
                </div>
            </div>
            <div class="field">
                <label for="password">New Password</label>
                <div class="password-control">
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" maxlength="1024" required>
                    <button type="button" class="password-toggle" data-password-toggle="password" aria-label="Show password">Show</button>
                </div>
            </div>
            <div class="field">
                <label for="password_confirmation">Confirm New Password</label>
                <div class="password-control">
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" maxlength="1024" required>
                    <button type="button" class="password-toggle" data-password-toggle="password_confirmation" aria-label="Show password">Show</button>
                </div>
            </div>
            <div class="account-form-actions">
                <a class="secondary-button" href="{{ route('systems.index') }}">Cancel</a>
                <button class="primary-button" type="submit" data-loading-text="Changing password...">Change Password</button>
            </div>
        </form>
    </div>
</section>
@endsection
