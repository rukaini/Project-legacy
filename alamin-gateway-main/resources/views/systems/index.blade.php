@extends('layouts.portal')

@section('title', 'Systems | ELIT Portal')
@section('body-class', 'systems-body')

@section('content')
<section class="selection-page">
    @if($errors->any())<div class="notice notice-error" role="alert">{{ $errors->first() }}</div>@endif

    <div class="page-intro">
        <div class="intro-heading-row">
            <h1>Welcome, {{ $identity['name'] }}</h1>
            @if($identity['is_administrator'] ?? false)
                <div class="account-badge"><span>Account:</span><strong>Administrator</strong></div>
            @endif
            @if($role)
                <div class="role-badge"><span>Role:</span><strong>{{ $role['label'] }}</strong></div>
            @endif
        </div>
        <p>Choose an available system to continue.</p>
    </div>

    @if(count($roles) > 1)
        <section class="role-section" aria-labelledby="role-heading">
            <h2 id="role-heading">Select Role</h2>
            <p>Available systems are based on your selected role.</p>
            <form class="role-selector" method="POST" action="{{ route('roles.select') }}">
                @csrf
                <label class="sr-only" for="role">Select Role</label>
                <select id="role" name="role" data-role-select required>
                    <option value="" disabled @selected(! $roleKey)>Select a role</option>
                    @foreach($roles as $key => $availableRole)
                        <option value="{{ $key }}" @selected($roleKey === $key)>{{ $availableRole['label'] }}</option>
                    @endforeach
                </select>
                <noscript><button class="secondary-button" type="submit">Apply role</button></noscript>
            </form>
        </section>
    @endif

    @if($role)
        <section class="systems-section" aria-labelledby="systems-heading">
            <h2 id="systems-heading">Available Systems</h2>
            @if($systems)
                <div class="system-grid {{ count($systems) === 1 ? 'is-single' : '' }}">
                    @foreach($systems as $key => $system)
                        <article class="system-card accent-{{ $system['accent'] }}">
                            <div class="system-copy">
                                <h3>{{ $system['display_name'] }}</h3>
                                <p>{{ $system['description'] }}</p>
                            </div>
                            <form method="POST" action="{{ route('systems.launch', $key) }}" data-loading>
                                @csrf
                                <button class="open-button" type="submit" data-loading-text="Opening...">Open</button>
                            </form>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="empty-state"><strong>No systems are currently available for this role.</strong><p>Please contact your administrator if you believe this is incorrect.</p></div>
            @endif
        </section>
    @else
        <div class="role-prompt" role="status">Select a role to view your available systems.</div>
    @endif
</section>
@endsection
