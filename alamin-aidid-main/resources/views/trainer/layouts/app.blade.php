<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <title>@yield('title', 'Trainer Management System | Al Amin Edu Oasis')</title>

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap + Icons + Inter Font -->
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
    @include('components.app-signed-in-header-styles')
    @include('components.app-page-shell-styles')
    @include('components.app-toast-styles')
</head>

<body>

@include('trainer.partials.navbar')
@include('components.app-toasts')

@yield('content')

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@include('components.app-toast-scripts')

@stack('scripts')

</body>
</html>
