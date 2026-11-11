@php
    $adminName = session('user_name') ?: 'Admin';
    $isAdminProfile = request()->routeIs('admin.profile')
        || request()->routeIs('admin.profile.*');

    $adminNavigationItems = [
        [
            'label' => 'Dashboard',
            'url' => route('admin.dashboard'),
            'active' => request()->routeIs('admin.dashboard'),
        ],
        [
            'label' => 'Proposals',
            'url' => route('admin.proposals'),
            'active' => request()->routeIs('admin.proposals')
                || request()->routeIs('admin.proposals.*'),
        ],
        [
            'label' => 'Activity',
            'url' => route('admin.activity'),
            'active' => request()->routeIs('admin.activity'),
        ],
        [
            'label' => 'Trainings',
            'url' => route('admin.courses'),
            'active' => request()->routeIs('admin.courses'),
        ],
        [
            'label' => 'Directory',
            'url' => route('admin.directory'),
            'active' => request()->routeIs('admin.directory'),
        ],
        [
            'label' => 'Analytics',
            'url' => route('admin.analytics'),
            'active' => request()->routeIs('admin.analytics'),
        ],
    ];
@endphp

<header class="topbar app-header">
    <div class="admin-topbar-shell app-header-shell container-fluid">
        <div class="admin-topbar-primary-row app-header-primary-row d-flex align-items-center">
            <a href="{{ route('admin.dashboard') }}"
               class="app-header-brand"
               aria-label="Al Amin Edu Oasis administrator dashboard">
                <img src="{{ asset('images/branding/ai-amin-edu-oasis-logo.png') }}"
                     class="app-header-logo"
                     width="447"
                     height="447"
                     alt="Al Amin Edu Oasis">
            </a>

            <nav class="app-header-nav d-none d-lg-flex"
                 aria-label="Administrator primary navigation"
                 data-navigation-surface="desktop">
                @foreach ($adminNavigationItems as $navigationItem)
                    <a href="{{ $navigationItem['url'] }}"
                       class="app-header-nav-link nav-link-top {{ $navigationItem['active'] ? 'active' : '' }}"
                       @if ($navigationItem['active']) aria-current="page" @endif>
                        {{ $navigationItem['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="admin-account-actions app-header-account-actions">
                <button type="button"
                        class="app-header-mobile-toggle admin-mobile-nav-toggle d-lg-none"
                        data-bs-toggle="collapse"
                        data-bs-target="#adminMobileNavigation"
                        aria-controls="adminMobileNavigation"
                        aria-expanded="false"
                        aria-label="Toggle administrator navigation">
                    <i class="bi bi-list" aria-hidden="true"></i>
                    <span>Menu</span>
                </button>

                <div class="app-header-account-divider">
                    <a href="{{ route('admin.profile') }}"
                       class="admin-profile-link app-header-profile-link {{ $isAdminProfile ? 'profile-link-active' : '' }}"
                       title="My profile"
                       aria-label="View administrator profile"
                       @if ($isAdminProfile) aria-current="page" @endif>
                        <div class="app-header-account-copy text-end d-none d-sm-block">
                            <div class="app-header-account-name">
                                {{ $adminName }}
                            </div>
                            <div class="app-header-account-role">
                                Administrator
                            </div>
                        </div>

                        <div class="app-header-avatar profile-avatar">
                            {{ strtoupper(substr($adminName, 0, 1)) }}
                        </div>
                    </a>

                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit"
                                class="app-header-logout logout-icon"
                                title="Logout"
                                aria-label="Logout">
                            <i class="bi bi-box-arrow-right fs-5" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <nav id="adminMobileNavigation"
             class="app-header-mobile-navigation admin-mobile-navigation collapse d-lg-none"
             aria-label="Administrator mobile navigation"
             data-navigation-surface="mobile">
            @foreach ($adminNavigationItems as $navigationItem)
                <a href="{{ $navigationItem['url'] }}"
                   class="app-header-mobile-link admin-mobile-link {{ $navigationItem['active'] ? 'active' : '' }}"
                   @if ($navigationItem['active']) aria-current="page" @endif>
                    {{ $navigationItem['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</header>
