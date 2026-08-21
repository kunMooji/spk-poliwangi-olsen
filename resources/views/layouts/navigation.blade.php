<nav x-data="{ open: false }" class="sticky top-0 z-30 border-b border-black/5 bg-porcelain-50/90 backdrop-blur transition-shadow duration-500 ease-brand-out dark:border-white/10 dark:bg-ink-950/85">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-brand-600 to-ink-800 font-display text-[11px] font-bold tracking-tight text-brand-200 ring-1 ring-brand-300/40">SPK</span>
                        <span class="hidden text-sm font-semibold leading-tight text-ink-900 dark:text-porcelain-50 lg:block">
                            {{ config('app.name') }}
                        </span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @if (Auth::user()->isAdmin())
                        <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                            Beranda
                        </x-nav-link>
                        <x-nav-link :href="route('admin.study-programs.index')" :active="request()->routeIs('admin.study-programs.*')">
                            Program Studi
                        </x-nav-link>
                        <x-nav-link :href="route('admin.subjects.index')" :active="request()->routeIs('admin.subjects.*')">
                            Mata Pelajaran
                        </x-nav-link>
                        <x-nav-link :href="route('admin.criteria.index')" :active="request()->routeIs('admin.criteria.*')">
                            Kriteria
                        </x-nav-link>
                        <x-nav-link :href="route('admin.recap.index')" :active="request()->routeIs('admin.recap.*')">
                            Rekap Hasil Tes
                        </x-nav-link>
                        <x-nav-link :href="route('admin.statistics')" :active="request()->routeIs('admin.statistics')">
                            Statistik
                        </x-nav-link>

                        {{-- Menu pengelolaan yang lebih jarang dibuka dikumpulkan
                             di sini supaya bilah navigasi tetap terbaca. --}}
                        @php
                            $kelolaAktif = request()->routeIs('admin.questions.*')
                                || request()->routeIs('admin.tracer.*')
                                || request()->routeIs('admin.periods.*')
                                || request()->routeIs('admin.users.*')
                                || request()->routeIs('admin.activity-logs.*')
                                || request()->routeIs('admin.settings.*');
                        @endphp
                        <div class="flex items-center">
                            <x-dropdown align="left" width="w-56">
                                <x-slot name="trigger">
                                    <button class="inline-flex h-16 items-center gap-1 border-b-2 px-1 text-sm font-medium transition duration-150 ease-brand-out
                                                   {{ $kelolaAktif
                                                       ? 'border-brand-500 text-ink-900 focus:border-brand-700 dark:border-brand-300 dark:text-porcelain-50'
                                                       : 'border-transparent text-ink-500 hover:border-black/15 hover:text-ink-700 dark:text-porcelain-300 dark:hover:border-white/15 dark:hover:text-porcelain-100' }}">
                                        Pengelolaan
                                        <svg class="h-4 w-4 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </x-slot>

                                <x-slot name="content">
                                    <x-dropdown-link :href="route('admin.questions.index')">Pernyataan RIASEC</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.tracer.index')">Tracer Study</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.periods.index')">Gelombang PMB</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.users.index')">Akun Pengguna</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.activity-logs.index')">Catatan Perubahan</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.settings.edit')">Pengaturan</x-dropdown-link>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @else
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            Beranda
                        </x-nav-link>
                        <x-nav-link :href="route('assessments.index')" :active="request()->routeIs('assessments.*')">
                            Tes Saya
                        </x-nav-link>
                        <x-nav-link :href="route('assessments.compare')" :active="request()->routeIs('assessments.compare')">
                            Bandingkan Hasil
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:gap-2">
                <x-theme-toggle />

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-1 rounded-lg border border-transparent px-3 py-2 text-sm font-medium leading-4 text-ink-500 transition ease-brand-out duration-150 hover:bg-black/5 hover:text-ink-700 focus:outline-none dark:text-porcelain-300 dark:hover:bg-white/5 dark:hover:text-porcelain-100">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center gap-1 sm:hidden">
                <x-theme-toggle />

                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-lg text-ink-400 dark:text-porcelain-400 hover:text-ink-600 dark:hover:text-porcelain-200 hover:bg-black/5 dark:hover:bg-white/5 focus:outline-none focus:bg-black/5 dark:focus:bg-white/5 transition duration-150 ease-brand-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if (Auth::user()->isAdmin())
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                    Beranda
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.study-programs.index')" :active="request()->routeIs('admin.study-programs.*')">
                    Program Studi
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.subjects.index')" :active="request()->routeIs('admin.subjects.*')">
                    Mata Pelajaran
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.criteria.index')" :active="request()->routeIs('admin.criteria.*')">
                    Kriteria
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.questions.index')" :active="request()->routeIs('admin.questions.*')">
                    Pernyataan RIASEC
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.tracer.index')" :active="request()->routeIs('admin.tracer.*')">
                    Tracer Study
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.recap.index')" :active="request()->routeIs('admin.recap.*')">
                    Rekap Hasil Tes
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.statistics')" :active="request()->routeIs('admin.statistics')">
                    Statistik
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.periods.index')" :active="request()->routeIs('admin.periods.*')">
                    Gelombang PMB
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                    Akun Pengguna
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.activity-logs.index')" :active="request()->routeIs('admin.activity-logs.*')">
                    Catatan Perubahan
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.settings.edit')" :active="request()->routeIs('admin.settings.*')">
                    Pengaturan
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    Beranda
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('assessments.index')" :active="request()->routeIs('assessments.*')">
                    Tes Saya
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('assessments.compare')" :active="request()->routeIs('assessments.compare')">
                    Bandingkan Hasil
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-black/10 dark:border-white/10">
            <div class="px-4">
                <div class="font-medium text-base text-ink-800 dark:text-porcelain-100">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-ink-500 dark:text-porcelain-400">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
