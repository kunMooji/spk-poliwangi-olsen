@use('App\Support\Riasec')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-[10px] font-extrabold tracking-wide text-ink-900 dark:text-porcelain-50 sm:text-sm">
            Hasil Rekomendasi Program Studi
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-none space-y-6 px-5 sm:px-8 lg:px-10 xl:px-12">
            @if (session('success'))
                <x-alert type="success" class="mb-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span>{{ session('success') }}</span>
                        <span class="shrink-0 rounded-lg bg-white/60 px-3 py-1 font-mono text-xs font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-200">
                            {{ $assessment->code }}
                        </span>
                    </div>
                </x-alert>
            @endif

            <x-flash :show-success="false" />

            {{-- Kartu rekomendasi utama --}}
            <section class="relative overflow-hidden rounded-[1.75rem] border border-brand-100 bg-[radial-gradient(circle_at_86%_0%,rgba(179,227,236,.65),transparent_28%),linear-gradient(135deg,#ffffff,#eff9fb)] p-2 shadow-xl shadow-ink-950/5 dark:border-white/10 dark:bg-[radial-gradient(circle_at_78%_7%,rgba(27,137,163,.30),transparent_24%),linear-gradient(135deg,#071b29,#0b1627_55%,#14243a)] dark:shadow-2xl dark:shadow-ink-950/20">
                <div class="overflow-hidden rounded-[1.35rem] bg-ink-950 p-6 text-porcelain-50 shadow-inner sm:p-8">
                    <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-200">Rekomendasi Utama</p>
                    <h3 class="mt-3 text-3xl font-bold tracking-tight">{{ $assessment->recommendedProgram?->full_name ?? '-' }}</h3>
                    <p class="mt-3 line-clamp-3 max-w-3xl text-sm leading-relaxed text-porcelain-200/75">
                        {{ $assessment->recommendedProgram?->description }}
                    </p>
                </div>
            </section>

            {{-- Tabel peringkat --}}
            <section x-data="{ showAllRanks: false }" class="student-panel overflow-hidden">
                <div class="p-6">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Peringkat Program Studi</h3>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-200">
                            Top 5 dari {{ $assessment->results->count() }}
                        </span>
                    </div>

                    {{-- Pilihan prodi responden tampil sebagai kartu gradien (palet sama dengan
                         dasbor admin) agar menonjol; daftar peringkat di bawahnya tetap netral. --}}
                    <div class="student-panel-muted mt-5 rounded-xl border border-black/5 p-4 dark:border-white/10">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pilihan program studi Anda</p>
                        <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-3">
                            @foreach ($assessment->priorities->take(3) as $priority)
                                {{-- Sengaja memakai blok PHP, bukan bentuk inline berkurung: seksi ini
                                     berada di atas blok PHP lain, dan Blade akan menelan bentuk inline
                                     sampai penutup blok terdekat sehingga halaman error 500. --}}
                                @php
                                    $priorityResult = $assessment->results->firstWhere('study_program_id', $priority->study_program_id);
                                    $isPriorityRecommended = $priority->study_program_id === $assessment->recommended_program_id;
                                    $priorityUrl = $priority->studyProgram?->website_url;
                                @endphp
                                <x-gradient-card :index="$loop->index" :href="$priorityUrl" class="p-4"
                                                 :title="$priorityUrl ? 'Buka situs prodi' : null">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-bold uppercase tracking-wide text-white/85">Pilihan {{ $priority->priority_order }}</span>
                                        <span class="rounded-md bg-white/20 px-2 py-0.5 font-mono text-xs font-bold tabular-nums">
                                            #{{ $priorityResult?->ranking ?? '-' }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm font-bold leading-snug sm:text-base">
                                        {{ $priority->studyProgram?->full_name ?? '-' }}
                                        @if ($priorityUrl)
                                            <x-heroicon-o-arrow-top-right-on-square class="ms-0.5 inline h-3.5 w-3.5 align-[-1px] opacity-80" aria-hidden="true" />
                                        @endif
                                    </p>
                                    @if ($isPriorityRecommended)
                                        <span class="mt-2 inline-block rounded-md bg-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-ink-900">Rekomendasi utama</span>
                                    @endif
                                </x-gradient-card>
                            @endforeach
                        </div>
                    </div>

                </div>

                {{-- Daftar peringkat: kartu putih netral. Prodi yang punya situs menjadi
                     tautan ke situs jurusan/prodinya (<a> tanpa href bukan tautan). --}}
                @php
                    $tieGroups = $assessment->tieGroups();
                @endphp
                <ol class="space-y-2 px-6 pb-6">
                    @foreach ($assessment->results as $result)
                        @php
                            $isRecommended = $result->study_program_id === $assessment->recommended_program_id;
                            $tie = $tieGroups[$result->study_program_id] ?? null;
                            $priorityOrder = $assessment->priorities->firstWhere('study_program_id', $result->study_program_id)?->priority_order;
                            $websiteUrl = $result->studyProgram->website_url;
                        @endphp
                        <li @if ($result->ranking > 5) x-cloak x-show="showAllRanks" @endif>
                            <a @if ($websiteUrl) href="{{ $websiteUrl }}" target="_blank" rel="noopener noreferrer" title="Buka situs prodi" @endif
                               class="flex items-center gap-3 rounded-xl border px-4 py-3 {{ $isRecommended ? 'border-brand-200 bg-brand-50 dark:border-brand-500/30 dark:bg-brand-500/10' : 'border-black/5 bg-white dark:border-white/10 dark:bg-white/[0.04]' }} {{ $websiteUrl ? 'transition hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-md dark:hover:border-brand-500/50' : '' }}">
                                <span class="w-5 shrink-0 font-mono text-sm font-bold tabular-nums text-gray-500 dark:text-gray-400">{{ $result->ranking }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold leading-snug text-gray-900 dark:text-gray-100">
                                        {{ $result->studyProgram->full_name }}
                                        @if ($websiteUrl)
                                            <x-heroicon-o-arrow-top-right-on-square class="ms-0.5 inline h-3.5 w-3.5 align-[-1px] text-brand-600 dark:text-brand-300" aria-hidden="true" />
                                        @endif
                                    </p>
                                    @if ($isRecommended || $tie || $priorityOrder)
                                        <div class="mt-1.5 flex flex-wrap gap-1.5 text-[10px] font-semibold uppercase tracking-wide">
                                            @if ($isRecommended)
                                                <span class="rounded-md bg-brand-600 px-2 py-0.5 text-white">Rekomendasi</span>
                                            @endif
                                            @if ($tie)
                                                <span class="rounded-md bg-amber-100 px-2 py-0.5 text-amber-700 dark:bg-amber-400/15 dark:text-amber-100"
                                                      title="Selisih skornya terlalu kecil untuk dianggap berbeda. Peringkat {{ $tie['from'] }}–{{ $tie['to'] }} sebaiknya dibaca setara.">
                                                    Setara #{{ $tie['from'] }}&ndash;{{ $tie['to'] }}
                                                </span>
                                            @endif
                                            @if ($priorityOrder)
                                                <span class="rounded-md bg-gray-200 px-2 py-0.5 text-gray-700 dark:bg-gray-600 dark:text-gray-200">Pilihan {{ $priorityOrder }}</span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ol>

                @if ($assessment->results->count() > 5)
                    <div class="border-t border-gray-100 px-6 py-4 text-center dark:border-gray-700">
                        <button type="button" @click="showAllRanks = !showAllRanks"
                                class="inline-flex items-center gap-2 rounded-lg border border-brand-200 px-4 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50 dark:border-brand-700 dark:text-brand-200 dark:hover:bg-brand-900/20">
                            <span x-text="showAllRanks ? 'Sembunyikan daftar lengkap' : 'Lihat lebih banyak'"></span>
                            <svg class="h-4 w-4 transition-transform duration-200" :class="showAllRanks && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                            </svg>
                        </button>
                    </div>
                @endif
            </section>

            {{-- Posisi pilihan pertama & keyakinan peringkat teratas: angka tampil
                 sebagai tiga petak data, teks hanya untuk kesimpulannya. --}}
            @php
                $confident = $assessment->hasConfidentRecommendation();
                $hasMargin = $assessment->margin_to_runner_up !== null;
            @endphp
            <section class="student-panel dashboard-enter overflow-hidden">
                <dl class="grid grid-cols-1 divide-y divide-black/5 dark:divide-white/10 sm:divide-x sm:divide-y-0 {{ $hasMargin ? 'sm:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)_minmax(0,1.2fr)]' : 'sm:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]' }}">
                    <div class="px-5 py-4">
                        <dt class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-ink-500 dark:text-porcelain-300/70">Pilihan pertama Anda</dt>
                        <dd class="mt-1.5 text-base font-semibold leading-snug text-ink-900 dark:text-porcelain-50">{{ $assessment->primaryProgram?->full_name ?? '-' }}</dd>
                        <dd class="mt-0.5 text-xs text-ink-500 dark:text-porcelain-300/70">
                            Peringkat <span class="font-semibold text-ink-700 dark:text-porcelain-100">{{ $primaryResult?->ranking ?? '-' }}</span> dari {{ $assessment->results->count() }} prodi
                        </dd>
                    </div>

                    <div class="px-5 py-4">
                        <dt class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-ink-500 dark:text-porcelain-300/70">Skor relatif</dt>
                        <dd class="mt-1.5 font-mono text-2xl font-bold leading-none text-ink-900 dark:text-porcelain-50">
                            {{ number_format($primaryResult?->k_normal ?? 0, 2) }}<span class="ml-1 text-xs font-normal text-ink-500 dark:text-porcelain-300/70">/ 100</span>
                        </dd>
                    </div>

                    @if ($hasMargin)
                        <div class="px-5 py-4">
                            <dt class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-ink-500 dark:text-porcelain-300/70">Jarak ke peringkat 2</dt>
                            <dd class="mt-1.5 flex flex-wrap items-center gap-x-2.5 gap-y-1">
                                <span class="font-mono text-2xl font-bold leading-none text-ink-900 dark:text-porcelain-50">{{ number_format($assessment->margin_to_runner_up, 2) }}<span class="ml-1 text-xs font-normal text-ink-500 dark:text-porcelain-300/70">poin</span></span>
                                @if ($confident === false)
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800 dark:bg-amber-400/15 dark:text-amber-200">Praktis setara</span>
                                @elseif ($confident === true)
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 dark:bg-emerald-400/15 dark:text-emerald-200">Meyakinkan</span>
                                @endif
                            </dd>
                        </div>
                    @endif
                </dl>

                @if ($confident !== null)
                    <div class="border-t border-black/5 px-5 py-3.5 dark:border-white/10">
                        @if ($confident === false)
                            <p class="flex items-start gap-2 text-sm font-medium text-amber-800 dark:text-amber-200">
                                <x-heroicon-o-exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                                Dua prodi teratas nyaris setara &mdash; timbang keduanya.
                            </p>
                        @else
                            <p class="flex items-start gap-2 text-sm font-medium text-emerald-800 dark:text-emerald-200">
                                <x-heroicon-o-check-circle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                                Peringkat teratas cukup kuat.
                            </p>
                        @endif
                    </div>
                @endif
            </section>

            {{-- Penjelasan: kriteria mana yang mengangkat dan menahan prodi rekomendasi --}}
            @if ($contributions !== [])
                @php
                    $dominantRiasecImage = match (strtoupper((string) $assessment->dominant_type)) {
                        'R' => 'images/realistic char.png',
                        'I' => 'images/investigative char.png',
                        'A' => 'images/artistic char.png',
                        'S' => 'images/social char.png',
                        'E' => 'images/Enterprise char.png',
                        'C' => 'images/conventional char.png',
                        default => 'images/artistic char.png',
                    };
                @endphp
                <section class="student-panel result-explanation-enter overflow-hidden">
                    <div class="grid grid-cols-1 lg:grid-cols-[minmax(0,2fr)_minmax(17rem,1fr)]">
                        <div class="p-6 sm:p-7">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Mengapa {{ $assessment->recommendedProgram?->full_name }}?
                    </h3>

                    @if ($highlights['strengths'] !== [] || $highlights['weaknesses'] !== [])
                        <dl class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            @if ($highlights['strengths'] !== [])
                                <div>
                                    <dt class="inline font-medium">Unggul:</dt>
                                    <dd class="inline font-semibold text-emerald-700 dark:text-emerald-400">{{ implode(', ', $highlights['strengths']) }}</dd>
                                </div>
                            @endif
                            @if ($highlights['weaknesses'] !== [])
                                <div>
                                    <dt class="inline font-medium">Menahan nilai:</dt>
                                    <dd class="inline font-semibold text-rose-700 dark:text-rose-400">{{ implode(', ', $highlights['weaknesses']) }}</dd>
                                </div>
                            @endif
                        </dl>
                    @endif

                    <div class="mt-5 space-y-3">
                        @foreach ($contributions as $row)
                            @php
                                $tone = match ($row['level']) {
                                    'kuat' => '#059669',
                                    'lemah' => '#e11d48',
                                    default => '#6366f1',
                                };
                                $toneClasses = match ($row['level']) {
                                    'kuat' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-400',
                                    'lemah' => 'bg-rose-50 text-rose-700 dark:bg-rose-400/10 dark:text-rose-400',
                                    default => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-400/10 dark:text-indigo-400',
                                };
                            @endphp
                            <div>
                                <div class="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                                    <span class="text-gray-700 dark:text-gray-300">
                                        {{ $row['name'] }}
                                    </span>
                                    <span class="tabular-nums text-gray-500 dark:text-gray-400">
                                        {{ $row['share'] }}% dari nilai
                                        <span class="ms-1 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase {{ $toneClasses }}">
                                            {{ $row['level'] }}
                                        </span>
                                    </span>
                                </div>
                                <div class="mt-1 h-2 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div class="h-full rounded-full"
                                         style="width: {{ min(100, max(0, $row['normalized'] * 100)) }}%; background-color: {{ $tone }}"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                        </div>

                        <figure class="relative min-h-64 overflow-hidden bg-porcelain-50 dark:bg-ink-800 lg:min-h-full">
                            <img src="{{ asset($dominantRiasecImage) }}"
                                 alt="Ilustrasi tipe kepribadian RIASEC {{ Riasec::name($assessment->dominant_type) }}"
                                 class="absolute inset-0 h-full w-full object-contain"
                                 loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-ink-950/45 via-transparent to-transparent"></div>
                            <figcaption class="absolute inset-x-0 bottom-0 p-5 text-sm font-semibold text-white drop-shadow-sm">
                                Profil dominan Anda: {{ Riasec::name($assessment->dominant_type) }}.
                            </figcaption>
                        </figure>
                    </div>
                </section>
            @endif

            {{-- Profil RIASEC: grafik di kiri, nilai tiap dimensi di kanan. --}}
            <section class="student-panel p-6 sm:p-7">
                {{-- grid-cols-1 = minmax(0,1fr): tanpa kolom eksplisit, kolom implisit di HP
                     melebar mengikuti kanvas grafik sehingga grafik & kartu dimensi melewati panel. --}}
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-[minmax(0,1.25fr)_minmax(17rem,0.75fr)] lg:items-start">
                    <div>
                        <div class="flex flex-wrap items-start justify-between gap-4">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Profil Kepribadian RIASEC</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Kode Holland Anda <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $assessment->holland_code }}</span>.
                                </p>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">{{ $assessment->completed_at?->translatedFormat('d M Y') }}</p>
                        </div>
                        <div class="mt-5 h-72 pr-0 lg:pr-4">
                            <canvas data-riasec-chart="{{ json_encode($chart) }}"></canvas>
                        </div>
                    </div>

                    <aside class="border-t border-gray-100 pt-5 dark:border-gray-700 lg:flex lg:h-[356px] lg:flex-col lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Rincian dimensi</h4>
                        <dl class="mt-4 grid gap-2.5 lg:min-h-0 lg:flex-1 lg:grid-rows-6">
                            @foreach (Riasec::DIMENSIONS as $dimension)
                                @php($color = Riasec::color($dimension))
                                @php($isDominant = $dimension === $assessment->dominant_type)
                                <div class="riasec-dimension-card" style="--dimension-color: {{ $color }}">
                                    <div class="flex items-center gap-3">
                                        <span class="riasec-dimension-index flex h-7 w-7 shrink-0 items-center justify-center rounded-lg font-mono text-xs font-bold">
                                            {{ $loop->iteration }}
                                        </span>
                                        <dt class="riasec-dimension-name min-w-0 flex-1 text-sm font-semibold">
                                            {{ Riasec::name($dimension) }}
                                            @if ($isDominant)
                                                <span class="riasec-dimension-dominant ms-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">Dominan</span>
                                            @endif
                                        </dt>
                                        <dd class="riasec-dimension-score rounded-lg px-2.5 py-1 font-mono text-sm font-bold tabular-nums">
                                            {{ number_format($percentages[$dimension], 1) }}%
                                        </dd>
                                    </div>
                                    <p class="riasec-dimension-description ms-10 mt-1 hidden text-xs leading-relaxed sm:block" title="{{ Riasec::shortDescription($dimension) }}">
                                        {{ Riasec::shortDescription($dimension) }}
                                    </p>
                                </div>
                            @endforeach
                        </dl>
                    </aside>
                </div>
            </section>

            {{-- Kuesioner umpan balik pengguna --}}
            @if ($feedbackFormUrl = config('services.feedback_form.url'))
                <section class="student-panel p-6 sm:p-7">
                    <div class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-start gap-4">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-700 dark:bg-brand-900/30 dark:text-brand-200">
                                <x-heroicon-o-clipboard-document-list class="h-6 w-6" aria-hidden="true" />
                            </span>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Bantu kami dengan mengisi kuesioner</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                    Bantu kami menyempurnakan sistem. Hanya beberapa menit.
                                </p>
                            </div>
                        </div>
                        <a href="{{ $feedbackFormUrl }}" target="_blank" rel="noopener noreferrer"
                           class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                            Isi Kuesioner
                            <x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" aria-hidden="true" />
                        </a>
                    </div>
                </section>
            @endif

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('assessments.index') }}"
                   class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                    Kembali ke Riwayat
                </a>
                <a href="{{ route('assessments.print', $assessment) }}" target="_blank"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 9V4h12v5M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2M6 14h12v6H6v-6z" />
                    </svg>
                    Cetak / Simpan PDF
                </a>
                <a href="{{ route('assessments.create') }}"
                   class="rounded-lg bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                    Ikuti Tes Lagi
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
