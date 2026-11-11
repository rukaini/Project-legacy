<x-app-layout>

    <div class="app-page-shell">

        <div class="max-w-7xl mx-auto">

            {{-- Page Header --}}
            <div class="flex items-start gap-4 mb-7">

                <div class="w-12 h-12 rounded-2xl bg-blue-50 border border-blue-200
                            flex items-center justify-center shrink-0">

                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
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
                    
                    <h1 class="text-[26px] md:text-[30px] font-extrabold
                               tracking-tight text-slate-900">
                        My Profile
                    </h1>

                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        View and update your personal information.
                    </p>

                </div>

            </div>


            {{-- Profile Information --}}
            <div class="app-section-card mb-7">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <div class="flex items-center gap-3">

                        <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100
                                    flex items-center justify-center shrink-0">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.8"
                                stroke="currentColor"
                                class="w-[18px] h-[18px] text-blue-600">

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

                            <h2 class="text-[17px] font-extrabold text-slate-900">
                                Personal Information
                            </h2>

                        </div>

                    </div>

                </div>


                <form method="POST" action="{{ route('new_teacher.profile.update') }}">

                    @csrf
                    @method('PUT')


                    <div class="p-5 md:p-6">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-5">

                            {{-- Full Name --}}
                            <div>

                                <label class="app-label">
                                    Full Name
                                </label>

                                <input
                                    type="text"
                                    value="{{ $guru->gn_name }}"
                                    readonly
                                    class="app-input bg-slate-50 text-slate-500 cursor-not-allowed">

                            </div>


                            {{-- Current Status --}}
                            <div>

                                <label class="app-label">
                                    Current Status
                                </label>

                                <input
                                    type="text"
                                    value="{{ $guru->current_status }}"
                                    readonly
                                    class="app-input bg-slate-50 text-slate-500 cursor-not-allowed">

                            </div>


                            {{-- IC --}}
                            <div>

                                <label class="app-label">
                                    IC Number
                                </label>

                                <input
                                    type="text"
                                    value="{{ $guru->ic_number }}"
                                    readonly
                                    class="app-input bg-slate-50 text-slate-500 cursor-not-allowed">

                            </div>


                            {{-- School --}}
                            <div>

                                <label class="app-label">
                                    School
                                </label>

                                <input
                                    type="text"
                                    value="{{ $guru->school?->schoolName ?? '-' }}"
                                    readonly
                                    class="app-input bg-slate-50 text-slate-500 cursor-not-allowed">

                            </div>


                            {{-- Gender --}}
                            <div>

                                <label class="app-label">
                                    Gender
                                </label>

                                <input
                                    type="text"
                                    value="{{ $guru->gender }}"
                                    readonly
                                    class="app-input bg-slate-50 text-slate-500 cursor-not-allowed">

                            </div>


                            {{-- Appointed Date --}}
                            <div>

                                <label class="app-label">
                                    Appointed Date
                                </label>

                                <input
                                    type="date"
                                    value="{{ $guru->appointed_date }}"
                                    readonly
                                    class="app-input bg-slate-50 text-slate-500 cursor-not-allowed">

                            </div>


                            {{-- Phone --}}
                            <div>

                                <label for="phone_number" class="app-label">
                                    Phone Number
                                </label>

                                <input
                                    type="text"
                                    id="phone_number"
                                    name="phone_number"
                                    value="{{ old('phone_number', $guru->phone_number) }}"
                                    maxlength="20"
                                    required
                                    oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                    class="app-input">

                            </div>


                            {{-- Email --}}
                            <div>

                                <label for="email" class="app-label">
                                    Email
                                </label>

                                <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="{{ old('email', $guru->email) }}"
                                    required
                                    class="app-input">

                            </div>


                            {{-- Marital Status --}}
                            <div>

                                <label for="marital_status" class="app-label">
                                    Marital Status
                                </label>

                                <select
                                    id="marital_status"
                                    name="marital_status"
                                    class="app-select">

                                    <option value="">
                                        Select Marital Status
                                    </option>

                                    <option value="Single"
                                        {{ old('marital_status', $guru->marital_status) === 'Single' ? 'selected' : '' }}>
                                        Single
                                    </option>

                                    <option value="Married"
                                        {{ old('marital_status', $guru->marital_status) === 'Married' ? 'selected' : '' }}>
                                        Married
                                    </option>

                                    <option value="Divorced"
                                        {{ old('marital_status', $guru->marital_status) === 'Divorced' ? 'selected' : '' }}>
                                        Divorced
                                    </option>

                                </select>

                            </div>


                            {{-- Race --}}
                            <div>

                                <label for="race" class="app-label">
                                    Race
                                </label>

                                <select
                                    id="race"
                                    name="race"
                                    onchange="toggleOtherRace()"
                                    class="app-select">

                                    <option value="">
                                        Select Race
                                    </option>

                                    <option value="Malay"
                                        {{ old('race', $guru->race) === 'Malay' ? 'selected' : '' }}>
                                        Malay
                                    </option>

                                    <option value="Chinese"
                                        {{ old('race', $guru->race) === 'Chinese' ? 'selected' : '' }}>
                                        Chinese
                                    </option>

                                    <option value="Indian"
                                        {{ old('race', $guru->race) === 'Indian' ? 'selected' : '' }}>
                                        Indian
                                    </option>

                                    <option value="Others"
                                        {{ !in_array(old('race', $guru->race), ['', 'Malay', 'Chinese', 'Indian']) ? 'selected' : '' }}>
                                        Others
                                    </option>

                                </select>

                            </div>


                            {{-- Other Race --}}
                            <div
                                id="other_race_section"
                                class="hidden md:col-start-2">

                                <label for="other_race" class="app-label">
                                    Please Specify Race
                                </label>

                                <input
                                    type="text"
                                    id="other_race"
                                    name="other_race"
                                    value="{{ !in_array(old('race', $guru->race), ['', 'Malay', 'Chinese', 'Indian']) ? old('race', $guru->race) : '' }}"
                                    class="app-input">

                            </div>

                        </div>


                        {{-- Address --}}
                        <div class="border-t border-slate-100 mt-7 pt-7">

                            <h3 class="text-[13px] font-extrabold text-slate-900 mb-5">
                                Address Information
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">

                                <div class="md:col-span-2">

                                    <label for="address_line" class="app-label">
                                        Address
                                    </label>

                                    <input
                                        type="text"
                                        id="address_line"
                                        name="address_line"
                                        value="{{ old('address_line', $addressLine) }}"
                                        required
                                        class="app-input">

                                </div>


                                <div>

                                    <label for="postcode" class="app-label">
                                        Postcode
                                    </label>

                                    <input
                                        type="text"
                                        id="postcode"
                                        name="postcode"
                                        value="{{ old('postcode', $postcode) }}"
                                        maxlength="5"
                                        required
                                        oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                        class="app-input">

                                </div>


                                <div>

                                    <label for="city" class="app-label">
                                        City
                                    </label>

                                    <input
                                        type="text"
                                        id="city"
                                        name="city"
                                        value="{{ old('city', $city) }}"
                                        required
                                        oninput="this.value=this.value.replace(/[^A-Za-z ]/g,'')"
                                        class="app-input">

                                </div>


                                <div>

                                    <label for="state" class="app-label">
                                        State
                                    </label>

                                    <input
                                        type="text"
                                        id="state"
                                        name="state"
                                        value="{{ old('state', $state) }}"
                                        required
                                        oninput="this.value=this.value.replace(/[^A-Za-z ]/g,'')"
                                        class="app-input">

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- Profile Actions --}}
                    <div class="px-5 md:px-6 py-4 bg-slate-50
                                border-t border-slate-100
                                flex justify-end">

                        <button
                            type="submit"
                            class="app-button-primary">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.8"
                                stroke="currentColor"
                                class="w-4 h-4">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16.862 4.487a2.1 2.1 0 013 2.97
                                       L8.4 18.92 4 20l1.08-4.4
                                       11.782-11.113z" />
                            </svg>

                            Update Profile

                        </button>

                    </div>

                </form>

            </div>


            {{-- Change Password --}}
            <div class="app-section-card">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <div class="flex items-center gap-3">

                        <div class="w-9 h-9 rounded-xl bg-blue-50 border border-blue-100
                                    flex items-center justify-center shrink-0">

                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="1.8"
                                stroke="currentColor"
                                class="w-[18px] h-[18px] text-blue-600">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75
                                       m-.75 0h10.5A2.25 2.25 0 0119.5 12.75
                                       v6A2.25 2.25 0 0117.25 21H6.75
                                       A2.25 2.25 0 014.5 18.75v-6
                                       A2.25 2.25 0 016.75 10.5Z" />
                            </svg>

                        </div>

                        <div>

                            <h2 class="text-[17px] font-extrabold text-slate-900">
                                Change Password
                            </h2>

                        </div>

                    </div>

                </div>


                <form method="POST" action="{{ route('new_teacher.profile.password') }}">

                    @csrf
                    @method('PUT')


                    <div class="p-5 md:p-6">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                            {{-- New Password --}}
                            <div>

                                <label for="new_password" class="app-label">
                                    New Password
                                </label>

                                <div class="relative">

                                    <input
                                        type="password"
                                        id="new_password"
                                        name="password"
                                        required
                                        class="app-input pr-12">

                                    <button
                                        type="button"
                                        onclick="togglePassword('new_password', this)"
                                        class="absolute inset-y-0 right-0 px-4
                                               flex items-center justify-center
                                               text-slate-400 hover:text-blue-600 transition">

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


                                <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200 p-4">

                                    <p class="text-[11px] uppercase tracking-[0.06em]
                                              font-extrabold text-slate-500 mb-3">
                                        Password Requirements
                                    </p>

                                    <ul class="space-y-2 text-[12.5px]">

                                        <li
                                            id="new-pw-length"
                                            class="flex items-center gap-2 text-gray-400">

                                            <span
                                                id="new-icon-length"
                                                class="w-5 h-5 rounded-full border border-slate-300
                                                       flex items-center justify-center text-[11px]">
                                                ○
                                            </span>

                                            <span>
                                                At least 8 characters
                                            </span>

                                        </li>

                                        <li
                                            id="new-pw-upper"
                                            class="flex items-center gap-2 text-gray-400">

                                            <span
                                                id="new-icon-upper"
                                                class="w-5 h-5 rounded-full border border-slate-300
                                                       flex items-center justify-center text-[11px]">
                                                ○
                                            </span>

                                            <span>
                                                At least 1 uppercase letter
                                            </span>

                                        </li>

                                        <li
                                            id="new-pw-symbol"
                                            class="flex items-center gap-2 text-gray-400">

                                            <span
                                                id="new-icon-symbol"
                                                class="w-5 h-5 rounded-full border border-slate-300
                                                       flex items-center justify-center text-[11px]">
                                                ○
                                            </span>

                                            <span>
                                                At least 1 symbol
                                            </span>

                                        </li>

                                    </ul>

                                </div>

                            </div>


                            {{-- Confirm Password --}}
                            <div>

                                <label for="new_password_confirmation" class="app-label">
                                    Confirm New Password
                                </label>

                                <div class="relative">

                                    <input
                                        type="password"
                                        id="new_password_confirmation"
                                        name="password_confirmation"
                                        required
                                        class="app-input pr-12">

                                    <button
                                        type="button"
                                        onclick="togglePassword('new_password_confirmation', this)"
                                        class="absolute inset-y-0 right-0 px-4
                                               flex items-center justify-center
                                               text-slate-400 hover:text-blue-600 transition">

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


                                <div class="mt-4 rounded-xl bg-slate-50 border border-slate-200 p-4">

                                    <p class="text-[11px] uppercase tracking-[0.06em]
                                              font-extrabold text-slate-500 mb-3">
                                        Confirmation
                                    </p>

                                    <ul class="space-y-2 text-[12.5px]">

                                        <li
                                            id="new-pw-match"
                                            class="flex items-center gap-2 text-gray-400">

                                            <span
                                                id="new-icon-match"
                                                class="w-5 h-5 rounded-full border border-slate-300
                                                       flex items-center justify-center text-[11px]">
                                                ○
                                            </span>

                                            <span>
                                                Passwords match
                                            </span>

                                        </li>

                                    </ul>

                                </div>

                            </div>

                        </div>


                        @error('password')

                        <div class="mt-5 bg-red-50 border border-red-200
                                    rounded-xl px-4 py-3">

                            <p class="text-red-600 text-sm">
                                {{ $message }}
                            </p>

                        </div>

                        @enderror

                    </div>


                    {{-- Password Action --}}
                    <div class="px-5 md:px-6 py-4 bg-slate-50
                                border-t border-slate-100
                                flex justify-end">

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
        // race
        function toggleOtherRace() {

            const race = document.getElementById('race');
            const otherSection = document.getElementById('other_race_section');
            const otherInput = document.getElementById('other_race');

            if (race.value === 'Others') {
                otherSection.classList.remove('hidden');
                otherInput.required = true;
            } else {
                otherSection.classList.add('hidden');
                otherInput.required = false;
                otherInput.value = '';
            }
        }

        document.addEventListener(
            'DOMContentLoaded',
            function() {
                toggleOtherRace();
            }
        );

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