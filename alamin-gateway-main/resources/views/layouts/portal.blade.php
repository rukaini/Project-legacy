<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ELIT Portal')</title>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}?v={{ filemtime(public_path('css/portal.css')) }}">
</head>
<body class="@yield('body-class')">
    <a class="skip-link" href="#main">Skip to content</a>
    <div class="portal-shell">
        <header class="portal-header">
            <div class="header-inner">
                <a href="{{ session()->has('gateway.identity') ? route('systems.index') : route('login') }}" class="brand" aria-label="ELIT Portal home">
                    <img src="{{ asset('images/al-amin-edu-oasis-logo.jpg') }}?v=7a75badc" alt="Al Amin Edu Oasis logo">
                    <span><strong>ELIT Portal</strong><small>EduOasis Learning &amp; Insight Tracking</small></span>
                </a>
                @if(session()->has('gateway.identity'))
                    <div class="header-account" data-account-menu>
                        @php
                            $identity = session('gateway.identity', []);
                            $displayName = trim((string) ($identity['name'] ?? 'User')) ?: 'User';
                            $nameParts = preg_split('/\s+/u', $displayName, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                            $initials = count($nameParts) > 1
                                ? mb_substr($nameParts[0], 0, 1).mb_substr($nameParts[array_key_last($nameParts)], 0, 1)
                                : mb_substr($nameParts[0] ?? 'U', 0, 2);
                            $initials = mb_strtoupper(mb_substr($initials, 0, 2));
                            $selectedRole = $identity['selected_role'] ?? null;
                            $selectedRoleLabel = is_string($selectedRole)
                                ? data_get(config('sso.roles', []), $selectedRole.'.label')
                                : null;
                            $isAdministrator = (bool) ($identity['is_administrator'] ?? false);
                        @endphp
                        <button
                            class="account-menu-trigger"
                            type="button"
                            aria-expanded="false"
                            aria-haspopup="menu"
                            aria-controls="account-menu-dropdown"
                            data-account-menu-trigger
                        >
                            <span class="header-avatar" aria-hidden="true">{{ $initials }}</span>
                            <span class="header-name">{{ $displayName }}</span>
                            <svg class="account-menu-chevron" aria-hidden="true" viewBox="0 0 12 8" width="12" height="8">
                                <path d="M1 1.25 6 6.25l5-5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" />
                            </svg>
                        </button>
                        <div
                            class="account-menu-dropdown"
                            id="account-menu-dropdown"
                            role="menu"
                            aria-label="Account options"
                            data-account-menu-dropdown
                            hidden
                        >
                            <div class="account-menu-summary" role="none">
                                <strong>{{ $displayName }}</strong>
                                <div class="account-menu-meta">
                                    @if($isAdministrator)
                                        <span>Administrator</span>
                                    @endif
                                    @if($selectedRoleLabel)
                                        <span>{{ $isAdministrator ? 'Role: ' : '' }}{{ $selectedRoleLabel }}</span>
                                    @endif
                                </div>
                            </div>
                            <div class="account-menu-divider" role="separator"></div>
                            <a class="account-menu-item" href="{{ route('password.change') }}" role="menuitem">Change Password</a>
                            <form method="POST" action="{{ route('logout') }}" data-loading>
                                @csrf
                                <button class="account-menu-item" type="submit" role="menuitem" data-loading-text="Logging out...">Logout</button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </header>
        <main id="main" class="portal-main">@yield('content')</main>
    </div>
    <script>
        document.querySelectorAll('[data-password-toggle]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.passwordToggle);
                const visible = input.type === 'text';
                input.type = visible ? 'password' : 'text';
                button.textContent = visible ? 'Show' : 'Hide';
                button.setAttribute('aria-label', visible ? 'Show password' : 'Hide password');
            });
        });
        document.querySelectorAll('form[data-loading]').forEach((form) => {
            form.addEventListener('submit', () => {
                const button = form.querySelector('button[type="submit"]');
                if (!button || button.disabled) return;
                button.disabled = true;
                button.dataset.originalText = button.textContent;
                button.textContent = button.dataset.loadingText || 'Please wait...';
                button.setAttribute('aria-busy', 'true');
            });
        });
        document.querySelectorAll('[data-role-select]').forEach((select) => {
            select.addEventListener('change', () => {
                if (select.value) select.form.requestSubmit();
            });
        });
        document.querySelectorAll('[data-account-menu]').forEach((menu) => {
            const trigger = menu.querySelector('[data-account-menu-trigger]');
            const dropdown = menu.querySelector('[data-account-menu-dropdown]');
            const items = [...dropdown.querySelectorAll('[role="menuitem"]')];

            const setOpen = (open, focusFirst = false) => {
                trigger.setAttribute('aria-expanded', String(open));
                dropdown.hidden = ! open;
                if (open && focusFirst) items[0]?.focus();
            };

            trigger.addEventListener('click', () => {
                setOpen(trigger.getAttribute('aria-expanded') !== 'true');
            });
            trigger.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    setOpen(true, true);
                } else if (event.key === 'Escape') {
                    setOpen(false);
                }
            });
            dropdown.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    setOpen(false);
                    trigger.focus();
                    return;
                }

                if (! ['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
                event.preventDefault();
                const current = Math.max(0, items.indexOf(document.activeElement));
                const next = event.key === 'Home'
                    ? 0
                    : (event.key === 'End'
                        ? items.length - 1
                        : (current + (event.key === 'ArrowDown' ? 1 : -1) + items.length) % items.length);
                items[next]?.focus();
            });
            document.addEventListener('click', (event) => {
                if (! menu.contains(event.target)) setOpen(false);
            });
        });
        document.querySelectorAll('[data-copy-target]').forEach((button) => {
            button.addEventListener('click', async () => {
                const target = document.getElementById(button.dataset.copyTarget);
                const status = document.querySelector('[data-copy-status]');
                if (!target) return;

                try {
                    await navigator.clipboard.writeText(target.textContent.trim());
                    button.textContent = 'Copied';
                    if (status) status.textContent = 'Password copied.';
                } catch (error) {
                    if (status) status.textContent = 'Could not copy automatically. Please select and copy the password.';
                }
            });
        });
        document.querySelectorAll('[data-registration-password]').forEach((password) => {
            const form = password.form;
            const confirmation = form.querySelector('[data-password-confirmation]');
            const passwordGuidance = form.querySelector('[data-password-guidance]');
            const confirmationGuidance = form.querySelector('[data-confirmation-guidance]');

            const updateGuidance = () => {
                const passwordIsLongEnough = password.value.length >= 8;
                const passwordMessage = passwordIsLongEnough
                    ? 'Password length requirement met.'
                    : 'Use at least 8 characters.';
                passwordGuidance.classList.toggle('is-valid', passwordIsLongEnough);
                if (passwordGuidance.textContent !== passwordMessage) {
                    passwordGuidance.textContent = passwordMessage;
                }

                const confirmationHasValue = confirmation.value.length > 0;
                const passwordsMatch = confirmationHasValue && password.value === confirmation.value;
                const confirmationMessage = ! confirmationHasValue
                    ? 'Re-enter your password to confirm it.'
                    : (passwordsMatch ? 'Passwords match.' : 'Passwords do not match yet.');
                confirmationGuidance.classList.toggle('is-valid', passwordsMatch);
                confirmationGuidance.classList.toggle('is-invalid', confirmationHasValue && ! passwordsMatch);
                if (confirmationGuidance.textContent !== confirmationMessage) {
                    confirmationGuidance.textContent = confirmationMessage;
                }
                confirmation.setAttribute('aria-invalid', confirmationHasValue && ! passwordsMatch ? 'true' : 'false');
            };

            password.addEventListener('input', updateGuidance);
            confirmation.addEventListener('input', updateGuidance);
        });
    </script>
</body>
</html>
