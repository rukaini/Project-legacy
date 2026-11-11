@extends('layouts.app')

@section('title', 'Register HR Admin')

@section('content')
<div class="ui-page-shell">

    <ul class="ui-breadcrumb">
        <li><a href="{{ route('hr.dashboard') }}">Dashboard</a></li>
        <li>/</li>
        <li><a href="{{ route('hr-admins.index') }}">HR Admins</a></li>
        <li>/</li>
        <li aria-current="page">Register</li>
    </ul>

    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1>Register HR Administrator</h1>
            <p>Create a new HR-level account with full system access.</p>
        </div>
    </div>

    @if($errors->any())
        <div class="ui-message ui-message--error">
            <i class="fas fa-triangle-exclamation"></i>
            Please fix the errors below and try again.
        </div>
    @endif

    <form method="POST" action="{{ route('hr-admins.store') }}">
        @csrf

        <div class="ui-card ui-card--section" style="padding:22px;">
            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">

                {{-- Username --}}
                <div class="ui-field">
                    <label class="ui-label" for="username">Username *</label>
                    <input id="username" name="username" type="text" class="ui-input"
                           value="{{ old('username') }}" required maxlength="50"
                           autocomplete="off">
                    @error('username') <div class="ui-field__error">{{ $message }}</div> @enderror
                </div>

                {{-- Email --}}
                <div class="ui-field">
                    <label class="ui-label" for="email">Email *</label>
                    <input id="email" name="email" type="email" class="ui-input"
                           value="{{ old('email') }}" required maxlength="100">
                    @error('email') <div class="ui-field__error">{{ $message }}</div> @enderror
                </div>

                {{-- Phone number --}}
                <div class="ui-field">
                    <label class="ui-label" for="phone_number">Phone Number</label>
                    <input id="phone_number" name="phone_number" type="text" class="ui-input"
                           value="{{ old('phone_number') }}" maxlength="20">
                    @error('phone_number') <div class="ui-field__error">{{ $message }}</div> @enderror
                </div>

                {{-- Role --}}
                <div class="ui-field">
                    <label class="ui-label" for="role">Role</label>
                    <input id="role" name="role" type="text" class="ui-input"
                           value="{{ old('role', 'hr') }}" maxlength="20"
                           placeholder="e.g. hr">
                    @error('role') <div class="ui-field__error">{{ $message }}</div> @enderror
                </div>

                {{-- Password --}}
                <div class="ui-field">
                    <label class="ui-label" for="password">Password *</label>
                    <input id="password" name="password" type="password" class="ui-input"
                           required autocomplete="new-password">
                    <div class="ui-field__helper">Min 8 chars, letters &amp; numbers.</div>
                    @error('password') <div class="ui-field__error">{{ $message }}</div> @enderror
                </div>

                {{-- Confirm password --}}
                <div class="ui-field">
                    <label class="ui-label" for="password_confirmation">Confirm Password *</label>
                    <input id="password_confirmation" name="password_confirmation"
                           type="password" class="ui-input" required autocomplete="new-password">
                </div>
            </div>

            <div style="margin-top:14px;padding:10px 12px;border:1px dashed #cbd5e1;border-radius:10px;color:#64748b;font-size:12px;">
                <i class="fas fa-circle-info" style="color:#2563eb;"></i>
                The <strong>HRID</strong> will be generated automatically
                in the format <code>HRA-YYYY-NNNN</code> (e.g. <code>HRA-2025-0001</code>).
            </div>
        </div>

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
            <a href="{{ route('hr-admins.index') }}" class="ui-button ui-button--compact">
                <i class="fas fa-xmark"></i> Cancel
            </a>
            <button type="submit" class="ui-button ui-button--primary">
                <i class="fas fa-user-plus"></i> Register HR Admin
            </button>
        </div>
    </form>
</div>

<style>
@media (max-width: 767.98px) {
    .ui-card--section > div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endsection