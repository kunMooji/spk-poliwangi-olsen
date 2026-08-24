@php($isAdmin = Auth::user()->isAdmin())

{{-- Drawer untuk kedua peran agar area kerja tetap fokus pada konten. --}}
<div x-data="{ open: false }"
     x-effect="document.documentElement.style.overflow = open ? 'hidden' : ''; document.body.style.overflow = open ? 'hidden' : ''"
     @keydown.escape.window="open = false">
    <div class="fixed right-4 top-3 z-40 flex items-center gap-2 sm:right-6">
        <x-theme-toggle />
        <button type="button" @click="open = true" :aria-expanded="open.toString()" aria-controls="main-navigation"
                class="inline-flex items-center gap-2 rounded-2xl border border-black/5 bg-white/85 p-1.5 ps-3 text-ink-700 shadow-sm shadow-ink-950/10 backdrop-blur transition duration-200 ease-brand-out hover:-translate-y-0.5 hover:border-brand-500/40 focus:outline-none focus:ring-2 focus:ring-brand-500/50 dark:border-white/10 dark:bg-ink-900/85 dark:text-porcelain-100">
            <span class="hidden text-sm font-semibold sm:block">Hi, {{ Auth::user()->name }}</span>
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-100 font-mono text-xs font-bold text-brand-800 dark:bg-brand-500/20 dark:text-brand-200">
                {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
            </span>
            <span class="sr-only">Buka menu {{ $isAdmin ? 'admin' : 'siswa' }}</span>
        </button>
    </div>

    <div x-cloak x-show="open" x-transition.opacity class="fixed inset-0 z-40 bg-ink-950/45 backdrop-blur-sm" @click="open = false" aria-hidden="true"></div>

    <aside id="main-navigation" x-cloak x-show="open"
           x-transition:enter="transition ease-out duration-300"
           @if ($isAdmin)
               x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="translate-x-full"
           @else
               x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
               x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
           @endif
           @class([
               'fixed inset-y-0 z-50 flex w-[20rem] max-w-[calc(100vw-2rem)] flex-col bg-porcelain-50 px-4 py-5 shadow-2xl shadow-ink-950/20 dark:bg-ink-950 sm:px-5',
               'right-0 border-l border-black/5 dark:border-white/10' => $isAdmin,
               'left-0 border-r border-black/5 dark:border-white/10' => ! $isAdmin,
           ])
           role="dialog" aria-modal="true" aria-label="{{ $isAdmin ? 'Navigasi admin' : 'Navigasi siswa' }}">
        <div class="flex items-center justify-between gap-3 px-1">
            <a href="{{ $isAdmin ? route('admin.dashboard') : route('dashboard') }}" class="flex min-w-0 items-center gap-3" @click="open = false">
                <img src="{{ asset('images/poliwangi_logo.png') }}" alt="Logo Politeknik Negeri Banyuwangi" class="h-10 w-10 shrink-0 object-contain">
                <span class="min-w-0 font-display text-xs font-extrabold uppercase tracking-wide text-ink-900 dark:text-porcelain-50">SPK <span class="text-brand-600 dark:text-brand-300">Poliwangi</span></span>
            </a>
            <button type="button" @click="open = false" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-ink-500 transition hover:bg-black/5 hover:text-ink-900 focus:outline-none focus:ring-2 focus:ring-brand-500/50 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50">
                <x-heroicon-o-x-mark class="h-5 w-5" aria-hidden="true" /><span class="sr-only">Tutup menu navigasi</span>
            </button>
        </div>

        <div class="mt-8 min-h-0 flex-1 overflow-y-auto px-1" data-lenis-prevent>
            <p class="font-mono text-[10px] font-bold uppercase tracking-[0.2em] text-ink-400 dark:text-porcelain-400/60">{{ $isAdmin ? 'Menu admin' : 'Menu siswa' }}</p>
            @if ($isAdmin)
                <nav class="mt-3 space-y-1.5" aria-label="Menu utama admin">
                    <a href="{{ route('admin.dashboard') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('admin.dashboard'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !request()->routeIs('admin.dashboard')])><x-heroicon-o-squares-2x2 class="h-5 w-5 shrink-0" /> Beranda</a>
                    <a href="{{ route('admin.study-programs.index') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('admin.study-programs.*'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !request()->routeIs('admin.study-programs.*')])><x-heroicon-o-academic-cap class="h-5 w-5 shrink-0" /> Program studi</a>
                    <a href="{{ route('admin.subjects.index') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('admin.subjects.*'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !request()->routeIs('admin.subjects.*')])><x-heroicon-o-book-open class="h-5 w-5 shrink-0" /> Mata pelajaran</a>
                    <a href="{{ route('admin.criteria.index') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('admin.criteria.*'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !request()->routeIs('admin.criteria.*')])><x-heroicon-o-list-bullet class="h-5 w-5 shrink-0" /> Kriteria</a>
                    <a href="{{ route('admin.recap.index') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('admin.recap.*'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !request()->routeIs('admin.recap.*')])><x-heroicon-o-clipboard-document-check class="h-5 w-5 shrink-0" /> Rekap hasil tes</a>
                    <a href="{{ route('admin.statistics') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('admin.statistics'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !request()->routeIs('admin.statistics')])><x-heroicon-o-chart-bar class="h-5 w-5 shrink-0" /> Statistik</a>
                </nav>

                <p class="mt-6 font-mono text-[10px] font-bold uppercase tracking-[0.2em] text-ink-400 dark:text-porcelain-400/60">Pengelolaan</p>
                <nav class="mt-3 space-y-1.5" aria-label="Menu pengelolaan admin">
                    <a href="{{ route('admin.questions.index') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('admin.questions.*'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !request()->routeIs('admin.questions.*')])><x-heroicon-o-clipboard-document-check class="h-5 w-5 shrink-0" /> Pernyataan RIASEC</a>
                    <a href="{{ route('admin.tracer.index') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('admin.tracer.*'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !request()->routeIs('admin.tracer.*')])><x-heroicon-o-chart-bar class="h-5 w-5 shrink-0" /> Tracer study</a>
                    <a href="{{ route('admin.periods.index') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('admin.periods.*'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !request()->routeIs('admin.periods.*')])><x-heroicon-o-calendar class="h-5 w-5 shrink-0" /> Gelombang PMB</a>
                    <a href="{{ route('admin.users.index') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('admin.users.*'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !request()->routeIs('admin.users.*')])><x-heroicon-o-user-group class="h-5 w-5 shrink-0" /> Akun pengguna</a>
                    <a href="{{ route('admin.activity-logs.index') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('admin.activity-logs.*'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !request()->routeIs('admin.activity-logs.*')])><x-heroicon-o-list-bullet class="h-5 w-5 shrink-0" /> Catatan perubahan</a>
                    <a href="{{ route('admin.settings.edit') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('admin.settings.*'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !request()->routeIs('admin.settings.*')])><x-heroicon-o-user-circle class="h-5 w-5 shrink-0" /> Pengaturan</a>
                </nav>
            @else
                <nav class="mt-3 space-y-1.5" aria-label="Menu utama siswa">
                    <a href="{{ route('dashboard') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('dashboard'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !request()->routeIs('dashboard')])><x-heroicon-o-squares-2x2 class="h-5 w-5 shrink-0" /> Beranda</a>
                    <a href="{{ route('assessments.index') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('assessments.index') || request()->routeIs('assessments.create') || request()->routeIs('assessments.questionnaire') || request()->routeIs('assessments.result') || request()->routeIs('assessments.calculation'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !(request()->routeIs('assessments.index') || request()->routeIs('assessments.create') || request()->routeIs('assessments.questionnaire') || request()->routeIs('assessments.result') || request()->routeIs('assessments.calculation'))])><x-heroicon-o-clipboard-document-check class="h-5 w-5 shrink-0" /> Tes saya</a>
                    <a href="{{ route('assessments.compare') }}" @click="open = false" @class(['flex items-center gap-3 rounded-xl px-3 py-3 text-sm font-semibold transition', 'bg-brand-600 text-porcelain-50 shadow-sm shadow-brand-600/25' => request()->routeIs('assessments.compare'), 'text-ink-600 hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50' => !request()->routeIs('assessments.compare')])><x-heroicon-o-arrows-right-left class="h-5 w-5 shrink-0" /> Bandingkan hasil</a>
                </nav>
            @endif
        </div>

        <div class="mt-4 border-t border-black/10 pt-4 dark:border-white/10">
            <div class="flex items-center justify-between gap-3 px-2">
                <a href="{{ route('profile.edit') }}" @click="open = false" class="min-w-0 transition hover:text-brand-600 dark:hover:text-brand-300"><p class="truncate text-sm font-semibold text-ink-800 dark:text-porcelain-100">{{ Auth::user()->name }}</p><p class="truncate text-xs text-ink-500 dark:text-porcelain-400">{{ Auth::user()->email }}</p></a>
                <x-theme-toggle />
            </div>
            <div class="mt-4 space-y-1.5">
                <a href="{{ route('profile.edit') }}" @click="open = false" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink-600 transition hover:bg-black/5 hover:text-ink-900 dark:text-porcelain-300 dark:hover:bg-white/10 dark:hover:text-porcelain-50"><x-heroicon-o-user-circle class="h-5 w-5 shrink-0" /> Profil</a>
                <form method="POST" action="{{ route('logout') }}">@csrf <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink-600 transition hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/40 dark:text-porcelain-300 dark:hover:bg-red-950/30 dark:hover:text-red-300"><x-heroicon-o-arrow-left-start-on-rectangle class="h-5 w-5 shrink-0" /> Keluar</button></form>
            </div>
        </div>
    </aside>
</div>
