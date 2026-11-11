<x-app-layout>

    <div class="app-page-shell">

        <div class="max-w-7xl mx-auto">

            {{-- Page heading --}}
            <div class="flex items-start gap-4 mb-7">

                <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200
                            flex items-center justify-center shrink-0">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.7"
                        stroke="currentColor"
                        class="w-6 h-6 text-blue-600">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15.75 6a3.75 3.75 0 11-7.5 0
                               3.75 3.75 0 017.5 0z
                               M4.501 20.118a7.5 7.5 0 0114.998 0
                               A17.933 17.933 0 0112 21.75
                               c-2.676 0-5.216-.584-7.499-1.632z" />

                    </svg>

                </div>

                <div>

                    <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                        My Profile
                    </h1>

                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        View your account information and manage your password.
                    </p>

                </div>

            </div>


            {{-- Admin Information --}}
            <div class="app-section-card mb-6">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Admin Information
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Your registered administrator account details.
                    </p>

                </div>


                <div class="p-5 md:p-6">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                        <div>

                            <label class="app-label">
                                Staff Name
                            </label>

                            <input
                                type="text"
                                value="{{ $admin->staffName }}"
                                readonly
                                class="app-input !bg-slate-50 !text-slate-600">

                        </div>


                        <div>

                            <label class="app-label">
                                Email
                            </label>

                            <input
                                type="email"
                                value="{{ $admin->email }}"
                                readonly
                                class="app-input !bg-slate-50 !text-slate-600">

                        </div>


                        <div>

                            <label class="app-label">
                                Status
                            </label>

                            <input
                                type="text"
                                value="{{ ucfirst($admin->status) }}"
                                readonly
                                class="app-input !bg-slate-50 !text-slate-600">

                        </div>

                    </div>

                </div>

            </div>


            {{-- Change Password --}}
            <div class="app-section-card">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Change Password
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Set a new password for your administrator account.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('admin.profile.password') }}">

                    @csrf
                    @method('PUT')


                    <div class="p-5 md:p-6">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            <div>

                                <label
                                    for="new_password"
                                    class="app-label">
                                    New Password
                                </label>

                                <div class="relative">

                                    <input
                                        type="password"
                                        id="new_password"
                                        name="password"
                                        required
                                        class="app-input !pr-12">

                                    <button
                                        type="button"
                                        onclick="togglePassword('new_password', this)"
                                        class="absolute inset-y-0 right-0 px-4
                                               flex items-center text-slate-400
                                               hover:text-blue-600 transition">

                                        <svg
                                            class="eye-off h-5 w-5"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor">

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8
                                                   M9.9 4.2A10.5 10.5 0 0112 4
                                                   c5 0 9 4 10 8
                                                   a11.8 11.8 0 01-2.1 3.5
                                                   M6.6 6.6C4.4 8 2.8 10 2 12
                                                   c1 4 5 8 10 8
                                                   1.4 0 2.7-.3 3.9-.8" />

                                        </svg>


                                        <svg
                                            class="eye-on h-5 w-5 hidden"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor">

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0
                                                   3 3 0 016 0z" />

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5
                                                   c4.477 0 8.268 2.943 9.542 7
                                                   -1.274 4.057-5.065 7-9.542 7
                                                   -4.477 0-8.268-2.943-9.542-7z" />

                                        </svg>

                                    </button>

                                </div>


                                <div class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-100">

                                    <p class="text-[10px] font-bold uppercase tracking-[0.07em] text-slate-400 mb-3">
                                        Password Requirements
                                    </p>

                                    <ul class="space-y-2 text-[12px]">

                                        <li id="new-pw-length"
                                            class="flex items-center gap-2 text-gray-400">

                                            <span id="new-icon-length">○</span>
                                            At least 8 characters

                                        </li>

                                        <li id="new-pw-upper"
                                            class="flex items-center gap-2 text-gray-400">

                                            <span id="new-icon-upper">○</span>
                                            At least 1 uppercase letter

                                        </li>

                                        <li id="new-pw-symbol"
                                            class="flex items-center gap-2 text-gray-400">

                                            <span id="new-icon-symbol">○</span>
                                            At least 1 symbol

                                        </li>

                                    </ul>

                                </div>

                            </div>


                            <div>

                                <label
                                    for="new_password_confirmation"
                                    class="app-label">
                                    Confirm New Password
                                </label>

                                <div class="relative">

                                    <input
                                        type="password"
                                        id="new_password_confirmation"
                                        name="password_confirmation"
                                        required
                                        class="app-input !pr-12">

                                    <button
                                        type="button"
                                        onclick="togglePassword('new_password_confirmation', this)"
                                        class="absolute inset-y-0 right-0 px-4
                                               flex items-center text-slate-400
                                               hover:text-blue-600 transition">

                                        <svg
                                            class="eye-off h-5 w-5"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor">

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M3 3l18 18M10.6 10.6a2 2 0 002.8 2.8
                                                   M9.9 4.2A10.5 10.5 0 0112 4
                                                   c5 0 9 4 10 8
                                                   a11.8 11.8 0 01-2.1 3.5
                                                   M6.6 6.6C4.4 8 2.8 10 2 12
                                                   c1 4 5 8 10 8
                                                   1.4 0 2.7-.3 3.9-.8" />

                                        </svg>


                                        <svg
                                            class="eye-on h-5 w-5 hidden"
                                            xmlns="http://www.w3.org/2000/svg"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor">

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0
                                                   3 3 0 016 0z" />

                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5
                                                   c4.477 0 8.268 2.943 9.542 7
                                                   -1.274 4.057-5.065 7-9.542 7
                                                   -4.477 0-8.268-2.943-9.542-7z" />

                                        </svg>

                                    </button>

                                </div>


                                <div class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-100">

                                    <p class="text-[10px] font-bold uppercase tracking-[0.07em] text-slate-400 mb-3">
                                        Confirmation
                                    </p>

                                    <ul class="space-y-2 text-[12px]">

                                        <li id="new-pw-match"
                                            class="flex items-center gap-2 text-gray-400">

                                            <span id="new-icon-match">○</span>
                                            Passwords match

                                        </li>

                                    </ul>

                                </div>

                            </div>

                        </div>


                        @error('password')

                        <p class="text-red-500 text-sm mt-4">
                            {{ $message }}
                        </p>

                        @enderror

                    </div>


                    <div class="px-5 md:px-6 py-5 border-t border-slate-100 flex justify-end">

                        <button
                            type="submit"
                            class="app-button-primary">

                            Update Password

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>


    <script>
        // show/hide password
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const eyeOff = button.querySelector('.eye-off');
            const eyeOn = button.querySelector('.eye-on');

            if (input.type === 'password') {
                input.type = 'text';
                eyeOff.classList.add('hidden');
                eyeOn.classList.remove('hidden');

            } else {
                input.type = 'password';
                eyeOn.classList.add('hidden');
                eyeOff.classList.remove('hidden');
            }
        }

        // password live check
        const newPasswordInput = document.getElementById('new_password');

        const newPasswordConfirmationInput =
            document.getElementById(
                'new_password_confirmation'
            );

        const newPwLength = document.getElementById('new-pw-length');
        const newPwUpper = document.getElementById('new-pw-upper');
        const newPwSymbol = document.getElementById('new-pw-symbol');
        const newPwMatch = document.getElementById('new-pw-match');
        const newIconLength = document.getElementById('new-icon-length');
        const newIconUpper = document.getElementById('new-icon-upper');
        const newIconSymbol = document.getElementById('new-icon-symbol');
        const newIconMatch = document.getElementById('new-icon-match');

        function updatePasswordStatus(
            condition,
            element,
            icon
        ) {

            if (condition) {
                element.classList.remove(
                    'text-gray-400'
                );
                element.classList.add(
                    'text-green-600',
                    'font-medium'
                );
                icon.textContent = '✓';
            } else {
                element.classList.remove(
                    'text-green-600',
                    'font-medium'
                );
                element.classList.add(
                    'text-gray-400'
                );
                icon.textContent = '○';
            }
        }

        function checkNewPasswordRequirements() {

            const value = newPasswordInput.value;

            updatePasswordStatus(
                value.length >= 8,
                newPwLength,
                newIconLength
            );

            updatePasswordStatus(
                /[A-Z]/.test(value),
                newPwUpper,
                newIconUpper
            );

            updatePasswordStatus(
                /[^A-Za-z0-9]/.test(value),
                newPwSymbol,
                newIconSymbol
            );

        }

        function checkNewPasswordMatch() {

            const password = newPasswordInput.value;
            const confirmation = newPasswordConfirmationInput.value;

            const isMatch =
                confirmation.length > 0 &&
                password === confirmation;

            updatePasswordStatus(
                isMatch,
                newPwMatch,
                newIconMatch
            );

        }

        newPasswordInput.addEventListener(
            'input',
            function() {
                checkNewPasswordRequirements();
                checkNewPasswordMatch();
            }
        );

        newPasswordConfirmationInput.addEventListener(
            'input',
            checkNewPasswordMatch
        );
    </script>

</x-app-layout>