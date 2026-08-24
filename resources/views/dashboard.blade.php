<x-app-layout>
    <div class="py-8 sm:py-10">
        {{-- Samakan bidang kerja siswa dengan admin: lebar penuh dengan gutter responsif,
             tanpa batas max-width yang menyisakan ruang besar di kanan dan kiri. --}}
        <div class="student-dashboard mx-auto flex max-w-none flex-col gap-6 px-5 sm:px-8 lg:px-10 xl:px-12">
            <x-flash />

            <section class="dashboard-enter dashboard-enter-1 relative overflow-hidden rounded-[2rem] bg-ink-950 px-6 py-8 text-porcelain-50 shadow-2xl shadow-ink-950/20 sm:px-10 sm:py-11">
                <div class="pointer-events-none absolute -right-20 -top-24 h-72 w-72 rounded-full bg-brand-400/20 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-24 left-[35%] h-64 w-64 rounded-full bg-gold-400/10 blur-3xl"></div>
                <div class="relative grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-end">
                    <div class="max-w-2xl">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1 font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-200">
                            <x-heroicon-o-sparkles class="h-3.5 w-3.5" aria-hidden="true" />
                            Ruang rekomendasi pribadi
                        </span>
                        <p class="mt-6 text-sm text-porcelain-200/70">Selamat datang kembali,</p>
                        <h1 class="mt-1 font-display text-3xl font-extrabold uppercase leading-[1.08] tracking-tight sm:text-4xl">
                            {{ auth()->user()->name }}
                        </h1>
                        <p class="mt-4 max-w-xl text-sm leading-relaxed text-porcelain-200/80 sm:text-base">
                            Telusuri kecocokan minat RIASEC, kesiapan akademik, dan rekomendasi program studi Anda dalam satu tempat.
                        </p>

                        <div class="mt-7 flex flex-wrap items-center gap-3">
                            @if ($unfinished)
                                <a href="{{ route('assessments.questionnaire', $unfinished) }}"
                                   class="inline-flex items-center gap-2 rounded-full bg-brand-300 px-5 py-3 text-sm font-bold text-ink-950 shadow-lg shadow-brand-400/10 transition duration-300 ease-brand-out hover:-translate-y-0.5 hover:bg-brand-200 active:scale-[0.98]">
                                    Lanjutkan tes
                                    <x-heroicon-o-arrow-right class="h-4 w-4" aria-hidden="true" />
                                </a>
                            @else
                                <a href="{{ route('assessments.create') }}"
                                   class="inline-flex items-center gap-2 rounded-full bg-brand-300 px-5 py-3 text-sm font-bold text-ink-950 shadow-lg shadow-brand-400/10 transition duration-300 ease-brand-out hover:-translate-y-0.5 hover:bg-brand-200 active:scale-[0.98]">
                                    Mulai tes rekomendasi
                                    <x-heroicon-o-arrow-right class="h-4 w-4" aria-hidden="true" />
                                </a>
                            @endif
                            <a href="{{ route('assessments.index') }}" class="inline-flex items-center gap-2 rounded-full border border-white/20 px-5 py-3 text-sm font-semibold text-porcelain-100 transition hover:border-brand-300/60 hover:bg-white/10">
                                Riwayat tes
                                <x-heroicon-o-clock class="h-4 w-4" aria-hidden="true" />
                            </a>
                        </div>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/[0.07] p-5 backdrop-blur-sm">
                        <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-200">Status saat ini</p>
                        @if ($latest?->isCompleted())
                            <p class="mt-4 text-xs text-porcelain-200/70">Rekomendasi terakhir Anda</p>
                            <p class="mt-1 text-lg font-bold leading-snug">{{ $latest->recommendedProgram?->full_name ?? 'Belum tersedia' }}</p>
                            <div class="mt-4 flex items-center justify-between border-t border-white/10 pt-4 text-xs text-porcelain-200/70">
                                <span>Kode Holland</span>
                                <span class="rounded-md bg-brand-300/15 px-2 py-1 font-mono font-bold tracking-widest text-brand-200">{{ $latest->holland_code }}</span>
                            </div>
                        @elseif ($unfinished)
                            <p class="mt-4 text-lg font-bold">Tes sedang berjalan</p>
                            <p class="mt-2 text-sm leading-relaxed text-porcelain-200/70">Lanjutkan dari langkah terakhir untuk melihat rekomendasi Anda.</p>
                        @else
                            <p class="mt-4 text-lg font-bold">Siap memulai?</p>
                            <p class="mt-2 text-sm leading-relaxed text-porcelain-200/70">Jawab kuesioner dan isi data akademik untuk mendapat rekomendasi prodi.</p>
                        @endif
                    </div>
                </div>
            </section>

            <section class="dashboard-enter dashboard-enter-2 grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['label' => 'Tes selesai', 'value' => $totalCompleted, 'unit' => 'sesi', 'icon' => 'clipboard-document-check'],
                    ['label' => 'Program studi', 'value' => $programCount, 'unit' => 'alternatif', 'icon' => 'academic-cap'],
                    ['label' => 'Pernyataan RIASEC', 'value' => $questionCount, 'unit' => 'butir', 'icon' => 'sparkles'],
                ] as $stat)
                    <article class="dashboard-lift group rounded-2xl border border-black/5 bg-white p-5 shadow-sm shadow-ink-950/5 transition duration-300 ease-brand-out hover:border-brand-500/25 hover:shadow-lg hover:shadow-ink-950/10 dark:border-white/10 dark:bg-ink-900/60">
                        <div class="flex items-start justify-between gap-3">
                            <p class="text-sm font-medium text-ink-500 dark:text-porcelain-300/70">{{ $stat['label'] }}</p>
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-50 text-brand-600 transition group-hover:bg-brand-600 group-hover:text-porcelain-50 dark:bg-brand-500/10 dark:text-brand-300">
                                <x-dynamic-component :component="'heroicon-o-'.$stat['icon']" class="h-4 w-4" aria-hidden="true" />
                            </span>
                        </div>
                        <p class="mt-5 font-mono text-3xl font-bold tabular-nums text-ink-950 dark:text-porcelain-50">
                            {{ $stat['value'] }} <span class="font-sans text-sm font-normal text-ink-400 dark:text-porcelain-400">{{ $stat['unit'] }}</span>
                        </p>
                    </article>
                @endforeach
            </section>

            <section class="dashboard-enter dashboard-enter-3 grid gap-6 lg:grid-cols-[minmax(0,1.1fr)_minmax(18rem,0.9fr)]">
                <article class="dashboard-lift rounded-2xl border border-black/5 bg-white p-6 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-ink-900/60 sm:p-7">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-300">Profil tes terakhir</p>
                            <h2 class="mt-2 text-xl font-bold tracking-tight text-ink-950 dark:text-porcelain-50">Sebaran RIASEC Anda</h2>
                            <p class="mt-1 max-w-md text-sm leading-relaxed text-ink-500 dark:text-porcelain-300/70">Komposisi enam dimensi kepribadian dari sesi tes terbaru yang telah Anda selesaikan.</p>
                        </div>
                        <span class="rounded-lg bg-brand-50 px-2.5 py-1 font-mono text-xs font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300">{{ $totalCompleted }} sesi</span>
                    </div>

                    @if ($riasecDistribution !== [])
                        <div class="mt-5 h-72">
                            <canvas data-assessment-history-chart='@json($riasecDistribution)'></canvas>
                        </div>
                    @else
                        <div class="mt-6 flex min-h-72 flex-col items-center justify-center rounded-2xl border border-dashed border-black/10 bg-porcelain-50 px-6 text-center dark:border-white/10 dark:bg-ink-950/40">
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-300">
                                <x-heroicon-o-chart-pie class="h-6 w-6" aria-hidden="true" />
                            </span>
                            <p class="mt-4 font-semibold text-ink-800 dark:text-porcelain-100">Profil Anda akan muncul di sini</p>
                            <p class="mt-1 max-w-sm text-sm leading-relaxed text-ink-500 dark:text-porcelain-300/70">Selesaikan tes pertama untuk melihat sebaran enam dimensi RIASEC Anda.</p>
                        </div>
                    @endif
                </article>

                <article class="dashboard-lift rounded-2xl border border-black/5 bg-white p-6 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-ink-900/60 sm:p-7">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-300">Sesi terbaru</p>
                            <h2 class="mt-2 text-xl font-bold tracking-tight text-ink-950 dark:text-porcelain-50">Riwayat rekomendasi</h2>
                        </div>
                        <a href="{{ route('assessments.index') }}" class="text-sm font-semibold text-brand-600 transition hover:text-brand-500 dark:text-brand-300">Lihat semua</a>
                    </div>

                    @if ($recentHistory->isNotEmpty())
                        <ol class="mt-5 space-y-3">
                            @foreach ($recentHistory as $assessment)
                                {{-- Rangkuman sesi ditampilkan lewat popover saat baris disentuh
                                     atau difokus (bukan cuma hover mouse) — supaya isinya bisa
                                     dilihat sekilas tanpa harus membuka halaman hasil lengkap. --}}
                                <li class="relative" x-data="{ show: false }"
                                    @mouseenter="show = true" @mouseleave="show = false">
                                    <a href="{{ route('assessments.result', $assessment) }}"
                                       @focus="show = true" @blur="show = false"
                                       class="dashboard-history-row group flex items-center gap-3 rounded-xl border border-transparent p-3 transition hover:border-brand-500/20 hover:bg-brand-50/60 dark:hover:bg-brand-500/10">
                                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-ink-950 font-mono text-xs font-bold tracking-wider text-brand-200 dark:bg-porcelain-100 dark:text-ink-950">{{ $assessment->holland_code }}</span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate text-sm font-semibold text-ink-800 dark:text-porcelain-100">{{ $assessment->recommendedProgram?->full_name ?? 'Rekomendasi tidak tersedia' }}</span>
                                            <span class="mt-0.5 block text-xs text-ink-500 dark:text-porcelain-300/70">{{ $assessment->completed_at?->translatedFormat('d M Y') }}</span>
                                        </span>
                                        <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-ink-400 transition group-hover:text-brand-600 dark:text-porcelain-400" aria-hidden="true" />
                                    </a>

                                    <div x-show="show" x-cloak
                                         x-transition:enter="transition ease-out duration-150"
                                         x-transition:enter-start="opacity-0 scale-95 translate-y-1"
                                         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                                         x-transition:leave="transition ease-in duration-100"
                                         x-transition:leave-start="opacity-100 scale-100"
                                         x-transition:leave-end="opacity-0 scale-95"
                                         class="absolute bottom-full left-3 z-20 mb-2 w-64 max-w-[85vw] origin-bottom-left rounded-xl border border-black/10 bg-white p-4 text-left shadow-xl shadow-ink-950/15 dark:border-white/10 dark:bg-ink-900"
                                         role="tooltip">
                                        <p class="truncate text-xs font-bold uppercase tracking-wide text-ink-900 dark:text-porcelain-100">{{ $assessment->recommendedProgram?->full_name ?? 'Rekomendasi tidak tersedia' }}</p>

                                        <dl class="mt-3 space-y-2 text-xs">
                                            <div class="flex items-center justify-between gap-2">
                                                <dt class="text-ink-500 dark:text-porcelain-400">Tipe dominan</dt>
                                                <dd class="font-semibold text-ink-800 dark:text-porcelain-100">{{ \App\Support\Riasec::name($assessment->dominant_type) }} ({{ $assessment->dominant_type }})</dd>
                                            </div>
                                            <div class="flex items-center justify-between gap-2">
                                                <dt class="text-ink-500 dark:text-porcelain-400">Rerata rapor</dt>
                                                <dd class="font-semibold text-ink-800 dark:text-porcelain-100">{{ number_format($assessment->rapor_average, 1) }}</dd>
                                            </div>
                                        </dl>

                                        <p @class([
                                            'mt-3 flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-[11px] font-medium',
                                            'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300' => $assessment->matches_preference,
                                            'bg-amber-50 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300' => ! $assessment->matches_preference,
                                        ])>
                                            @if ($assessment->matches_preference)
                                                <x-heroicon-o-check-circle class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                                Sesuai pilihan prioritas pertama Anda
                                            @else
                                                <x-heroicon-o-information-circle class="h-3.5 w-3.5 shrink-0" aria-hidden="true" />
                                                Berbeda dari pilihan prioritas pertama Anda
                                            @endif
                                        </p>

                                        <span class="absolute left-6 top-full h-2.5 w-2.5 -translate-y-1/2 rotate-45 border-b border-r border-black/10 bg-white dark:border-white/10 dark:bg-ink-900"></span>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <div class="mt-6 rounded-2xl bg-porcelain-50 p-5 dark:bg-ink-950/40">
                            <p class="text-sm font-semibold text-ink-800 dark:text-porcelain-100">Belum ada sesi selesai</p>
                            <p class="mt-1 text-sm leading-relaxed text-ink-500 dark:text-porcelain-300/70">Hasil rekomendasi Anda akan tersimpan otomatis setelah tes selesai.</p>
                        </div>
                    @endif
                </article>
            </section>
        </div>
    </div>
</x-app-layout>
