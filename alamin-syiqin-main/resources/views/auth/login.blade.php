<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login TEMS-ELIT</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body class="min-h-screen bg-slate-100 flex items-center justify-center p-6">

    <div class="w-full max-w-5xl bg-white rounded-[36px] shadow-2xl overflow-hidden grid grid-cols-1 md:grid-cols-2">

        {{-- Left Side --}}
        <div class="relative bg-gradient-to-br from-slate-900 via-violet-950 to-purple-900 text-white px-14 py-16 flex flex-col justify-center overflow-hidden">

            <div class="absolute right-0 top-0 translate-x-10 -translate-y-10 w-72 h-72 bg-purple-500/10 rounded-full blur-3xl"></div>

            <div class="relative z-10">

                <h1 class="text-4xl font-extrabold mb-8 leading-tight">
                    AL AMIN
                    <br>
                    EDU OASIS
                </h1>

                <h2 class="text-2xl font-semibold leading-relaxed text-violet-100">
                    EduOasis Learning &
                    <br>
                    Insight Tracking System
                    <br>
                    (ELIT)
                </h2>

            </div>

        </div>


        {{-- Right Side --}}
        <div class="px-14 py-14 flex flex-col justify-center">

            <h2 class="text-2xl font-bold text-slate-900 mb-8 text-center">
                USER LOGIN
            </h2>


            <form method="POST" action="{{ route('login') }}">
                @csrf


                <div class="mb-5">

                    <label
                        for="email"
                        class="block text-slate-700 font-semibold mb-2">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                        class="w-full h-12 rounded-xl
                               border border-slate-300
                               px-4 text-slate-700
                               focus:outline-none
                               focus:border-violet-700
                               focus:ring-2
                               focus:ring-violet-200
                               transition duration-200">

                </div>


                <div class="mb-7">

                    <label
                        for="password"
                        class="block text-slate-700 font-semibold mb-2">
                        Password
                    </label>


                    <div class="relative">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                            class="w-full h-12 rounded-xl
                                   border border-slate-300
                                   px-4 pr-12 text-slate-700
                                   focus:outline-none
                                   focus:border-violet-700
                                   focus:ring-2
                                   focus:ring-violet-200
                                   transition duration-200">


                        <button
                            type="button"
                            onclick="togglePassword()"
                            class="absolute inset-y-0 right-4 flex items-center text-slate-400">

                            {{-- Open Eye --}}
                            <svg
                                id="eyeOpen"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5
                                       c4.477 0 8.268 2.943 9.542 7
                                       -1.274 4.057-5.065 7-9.542 7
                                       -4.477 0-8.268-2.943-9.542-7z" />

                            </svg>


                            <svg
                                id="eyeClosed"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 hidden"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M3 3l18 18
                                       M10.6 10.6a2 2 0 002.8 2.8
                                       M9.9 4.24A9.77 9.77 0 0112 4
                                       c4.477 0 8.268 2.943 9.542 8
                                       a11.05 11.05 0 01-2.1 3.8
                                       M6.6 6.6A11.08 11.08 0 002.458 12
                                       C3.732 16.057 7.523 19 12 19
                                       a9.77 9.77 0 004.1-.9" />

                            </svg>

                        </button>

                    </div>

                </div>


                <button
                    type="submit"
                    class="w-full h-12
                           bg-gradient-to-r
                           from-slate-900 via-violet-950 to-purple-900
                           hover:from-slate-800 hover:via-violet-900 hover:to-purple-800
                           text-white font-bold rounded-xl
                           shadow-md hover:shadow-lg
                           transition duration-300">
                    LOGIN
                </button>

            </form>

        </div>

    </div>



    <script>
        function togglePassword() {

            const password = document.getElementById('password');
            const eyeOpen = document.getElementById('eyeOpen');
            const eyeClosed = document.getElementById('eyeClosed');

            password.type =
                password.type === 'password' ?
                'text' :
                'password';

            eyeOpen.classList.toggle('hidden');
            eyeClosed.classList.toggle('hidden');
        }
    </script>



    @if(session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: "{{ session('success') }}",
            showConfirmButton: false,
            timer: 1500
        });
    </script>
    @endif


    @if($errors->any())
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            html: `
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                `,
            confirmButtonColor: '#4c1d95'
        });
    </script>
    @endif

</body>

</html>