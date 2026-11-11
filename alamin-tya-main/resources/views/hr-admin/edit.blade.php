@extends('layouts.app')

@section('title', 'Edit HR Admin')

@section('content')
@php
    // Determine if the current user is editing their own account
    $isSelf = session('userID') === $hrAdmin->hrid
           || session('hrid')   === $hrAdmin->hrid
           || session('userName') === $hrAdmin->username;
@endphp

<div class="ui-page-shell">

    <ul class="ui-breadcrumb">
        <li><a href="{{ route('hr.dashboard') }}">Dashboard</a></li>
        <li>/</li>
        <li><a href="{{ route('hr-admins.index') }}">HR Admins</a></li>
        <li>/</li>
        <li aria-current="page">Edit</li>
    </ul>

    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1>Edit {{ $hrAdmin->username }}</h1>
            <p>
                @if($isSelf)
                    Update your own profile or change your password.
                @else
                    Update profile details. Only the account owner can change the password.
                @endif
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="ui-message ui-message--success">
            <i class="fas fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="ui-message ui-message--error">
            <i class="fas fa-triangle-exclamation"></i> Please fix the errors below.
        </div>
    @endif

    <form method="POST" action="{{ route('hr-admins.update', $hrAdmin) }}">
        @csrf @method('PUT')

        <div class="ui-card ui-card--section" style="padding:22px;">
            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">

                {{-- HRID (read-only) --}}
                <div class="ui-field">
                    <label class="ui-label">HRID</label>
                    <input type="text" class="ui-input" value="{{ $hrAdmin->hrid }}" disabled>
                </div>

                {{-- Username --}}
                <div class="ui-field">
                    <label class="ui-label">Username *</label>
                    <input name="username" type="text" class="ui-input"
                           value="{{ old('username', $hrAdmin->username) }}"
                           required maxlength="50"
                           {{ $isSelf ? '' : '' }}>
                    @error('username') <div class="ui-field__error">{{ $message }}</div> @enderror
                </div>

                {{-- Email --}}
                <div class="ui-field">
                    <label class="ui-label">Email *</label>
                    <input name="email" type="email" class="ui-input"
                           value="{{ old('email', $hrAdmin->email) }}"
                           required maxlength="100">
                    @error('email') <div class="ui-field__error">{{ $message }}</div> @enderror
                </div>

                {{-- Phone number --}}
                <div class="ui-field">
                    <label class="ui-label">Phone Number</label>
                    <input name="phone_number" type="text" class="ui-input"
                           value="{{ old('phone_number', $hrAdmin->phone_number) }}"
                           maxlength="20">
                    @error('phone_number') <div class="ui-field__error">{{ $message }}</div> @enderror
                </div>

                {{-- Role --}}
                <div class="ui-field">
                    <label class="ui-label">Role</label>
                    <input name="role" type="text" class="ui-input"
                           value="{{ old('role', $hrAdmin->role) }}"
                           maxlength="20" placeholder="e.g. hr"
                           {{ $isSelf ? '' : 'disabled' }}>
                    @if(!$isSelf)
                        {{-- Keep the value submitted even when input is disabled --}}
                        <input type="hidden" name="role" value="{{ $hrAdmin->role }}">
                    @endif
                    @error('role') <div class="ui-field__error">{{ $message }}</div> @enderror
                </div>

                {{-- Created (read-only) --}}
                <div class="ui-field">
                    <label class="ui-label">Created</label>
                    <input type="text" class="ui-input"
                           value="{{ $hrAdmin->created_at?->format('d M Y, H:i') ?? '—' }}"
                           disabled>
                </div>

                {{-- Last updated (read-only) --}}
                <div class="ui-field">
                    <label class="ui-label">Last Updated</label>
                    <input type="text" class="ui-input"
                           value="{{ $hrAdmin->updated_at?->format('d M Y, H:i') ?? '—' }}"
                           disabled>
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- PASSWORD SECTION — only visible when editing your OWN account --}}
        {{-- ============================================================ --}}
        @if($isSelf)
            <div class="ui-card ui-card--section" style="padding:22px;margin-top:18px;">
                <div style="margin-bottom:14px;">
                    <h2 style="margin:0 0 4px;font-size:16px;font-weight:850;color:#0f172a;">
                        <i class="fas fa-key" style="color:var(--app-primary);"></i>
                        Change Your Password
                    </h2>
                    <p style="margin:0;font-size:12.5px;color:#64748b;font-weight:600;">
                        Leave blank to keep your current password. Min 8 characters, letters &amp; numbers.
                    </p>
                </div>

                <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:18px;">
                    <div class="ui-field">
                        <label class="ui-label" for="password">New Password</label>
                        <input id="password" name="password" type="password"
                               class="ui-input" autocomplete="new-password">
                        @error('password') <div class="ui-field__error">{{ $message }}</div> @enderror
                    </div>

                    <div class="ui-field">
                        <label class="ui-label" for="password_confirmation">Confirm New Password</label>
                        <input id="password_confirmation" name="password_confirmation"
                               type="password" class="ui-input" autocomplete="new-password">
                    </div>
                </div>
            </div>
        @else
            {{-- Read-only notice when editing someone else --}}
            <div class="ui-card ui-card--section"
                 style="padding:16px 20px;margin-top:18px;border-left:4px solid #fbbf24;background:#fffbeb;border-radius:12px;">
                <div style="display:flex;align-items:center;gap:10px;color:#92400e;font-size:13px;font-weight:700;">
                    <i class="fas fa-lock" style="font-size:16px;"></i>
                    <span>Only <strong>{{ $hrAdmin->username }}</strong> can change this account's password.</span>
                </div>
            </div>
        @endif

        <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
            <a href="{{ route('hr-admins.index') }}" class="ui-button ui-button--compact">
                <i class="fas fa-xmark"></i> Cancel
            </a>
            <button type="submit" class="ui-button ui-button--primary">
                <i class="fas fa-floppy-disk"></i> Save Changes
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