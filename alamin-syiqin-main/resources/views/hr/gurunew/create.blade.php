<x-app-layout>

    <div class="app-page-shell">

        <div class="max-w-8xl mx-auto">

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
                            d="M18 7.5v3m0 0v3m0-3h3m-3 0h-3M6.75 6.75a3 3 0 116 0 3 3 0 01-6 0zM3.75 20.25a6 6 0 0112 0v.75h-12v-.75z" />

                    </svg>

                </div>

                <div>

                    <h1 class="text-[26px] md:text-[30px] font-extrabold tracking-tight text-slate-900">
                        Register New Teacher
                    </h1>

                    <p class="text-[13px] md:text-sm text-slate-500 mt-1">
                        Create a new teacher account.
                    </p>

                </div>

            </div>


            <div class="app-section-card">

                <div class="px-5 md:px-6 py-5 border-b border-slate-100">

                    <h2 class="text-[17px] font-extrabold text-slate-900">
                        Teacher Information
                    </h2>

                    <p class="text-[12.5px] text-slate-500 mt-1">
                        Enter the teacher details below.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route('hr.gurunew.store') }}">

                    @csrf

                    <div class="p-5 md:p-6">

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-x-8 gap-y-6">

                            {{-- LEFT COLUMN --}}
                            <div class="space-y-5">

                                <div>

                                    <label
                                        for="ic_number"
                                        class="app-label">
                                        IC Number
                                    </label>

                                    <input
                                        type="text"
                                        id="ic_number"
                                        name="ic_number"
                                        value="{{ old('ic_number') }}"
                                        maxlength="12"
                                        required
                                        class="app-input">

                                    @error('ic_number')
                                    <p class="text-red-500 text-xs mt-1.5">
                                        {{ $message }}
                                    </p>
                                    @enderror

                                </div>


                                <div>

                                    <label
                                        for="gender"
                                        class="app-label">
                                        Gender
                                    </label>

                                    <input
                                        type="text"
                                        id="gender"
                                        readonly
                                        placeholder="Auto detected from IC"
                                        class="app-input !bg-slate-50 !text-slate-500">

                                </div>


                                <div>

                                    <label
                                        for="gn_name"
                                        class="app-label">
                                        Full Name
                                    </label>

                                    <input
                                        type="text"
                                        id="gn_name"
                                        name="gn_name"
                                        value="{{ old('gn_name') }}"
                                        required
                                        class="app-input">

                                    @error('gn_name')
                                    <p class="text-red-500 text-xs mt-1.5">
                                        {{ $message }}
                                    </p>
                                    @enderror

                                </div>


                                <div>

                                    <label
                                        for="email"
                                        class="app-label">
                                        Email
                                    </label>

                                    <input
                                        type="email"
                                        id="email"
                                        name="email"
                                        value="{{ old('email') }}"
                                        required
                                        class="app-input">

                                    @error('email')
                                    <p class="text-red-500 text-xs mt-1.5">
                                        {{ $message }}
                                    </p>
                                    @enderror

                                </div>


                                <div>

                                    <label
                                        for="phone_number"
                                        class="app-label">
                                        Phone Number
                                    </label>

                                    <input
                                        type="text"
                                        id="phone_number"
                                        name="phone_number"
                                        value="{{ old('phone_number') }}"
                                        maxlength="20"
                                        required
                                        oninput="this.value=this.value.replace(/[^0-9]/g,'')"
                                        class="app-input">

                                    @error('phone_number')
                                    <p class="text-red-500 text-xs mt-1.5">
                                        {{ $message }}
                                    </p>
                                    @enderror

                                </div>

                            </div>


                            {{-- RIGHT COLUMN --}}
                            <div class="space-y-5">

                                <div>

                                    <label
                                        for="schoolID"
                                        class="app-label">
                                        School
                                    </label>

                                    <select
                                        id="schoolID"
                                        name="schoolID"
                                        required
                                        class="app-select">

                                        <option value="">
                                            Select School
                                        </option>

                                        @foreach($schools as $school)

                                        <option
                                            value="{{ $school->schoolID }}"
                                            {{ old('schoolID') == $school->schoolID ? 'selected' : '' }}>

                                            {{ $school->schoolName }}

                                        </option>

                                        @endforeach

                                    </select>

                                    @error('schoolID')
                                    <p class="text-red-500 text-xs mt-1.5">
                                        {{ $message }}
                                    </p>
                                    @enderror

                                </div>


                                <div>

                                    <label
                                        for="appointed_date"
                                        class="app-label">
                                        Appointed Date
                                    </label>

                                    <input
                                        type="date"
                                        id="appointed_date"
                                        name="appointed_date"
                                        value="{{ old('appointed_date') }}"
                                        required
                                        class="app-input">

                                    @error('appointed_date')
                                    <p class="text-red-500 text-xs mt-1.5">
                                        {{ $message }}
                                    </p>
                                    @enderror

                                </div>


                                <div>

                                    <label
                                        for="password"
                                        class="app-label">
                                        Temporary Password
                                    </label>

                                    <div class="relative">

                                        <input
                                            type="password"
                                            id="password"
                                            name="password"
                                            required
                                            class="app-input !pr-12">

                                        <button
                                            type="button"
                                            onclick="togglePassword('password', this)"
                                            class="absolute inset-y-0 right-0 px-4 flex items-center
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
                                                    d="M3 3l18 18
                                                   M10.6 10.6a2 2 0 002.8 2.8
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
                                                    d="M2.458 12
                                                   C3.732 7.943 7.523 5 12 5
                                                   c4.477 0 8.268 2.943 9.542 7
                                                   -1.274 4.057-5.065 7-9.542 7
                                                   -4.477 0-8.268-2.943-9.542-7z" />

                                            </svg>

                                        </button>

                                    </div>

                                </div>


                                <div>

                                    <label
                                        for="password_confirmation"
                                        class="app-label">
                                        Confirm Password
                                    </label>

                                    <div class="relative">

                                        <input
                                            type="password"
                                            id="password_confirmation"
                                            name="password_confirmation"
                                            required
                                            class="app-input !pr-12">

                                        <button
                                            type="button"
                                            onclick="togglePassword('password_confirmation', this)"
                                            class="absolute inset-y-0 right-0 px-4 flex items-center
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
                                                    d="M3 3l18 18
                                                   M10.6 10.6a2 2 0 002.8 2.8
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
                                                    d="M2.458 12
                                                   C3.732 7.943 7.523 5 12 5
                                                   c4.477 0 8.268 2.943 9.542 7
                                                   -1.274 4.057-5.065 7-9.542 7
                                                   -4.477 0-8.268-2.943-9.542-7z" />

                                            </svg>

                                        </button>

                                    </div>


                                    <div class="mt-4 p-4 rounded-xl bg-slate-50 border border-slate-100">

                                        <p class="text-[11px] font-bold uppercase tracking-[0.06em] text-slate-400 mb-3">
                                            Password Requirements
                                        </p>

                                        <ul class="space-y-2 text-[12.5px]">

                                            <li
                                                id="pw-length"
                                                class="flex items-center gap-2 text-gray-400">

                                                <span id="icon-length">○</span>

                                                <span>
                                                    At least 8 characters
                                                </span>

                                            </li>


                                            <li
                                                id="pw-upper"
                                                class="flex items-center gap-2 text-gray-400">

                                                <span id="icon-upper">○</span>

                                                <span>
                                                    At least 1 uppercase letter
                                                </span>

                                            </li>


                                            <li
                                                id="pw-symbol"
                                                class="flex items-center gap-2 text-gray-400">

                                                <span id="icon-symbol">○</span>

                                                <span>
                                                    At least 1 symbol
                                                </span>

                                            </li>


                                            <li
                                                id="pw-match"
                                                class="flex items-center gap-2 text-gray-400">

                                                <span id="icon-match">○</span>

                                                <span>
                                                    Passwords match
                                                </span>

                                            </li>

                                        </ul>

                                    </div>

                                    @error('password')
                                    <p class="text-red-500 text-xs mt-2">
                                        {{ $message }}
                                    </p>
                                    @enderror

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="px-5 md:px-6 py-5 border-t border-slate-100
                            flex items-center justify-end gap-3">

                        <a
                            href="{{ route('hr.gurunew.index') }}"
                            class="app-button-outline">
                            Cancel
                        </a>

                        <button
                            type="submit"
                            class="app-button-primary">
                            Register
                        </button>

                    </div>

                </form>

            </div>

        </div>


        <script>
            // auto detect gender
            const icInput = document.getElementById('ic_number');
            const genderInput = document.getElementById('gender');

            icInput.addEventListener('input', function() {
                const ic = this.value.replace(/\D/g, '');

                this.value = ic;

                if (ic.length === 12) {
                    const lastDigit = parseInt(ic.charAt(11));
                    genderInput.value =
                        lastDigit % 2 === 0 ?
                        'Female' :
                        'Male';
                } else {
                    genderInput.value = '';
                }
            });

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

            // live password check
            const passwordInput = document.getElementById('password');
            const passwordConfirmationInput = document.getElementById('password_confirmation');
            const pwLength = document.getElementById('pw-length');
            const pwUpper = document.getElementById('pw-upper');
            const pwSymbol = document.getElementById('pw-symbol');
            const pwMatch = document.getElementById('pw-match');
            const iconLength = document.getElementById('icon-length');
            const iconUpper = document.getElementById('icon-upper');
            const iconSymbol = document.getElementById('icon-symbol');
            const iconMatch = document.getElementById('icon-match');

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

            function checkPasswordRequirements() {
                const value = passwordInput.value;

                // at least 8 characters
                updatePasswordStatus(
                    value.length >= 8,
                    pwLength,
                    iconLength
                );

                // at least 1 uppercase letter
                updatePasswordStatus(
                    /[A-Z]/.test(value),
                    pwUpper,
                    iconUpper
                );

                // at least 1 symbol
                updatePasswordStatus(
                    /[^A-Za-z0-9]/.test(value),
                    pwSymbol,
                    iconSymbol
                );
            }

            function checkPasswordMatch() {

                const password = passwordInput.value;
                const confirmation = passwordConfirmationInput.value;

                const isMatch =
                    confirmation.length > 0 &&
                    password === confirmation;

                updatePasswordStatus(
                    isMatch,
                    pwMatch,
                    iconMatch
                );
            }

            passwordInput.addEventListener(
                'input',
                function() {
                    checkPasswordRequirements();
                    checkPasswordMatch();
                }
            );

            passwordConfirmationInput.addEventListener(
                'input',
                checkPasswordMatch
            );
        </script>

</x-app-layout>