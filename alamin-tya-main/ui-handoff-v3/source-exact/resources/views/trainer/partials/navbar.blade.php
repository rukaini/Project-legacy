@php
    $isDashboard = request()->routeIs('trainer.dashboard');

    $isTraining =
        request()->routeIs('trainer.courses') ||
        request()->routeIs('trainer.courses.*');

    $isAnalytics =
        request()->routeIs('trainer.analytics') ||
        request()->routeIs('trainer.analytics.*');

    $isFeedback =
        request()->routeIs('trainer.feedback') ||
        request()->routeIs('trainer.feedback.*');

    $isProposal =
        request()->routeIs('trainer.proposals') ||
        request()->routeIs('trainer.proposals.*') ||
        request()->is('trainer/proposals*');

    $isProfile =
        request()->routeIs('trainer.profile') ||
        request()->is('trainer/profile*');

    $userName = session('user_name') ?: 'Trainer';

    $trainerNavigationItems = [
        ['label' => 'Dashboard', 'url' => route('trainer.dashboard'), 'active' => $isDashboard],
        ['label' => 'My Training', 'url' => route('trainer.courses'), 'active' => $isTraining],
        ['label' => 'Analytics', 'url' => route('trainer.analytics'), 'active' => $isAnalytics],
        ['label' => 'Feedback', 'url' => route('trainer.feedback'), 'active' => $isFeedback],
        ['label' => 'Proposal', 'url' => url('/trainer/proposals'), 'active' => $isProposal],
    ];
@endphp

<header class="topbar app-header">
    <div class="trainer-topbar-shell app-header-shell container-fluid">
        <div class="trainer-topbar-primary-row app-header-primary-row d-flex align-items-center">
            <a href="{{ route('trainer.dashboard') }}"
               class="app-header-brand"
               aria-label="Al Amin Edu Oasis trainer dashboard">
                <img src="{{ asset('images/branding/ai-amin-edu-oasis-logo.png') }}"
                     class="app-header-logo"
                     width="447"
                     height="447"
                     alt="Al Amin Edu Oasis">
            </a>

            <nav class="app-header-nav d-none d-lg-flex"
                 aria-label="Trainer primary navigation"
                 data-navigation-surface="desktop">
                @foreach ($trainerNavigationItems as $navigationItem)
                    <a href="{{ $navigationItem['url'] }}"
                       class="app-header-nav-link nav-link-top {{ $navigationItem['active'] ? 'active' : '' }}"
                       @if ($navigationItem['active']) aria-current="page" @endif>
                        {{ $navigationItem['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="trainer-account-actions app-header-account-actions">
                <button type="button"
                        class="app-header-mobile-toggle mobile-nav-toggle d-lg-none"
                        data-bs-toggle="collapse"
                        data-bs-target="#trainerMobileNavigation"
                        aria-controls="trainerMobileNavigation"
                        aria-expanded="false"
                        aria-label="Toggle trainer navigation">
                    <i class="bi bi-list" aria-hidden="true"></i>
                    <span>Menu</span>
                </button>

                <div class="app-header-account-divider">
                    <a href="{{ url('/trainer/profile') }}"
                       class="trainer-profile-link app-header-profile-link {{ $isProfile ? 'profile-link-active' : '' }}"
                       title="View profile"
                       aria-label="View trainer profile"
                       @if ($isProfile) aria-current="page" @endif>
                        <div class="app-header-account-copy text-end d-none d-sm-block">
                            <div class="app-header-account-name">
                                {{ $userName }}
                            </div>

                            <div class="app-header-account-role">
                                Trainer
                            </div>
                        </div>

                        <div class="app-header-avatar profile-avatar">
                            {{ strtoupper(substr($userName, 0, 1)) }}
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

        <nav id="trainerMobileNavigation"
             class="app-header-mobile-navigation trainer-mobile-navigation collapse d-lg-none"
             aria-label="Trainer mobile navigation"
             data-navigation-surface="mobile">
            @foreach ($trainerNavigationItems as $navigationItem)
                <a href="{{ $navigationItem['url'] }}"
                   class="app-header-mobile-link trainer-mobile-link {{ $navigationItem['active'] ? 'active' : '' }}"
                   @if ($navigationItem['active']) aria-current="page" @endif>
                    {{ $navigationItem['label'] }}
                </a>
            @endforeach
        </nav>
    </div>
</header>
