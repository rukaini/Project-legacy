<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>@yield('title', 'Admin | Al Amin Edu Oasis')</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap + Icons + Inter Font (same as trainer side) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: "Inter", sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
    </style>

    @stack('styles')

    {{-- =========================================================
         SHARED ADMIN DESIGN TOKENS — appended AFTER @stack('styles')
         so it always wins over any per-page styles, locking the
         page rhythm identical across every admin page.
         ========================================================= --}}
    <style>
        :root {
            --adm-primary: #2563eb; --adm-primary-dark: #1d4ed8; --adm-primary-soft: #eff6ff;
            --adm-indigo: #4338ca;  --adm-indigo-soft: #eef2ff;
            --adm-green: #059669;   --adm-green-soft: #ecfdf5;
            --adm-amber: #d97706;   --adm-amber-soft: #fffbeb;
            --adm-red: #dc2626;     --adm-red-soft: #fef2f2;
            --adm-slate-900: #0f172a; --adm-slate-700: #334155; --adm-slate-600: #475569;
            --adm-slate-500: #64748b; --adm-slate-400: #94a3b8;
            --adm-border: #e2e8f0; --adm-shadow: 0 6px 20px rgba(15, 23, 42, 0.05);
        }

        body {
            background: #f6f8fb !important;
            font-family: "Inter", sans-serif !important;
            color: var(--adm-slate-900);
        }

        /* HEADER — identical rhythm on every admin page */
        main.adm-wrap .adm-head { margin-bottom: 18px !important; }
        main.adm-wrap .adm-head-row {
            display: flex !important;
            align-items: center !important;
            gap: 14px !important;
        }
        main.adm-wrap .adm-head-icon {
            width: 52px !important;
            height: 52px !important;
            border-radius: 15px !important;
            background: linear-gradient(135deg, #eff6ff, #e0e7ff) !important;
            border: 1px solid #c7d2fe !important;
            color: var(--adm-primary) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 23px !important;
            flex-shrink: 0 !important;
        }
        main.adm-wrap .adm-title {
            font-size: 24px !important;
            font-weight: 800 !important;
            letter-spacing: -0.03em !important;
            margin: 0 !important;
            line-height: 1.2 !important;
            color: var(--adm-slate-900) !important;
        }
        main.adm-wrap .adm-subtitle {
            font-size: 13.5px !important;
            font-weight: 500 !important;
            color: var(--adm-slate-500) !important;
            margin: 2px 0 0 !important;
            line-height: 1.45 !important;
            max-width: 760px;
        }

        @media (max-width: 768px) {
            main.adm-wrap .adm-title { font-size: 20px !important; }
        }
    </style>

    @include('admin.partials.system-styles')
    @include('components.app-signed-in-header-styles')
    @include('components.app-page-shell-styles')
    @include('components.app-toast-styles')
</head>

<body>

@include('admin.partials.navbar')
@include('components.app-toasts')

@yield('content')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@include('components.app-toast-scripts')

@stack('scripts')

</body>
</html>
