@extends('layouts.app')

@section('title', 'HR Administrators')

@section('content')
<div class="ui-page-shell">

    <ul class="ui-breadcrumb">
        <li><a href="{{ route('hr.dashboard') }}">Dashboard</a></li>
        <li>/</li>
        <li aria-current="page">HR Administrators</li>
    </ul>

    <div class="ui-page-heading">
        <div class="ui-page-heading__copy">
            <h1>HR Administrators</h1>
            <p>Manage accounts with HR-level access to the system.</p>
        </div>
        <div class="ui-page-heading__actions">
            <a href="{{ route('hr-admins.create') }}" class="ui-button ui-button--primary">
                <i class="fas fa-user-plus"></i> Register HR Admin
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="ui-message ui-message--success">
            <i class="fas fa-circle-check"></i> {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="ui-message ui-message--error">
            <i class="fas fa-triangle-exclamation"></i> {{ session('error') }}
        </div>
    @endif

    <div class="ui-card ui-card--section">

        {{-- Filters --}}
        <form method="GET" action="{{ route('hr-admins.index') }}" class="ui-filters">
            <div class="ui-search">
                <i class="fas fa-search ui-search__icon"></i>
                <input type="text" name="search" class="ui-input"
                       placeholder="Search username, email, or HRID…"
                       value="{{ request('search') }}">
            </div>
            <div class="ui-field">
                <button type="submit" class="ui-button ui-button--primary">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </form>

        @if($hrAdmins->count())
            <div class="ui-table-wrap">
                <table class="ui-table ui-table--dashboard">
                    <thead>
                        <tr>
                            <th>HRID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Password</th>
                            <th>Registered</th>
                            <th style="text-align:right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($hrAdmins as $admin)
                            <tr>
                                <td><strong>{{ $admin->hrid }}</strong></td>
                                <td>{{ $admin->username }}</td>
                                <td>{{ $admin->email }}</td>
                                <td>{{ $admin->phone_number ?? '—' }}</td>
                                <td>
                                    <span class="ui-badge ui-badge--info">
                                        {{ strtoupper($admin->role ?? 'hr') }}
                                    </span>
                                </td>
                                <td>
                                    @if($admin->password_change_required)
                                        <span class="ui-badge ui-badge--warning">Change required</span>
                                    @else
                                        <span class="ui-badge ui-badge--success">OK</span>
                                    @endif
                                </td>
                                <td>
                                    {{-- Null-safe: renders "—" if created_at is null --}}
                                    {{ $admin->created_at?->format('d M Y') ?? '—' }}
                                </td>
                                <td style="text-align:right;white-space:nowrap;">
                                    <a href="{{ route('hr-admins.edit', $admin) }}"
                                       class="ui-button ui-button--outline" title="Edit">
                                        <i class="fas fa-pen"></i>
                                    </a>

                                    <form method="POST"
                                          action="{{ route('hr-admins.destroy', $admin) }}"
                                          style="display:inline;"
                                          onsubmit="return confirm('Delete this HR administrator?');">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="ui-button ui-button--compact"
                                                style="color:#dc2626;border-color:#fecdd3;"
                                                title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ui-pagination">
                <div class="ui-pagination__summary">
                    Showing {{ $hrAdmins->firstItem() }}–{{ $hrAdmins->lastItem() }}
                    of {{ $hrAdmins->total() }}
                </div>
                <div class="ui-pagination__controls">
                    {{ $hrAdmins->links() }}
                </div>
            </div>
        @else
            <div class="ui-state">
                <i class="fas fa-user-tie"
                   style="font-size:32px;color:#cbd5e1;margin-bottom:12px;"></i>
                <div class="ui-state__title">No HR administrators found</div>
                <div class="ui-state__copy">Register your first HR administrator to get started.</div>
            </div>
        @endif
    </div>
</div>
@endsection