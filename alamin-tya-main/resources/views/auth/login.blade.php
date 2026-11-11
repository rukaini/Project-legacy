<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Al Amin HRMS | Secure Login</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800;14..32,850;14..32,900&display=swap" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ================================================ -->
    <!-- EDU DESIGN SYSTEM - White & Blue Theme          -->
    <!-- ================================================ -->
    <style>
        /* ===========================================================================
           Design system — White & Blue Theme
           =========================================================================== */

        /* ---------- foundation/tokens.css ---------- */
        :root {
          --app-font-family: "Inter", sans-serif;
          --app-shell-max-width: 1880px;
          --app-page-top-space: clamp(22px, 2.2vw, 36px);
          --app-page-gutter: clamp(16px, 2.4vw, 44px);
          --app-page-bottom-space: clamp(44px, 4vw, 68px);
          --app-header-height: 58px;
          --app-header-gutter: clamp(16px, 2.25vw, 42px);
          --app-header-nav-padding: clamp(11px, .95vw, 16px);
          --app-logo-height: 48px;
          --app-logo-max-width: 52px;
          --app-navigator-width: 360px;
          --app-navigator-width-intermediate: 320px;
          --app-navigator-width-compact: 44px;
          --app-background: #f8fafc;
          --app-surface: #fff;
          --app-text: #0f172a;
          --app-text-secondary: #475569;
          --app-text-muted: #64748b;
          --app-text-subtle: #94a3b8;
          --app-border: #e2e8f0;
          --app-border-strong: #cbd5e1;
          --app-divider: #f1f5f9;
          --app-primary: #2563eb;
          --app-primary-dark: #1d4ed8;
          --app-primary-soft: #eff6ff;
          --app-primary-border: #bfdbfe;
          --app-success: #059669;
          --app-success-soft: #ecfdf5;
          --app-warning: #d97706;
          --app-warning-soft: #fffbeb;
          --app-danger: #dc2626;
          --app-danger-soft: #fff1f2;
          --app-focus-ring: rgba(37, 99, 235, .3);
          --app-input-focus-ring: rgba(37, 99, 235, 0.09);
          --app-disabled-opacity: .45;
          --app-radius-control: 10px;
          --app-radius-compact: 9px;
          --app-radius-toast: 11px;
          --app-radius-card: 16px;
          --app-radius-panel: 18px;
          --app-radius-pill: 999px;
          --app-shadow-header: 0 1px 10px rgba(15, 23, 42, 0.04);
          --app-shadow-card: 0 6px 20px rgba(15, 23, 42, 0.05);
          --app-shadow-toast: 0 12px 30px rgba(15, 23, 42, 0.16);
          --app-motion-duration: .18s;
          --app-motion-easing: ease;
          --app-motion-reduced: .01ms;
          --app-touch-target: 44px;
          --app-focus-width: 3px;
          --app-focus-offset: 2px;
        }

        @media (max-width: 767.98px) {
          :root {
            --app-page-top-space: 20px;
            --app-page-gutter: 16px;
            --app-page-bottom-space: 44px;
          }
        }

        @media (max-width: 575.98px) {
          :root {
            --app-page-gutter: 12px;
            --app-logo-height: 44px;
          }
        }

        @media (prefers-reduced-motion: reduce) {
          :root { --app-motion-duration: .01ms; }
        }

        /* ---------- components/cards/cards.css ---------- */
        .ui-card{min-width:0;border:1px solid #e2e8f0;background:#fff}
        .ui-card--section{border-radius:18px;box-shadow:0 6px 20px rgba(15,23,42,0.05);overflow:hidden}
        .ui-card__header{padding:16px 20px;border-bottom:1px solid #f1f5f9;display:flex;gap:12px;justify-content:space-between;flex-wrap:wrap}
        .ui-card__body{padding:20px}

        /* ---------- components/forms/forms.css ---------- */
        .ui-field{display:grid;min-width:0;gap:6px}
        .ui-label{color:#475569;font-size:11px;font-weight:850;letter-spacing:.06em;text-transform:uppercase}
        .ui-input{box-sizing:border-box;width:100%;min-width:0;padding:9px 12px;border:1px solid #e2e8f0;border-radius:10px;background:#fff;color:#475569;font-family:"Inter",sans-serif;font-size:13.5px;font-weight:600}
        .ui-input:focus{outline:0;border-color:#bfdbfe;box-shadow:0 0 0 3px rgba(37,99,235,.09)}
        .ui-field__helper{color:#64748b;font-size:11px;font-weight:600;line-height:1.5}
        .ui-field__error{margin-top:5px;color:#dc2626;font-size:11px;font-weight:800}

        /* ---------- components/buttons/buttons.css ---------- */
        .ui-button{border:1px solid transparent;display:inline-flex;align-items:center;justify-content:center;gap:6px;font-family:"Inter",sans-serif;text-decoration:none;cursor:pointer}
        .ui-button--primary{min-height:40px;padding:11px 17px;border-radius:11px;background:#2563eb;color:#fff;font-size:13px;font-weight:850;box-shadow:0 6px 16px rgba(37,99,235,0.18)}
        .ui-button--primary:hover:not(:disabled){background:#1d4ed8}
        .ui-button--outline{min-height:36px;padding:7px 13px;border-color:#bfdbfe;border-radius:9px;background:#fff;color:#2563eb;font-size:12.5px;font-weight:850}
        .ui-button--outline:hover:not(:disabled){background:#eff6ff;color:#1d4ed8}
        .ui-button:focus-visible{outline:3px solid rgba(37,99,235,.3);outline-offset:2px}

        /* ---------- components/badges-status/badges-status.css ---------- */
        .ui-badge{display:inline-flex;align-items:center;padding:3px 9px;border:1px solid;border-radius:999px;font-size:11px;font-weight:800;white-space:nowrap}

        /* ---------- components/alerts-messages/alerts-messages.css ---------- */
        .ui-message{display:flex;align-items:center;gap:9px;margin-bottom:16px;padding:11px 13px;border:1px solid;border-radius:11px;font-size:12.5px;font-weight:750;overflow-wrap:anywhere}
        .ui-message--error{border-color:#fecaca;background:#fff1f2;color:#b91c1c}

        /* ---------- Login Specific — White & Blue ---------- */
        body {
            font-family: var(--app-font-family);
            background: #ffffff;
            background: linear-gradient(145deg, #f0f7ff 0%, #e6f0fa 100%);
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 16px;
            margin: 0;
        }

        .login-wrapper {
            width: 100%;
            max-width: 420px;
            margin: 0 auto;
        }

        .brand-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            border-radius: var(--app-radius-card);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.25);
            margin: 0 auto 12px;
            transition: transform 0.2s ease;
        }

        .brand-logo:hover {
            transform: scale(1.02);
        }

        .brand-logo i {
            font-size: 26px;
            color: #fff;
        }

        .login-card {
            background: #ffffff;
            border-radius: var(--app-radius-panel);
            border: 1px solid var(--app-border);
            box-shadow: 0 12px 32px rgba(0, 40, 100, 0.08);
            overflow: hidden;
            transition: box-shadow 0.2s ease;
        }

        .login-card:hover {
            box-shadow: 0 16px 40px rgba(0, 40, 100, 0.12);
        }

        .login-header {
            background: linear-gradient(135deg, #2563eb, #1e4fbd);
            padding: 28px 28px 24px;
            position: relative;
            overflow: hidden;
            text-align: center;
        }

        .login-header::before {
            content: "🔐";
            position: absolute;
            right: -12px;
            top: -12px;
            font-size: 100px;
            opacity: 0.08;
            pointer-events: none;
            transform: rotate(10deg);
        }

        .login-header::after {
            content: "";
            position: absolute;
            bottom: -20px;
            left: -20px;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .login-icon {
            width: 48px;
            height: 48px;
            background: rgba(255,255,255,0.18);
            border-radius: var(--app-radius-pill);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            backdrop-filter: blur(4px);
            border: 1px solid rgba(255,255,255,0.25);
        }

        .login-icon i {
            font-size: 20px;
            color: #fff;
        }

        .login-body {
            padding: 28px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
            pointer-events: none;
            transition: color 0.2s ease;
        }

        .input-wrapper:focus-within .input-icon {
            color: #2563eb;
        }

        .input-wrapper .ui-input {
            padding-left: 40px;
            background: #ffffff;
            border-color: #e2e8f0;
        }

        .input-wrapper .ui-input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 4px;
            transition: color 0.2s ease;
            font-size: 14px;
        }

        .toggle-password:hover {
            color: #2563eb;
        }

        .login-btn {
            background: linear-gradient(135deg, #2563eb, #1e4fbd);
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: var(--app-radius-control);
            font-weight: 700;
            font-size: 14px;
            width: 100%;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: var(--app-font-family);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 24px rgba(37, 99, 235, 0.35);
            background: linear-gradient(135deg, #1d4ed8, #1a43a8);
        }

        .login-btn:focus-visible {
            outline: 3px solid var(--app-focus-ring);
            outline-offset: 2px;
        }

        .login-footer {
            text-align: center;
            padding: 16px 0 0;
            font-size: 11px;
            color: var(--app-text-subtle);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            flex-wrap: wrap;
        }

        .login-footer .dot {
            width: 4px;
            height: 4px;
            border-radius: 50%;
            background: #94a3b8;
            display: inline-block;
        }

        /* Fade in animation */
        @keyframes fadeInUp {
            0% { opacity: 0; transform: translateY(16px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.5s cubic-bezier(0.2, 0.9, 0.4, 1.1) forwards;
        }

        /* Blue accents for labels and links */
        .accent-blue {
            color: #2563eb;
        }

        .ui-label i {
            color: #2563eb !important;
        }

        @media (max-width: 575.98px) {
            .login-body {
                padding: 20px;
            }
            .login-header {
                padding: 22px 20px;
            }
        }

        /* Ensure the checkbox uses blue accent */
        input[type="checkbox"] {
            accent-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="login-wrapper animate-fade-in-up">
        {{-- Brand Logo --}}
        <div style="text-align:center;margin-bottom:24px;">
            <div class="brand-logo">
                <i class="fas fa-building"></i>
            </div>
            <h1 style="font-size:20px;font-weight:800;color:#0f172a;margin:0;">
                Al Amin <span style="color:#2563eb;">HRMS</span>
            </h1>
            <p style="font-size:12px;font-weight:600;color:#94a3b8;margin:2px 0 0;letter-spacing:0.05em;text-transform:uppercase;">
                Human Resource Management System
            </p>
        </div>

        {{-- Login Card --}}
        <div class="login-card">
            {{-- Header --}}
            <div class="login-header">
                <div style="position:relative;z-index:1;">
                    <div class="login-icon">
                        <i class="fas fa-lock-open"></i>
                    </div>
                    <h2 style="color:#fff;font-size:20px;font-weight:700;margin:0;">Secure Access</h2>
                    <p style="color:rgba(255,255,255,0.85);font-size:12px;margin:4px 0 0;font-weight:500;">
                        Sign in with your corporate credentials
                    </p>
                </div>
            </div>

            {{-- Body --}}
            <div class="login-body">
                {{-- Error Message --}}
                @if(session('error'))
                    <div class="ui-message ui-message--error" style="margin-bottom:16px;padding:10px 14px;font-size:12px;">
                        <i class="fas fa-circle-exclamation"></i>
                        <div>{{ session('error') }}</div>
                    </div>
                @endif

                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf

                    {{-- Email Field --}}
                    <div class="ui-field" style="margin-bottom:16px;">
                        <label class="ui-label" style="display:flex;align-items:center;gap:6px;color:#1e293b;">
                            <i class="fas fa-envelope" style="color:#2563eb;font-size:12px;"></i> Email Address
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" name="email" class="ui-input" placeholder="admin@alamin.edu.my" value="{{ old('email') }}" required autofocus>
                        </div>
                    </div>

                    {{-- Password Field --}}
                    <div class="ui-field" style="margin-bottom:16px;">
                        <label class="ui-label" style="display:flex;align-items:center;gap:6px;color:#1e293b;">
                            <i class="fas fa-key" style="color:#2563eb;font-size:12px;"></i> Password
                        </label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="password" id="password" class="ui-input" placeholder="••••••••" required>
                            <button type="button" class="toggle-password" id="togglePassword" aria-label="Toggle password visibility">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Remember Me --}}
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
                        <label style="display:flex;align-items:center;gap:8px;font-size:13px;font-weight:600;color:#475569;cursor:pointer;">
                            <input type="checkbox" name="remember" style="accent-color:#2563eb;width:16px;height:16px;cursor:pointer;">
                            Keep me signed in
                        </label>
                    </div>

                    {{-- Submit Button --}}
                    <button type="submit" class="login-btn">
                        <i class="fas fa-arrow-right-to-bracket"></i>
                        Sign In
                    </button>
                </form>

                {{-- Divider --}}
                <div style="position:relative;margin:20px 0 12px;">
                    <div style="position:absolute;inset:0;display:flex;align-items:center;">
                        <div style="width:100%;border-top:1px solid #f1f5f9;"></div>
                    </div>
                </div>

                {{-- System Status --}}
                <div style="text-align:center;font-size:11px;color:#94a3b8;display:flex;align-items:center;justify-content:center;gap:8px;">
                    <i class="fas fa-shield-alt" style="color:#2563eb;"></i>
                    <span>Role-based Access</span>
                </div>
            </div>
        </div>

        {{-- Footer --}}
        <div class="login-footer">
            <span>© {{ date('Y') }} Al Amin Eduoasis Sdn Bhd</span>
            <span class="dot"></span>
            <span>HRMS v4.2.1</span>
            <span class="dot"></span>
            <span><i class="far fa-clock"></i> Secure Session</span>
        </div>
    </div>

    <!-- Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle password visibility
            const toggleBtn = document.getElementById('togglePassword');
            if (toggleBtn) {
                const passwordField = document.getElementById('password');
                toggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                    passwordField.setAttribute('type', type);
                    const icon = toggleBtn.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-eye');
                        icon.classList.toggle('fa-eye-slash');
                    }
                });
            }
        });
    </script>
</body>
</html>