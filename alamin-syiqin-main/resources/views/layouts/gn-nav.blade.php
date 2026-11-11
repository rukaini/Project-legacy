<nav class="sticky top-0 z-50 h-[50px] bg-white border-b border-slate-200">

    <div class="w-full h-full px-5">

        <div class="h-full flex items-center justify-between">

            <div class="h-full flex items-center">

                <a href="{{ route('new_teacher.dashboard') }}"
                    class="h-full flex items-center pr-6">

                    <img
                        src="{{ asset('images/logo.jpg') }}"
                        alt="Edu Oasis"
                        class="h-9 w-auto object-contain"
                    >

                </a>

                <div class="hidden lg:flex items-center h-full">

                    <a href="{{ route('new_teacher.dashboard') }}"
                        class="h-full px-5 flex items-center text-[13px] font-semibold transition
                        {{ request()->routeIs('new_teacher.dashboard')
                            ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50/40'
                            : 'text-slate-500 hover:text-slate-800' }}">

                        Dashboard
                    </a>

                    <a href="{{ route('new_teacher.result') }}"
                        class="h-full px-5 flex items-center text-[13px] font-semibold transition
                        {{ request()->routeIs('new_teacher.result')
                            ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50/40'
                            : 'text-slate-500 hover:text-slate-800' }}">

                        Result
                    </a>

                    <a href="{{ route('new_teacher.profile') }}"
                        class="h-full px-5 flex items-center text-[13px] font-semibold transition
                        {{ request()->routeIs('new_teacher.profile')
                            ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50/40'
                            : 'text-slate-500 hover:text-slate-800' }}">

                        My Profile
                    </a>

                </div>

            </div>

            <div class="hidden lg:flex items-center h-full">

                <div class="h-[32px] border-l border-slate-200 mr-4"></div>

                <div class="text-right mr-3 leading-[1.05]">

                    <div class="text-[12px] font-bold text-slate-800">
                        {{ Auth::guard('new_teacher')->user()->gn_name ?? 'New Teacher' }}
                    </div>

                    <div class="text-[10px] font-medium text-slate-400 mt-0.5">
                        New Teacher
                    </div>

                </div>

                <div class="w-8 h-8 rounded-lg bg-blue-50 border border-blue-200
                            flex items-center justify-center mr-3">

                    <span class="text-[12px] font-bold text-blue-600">
                        {{ strtoupper(substr(Auth::guard('new_teacher')->user()->gn_name ?? 'N', 0, 1)) }}
                    </span>

                </div>

                <form method="POST" action="{{ route('logout') }}">

                    @csrf

                    <button type="submit"
                        title="Logout"
                        class="w-8 h-8 flex items-center justify-center
                               text-slate-400 hover:text-blue-600 transition">

                        <svg xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.7"
                            stroke="currentColor"
                            class="w-[18px] h-[18px]">

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6A2.25 2.25 0 005.25 5.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M18 15l3-3m0 0l-3-3m3 3H9"
                            />

                        </svg>

                    </button>

                </form>

            </div>

            <details class="lg:hidden relative">

                <summary
                    class="list-none cursor-pointer w-8 h-8 flex items-center justify-center text-slate-600">

                    <svg xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                        class="w-5 h-5">

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"
                        />

                    </svg>

                </summary>

                <div class="absolute right-0 top-10 w-52 bg-white border border-slate-200
                            rounded-xl shadow-lg p-2">

                    <a href="{{ route('new_teacher.dashboard') }}"
                        class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Dashboard
                    </a>

                    <a href="{{ route('new_teacher.result') }}"
                        class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        Result
                    </a>

                    <a href="{{ route('new_teacher.profile') }}"
                        class="block px-3 py-2 rounded-lg text-sm font-semibold text-slate-600 hover:bg-slate-50">
                        My Profile
                    </a>

                    <div class="border-t border-slate-100 mt-2 pt-2">

                        <form method="POST" action="{{ route('logout') }}">

                            @csrf

                            <button type="submit"
                                class="w-full text-left px-3 py-2 rounded-lg
                                       text-sm font-semibold text-red-600 hover:bg-red-50">
                                Logout
                            </button>

                        </form>

                    </div>

                </div>

            </details>

        </div>

    </div>

</nav>