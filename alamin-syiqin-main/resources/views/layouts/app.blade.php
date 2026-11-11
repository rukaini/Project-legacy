<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'TEMS') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">

    <link
        href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800,900&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>

<body class="antialiased">

    <div class="min-h-screen">

        @if(Auth::guard('admin')->check())

        @include('layouts.admin-nav')

        @elseif(Auth::guard('hr')->check())

        @include('layouts.hr-nav')

        @elseif(Auth::guard('new_teacher')->check())

        @include('layouts.gn-nav')

        @elseif(Auth::guard('principal')->check())

        @include('layouts.principal-nav')

        @elseif(
        Auth::guard('teacher')->check()
        && session('teacher_role') === 'observer'
        )

        @include('layouts.ob-nav')

        @elseif(
        Auth::guard('teacher')->check()
        && session('teacher_role') === 'external_observer'
        )

        @include('layouts.ext-nav')

        @endif


        @isset($header)

        <div class="app-page-shell pb-0">

            {{ $header }}

        </div>

        @endisset


        <main>

            {{ $slot }}

        </main>

    </div>


    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    @if(session('success'))

    <script>
        Swal.fire({
            icon: 'success',
            text: "{{ session('success') }}",
            confirmButtonColor: '#2563eb'
        });
    </script>

    @endif


    @if(session('error'))

    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: "{{ session('error') }}",
            confirmButtonColor: '#dc2626'
        });
    </script>

    @endif


    @if($errors->any())

    <script>
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            html: `
                @foreach($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            `,
            confirmButtonColor: '#dc2626'
        });
    </script>

    @endif

</body>

</html>