@use('App\Support\Riasec')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-display text-[10px] font-bold uppercase tracking-wide text-ink-900 dark:text-porcelain-50 sm:text-sm">
                Hasil Rekomendasi Program Studi
            </h2>
            <span class="rounded-lg bg-brand-50 px-3 py-1 font-mono text-xs font-bold text-brand-700 dark:bg-brand-500/10 dark:text-brand-200">
                {{ $assessment->code }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-none space-y-6 px-5 sm:px-8 lg:px-10 xl:px-12">
            <x-flash />

            {{-- Kartu rekomendasi utama --}}
            <section class="relative overflow-hidden rounded-[1.75rem] border border-brand-100 bg-[radial-gradient(circle_at_86%_0%,rgba(179,227,236,.65),transparent_28%),linear-gradient(135deg,#ffffff,#eff9fb)] p-2 shadow-xl shadow-ink-950/5 dark:border-white/10 dark:bg-[radial-gradient(circle_at_78%_7%,rgba(27,137,163,.30),transparent_24%),linear-gradient(135deg,#071b29,#0b1627_55%,#14243a)] dark:shadow-2xl dark:shadow-ink-950/20">
                <div class="overflow-hidden rounded-[1.35rem] bg-ink-950 p-6 text-porcelain-50 shadow-inner sm:p-8">
                    <p class="font-mono text-[10px] font-bold uppercase tracking-[0.18em] text-brand-200">Rekomendasi Utama</p>
                    <h3 class="mt-3 text-3xl font-bold tracking-tight">{{ $assessment->recommendedProgram?->full_name ?? '-' }}</h3>
                    <p class="mt-3 max-w-3xl text-sm leading-relaxed text-porcelain-200/75">
                        {{ $assessment->recommendedProgram?->description }}
                    </p>
                </div>
            </section>

            {{-- Penjelasan kesesuaian dengan pilihan --}}
            @if ($assessment->matches_preference)
                <x-alert type="success">
                    Program studi pilihan pertama Anda,
                    <strong>{{ $assessment->primaryProgram?->full_name }}</strong>,
                    sekaligus menempati peringkat teratas dengan nilai
                    <strong>{{ number_format($primaryResult?->k_normal ?? 0, 2) }}</strong>
                    dari {{ $assessment->results->count() }} program studi.
                    Peringkat ini murni hasil perhitungan seluruh kriteria &mdash;
                    bukan karena Anda menempatkannya sebagai pilihan pertama.
                </x-alert>
            @else
                <x-alert type="warning">
                    Program studi pilihan pertama Anda,
                    <strong>{{ $assessment->primaryProgram?->full_name ?? '-' }}</strong>,
                    berada di peringkat
                    <strong>{{ $primaryResult?->ranking ?? '-' }}</strong>
                    dari {{ $assessment->results->count() }} program studi dengan nilai
                    <strong>{{ number_format($primaryResult?->k_normal ?? 0, 2) }}</strong>.
                    Nilai tertinggi diraih
                    <strong>{{ $assessment->recommendedProgram?->full_name }}</strong>
                    ({{ number_format($recommendedResult?->k_normal ?? 0, 2) }}).
                    Minat Anda sudah ikut diperhitungkan sebagai salah satu kriteria,
                    dan keputusan akhir tetap berada di tangan Anda.
                </x-alert>
            @endif

            {{-- Penjelasan: kriteria mana yang mengangkat dan menahan prodi rekomendasi --}}
            @if ($contributions !== [])
                <section class="student-panel result-explanation-enter p-6 sm:p-7">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Mengapa {{ $assessment->recommendedProgram?->full_name }}?
                    </h3>

                    @if ($highlights['strengths'] !== [] || $highlights['weaknesses'] !== [])
                        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                            @if ($highlights['strengths'] !== [])
                                Program studi ini unggul pada
                                <strong class="text-emerald-700 dark:text-emerald-400">{{ implode(', ', $highlights['strengths']) }}</strong>.
                            @endif
                            @if ($highlights['weaknesses'] !== [])
                                Yang menahan nilainya adalah
                                <strong class="text-rose-700 dark:text-rose-400">{{ implode(', ', $highlights['weaknesses']) }}</strong>
                                &mdash; bagian inilah yang perlu Anda pertimbangkan sebelum memutuskan.
                            @endif
                        </p>
                    @endif

                    <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                        Batang di bawah menunjukkan sumbangan tiap kriteria terhadap nilai prodi ini, yaitu
                        bobot kriteria dikalikan nilai ternormalisasinya.
                    </p>

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
                </section>
            @endif

            {{-- Profil RIASEC: grafik di kiri, nilai tiap dimensi di kanan. --}}
            <section class="student-panel p-6 sm:p-7">
                <div class="grid gap-6 lg:grid-cols-[minmax(0,1.25fr)_minmax(17rem,0.75fr)] lg:items-start">
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

                    <aside class="border-t border-gray-100 pt-5 dark:border-gray-700 lg:border-l lg:border-t-0 lg:pl-6 lg:pt-0">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-gray-100">Rincian dimensi</h4>
                        <dl class="mt-4 space-y-4">
                            @foreach (Riasec::DIMENSIONS as $dimension)
                                @php($color = Riasec::color($dimension))
                                @php($isDominant = $dimension === $assessment->dominant_type)
                                <div>
                                    <div class="flex items-center gap-3">
                                        <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background-color: {{ $color }}"></span>
                                        <dt class="min-w-0 flex-1 text-sm text-gray-700 dark:text-gray-200">
                                            {{ Riasec::name($dimension) }}
                                            @if ($isDominant)
                                                <span class="ms-1 text-xs font-semibold" style="color: {{ $color }}">Dominan</span>
                                            @endif
                                        </dt>
                                        <dd class="font-mono text-sm font-bold tabular-nums text-gray-900 dark:text-gray-100">{{ number_format($percentages[$dimension], 1) }}%</dd>
                                    </div>
                                    <p class="ms-5 mt-1 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                                        {{ Riasec::shortDescription($dimension) }}
                                    </p>
                                </div>
                            @endforeach
                        </dl>
                    </aside>
                </div>
            </section>

            {{-- Tabel peringkat --}}
            <section x-data="{ showAllRanks: false }" class="student-panel overflow-hidden">
                <div class="p-6">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Peringkat Program Studi</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Lima program studi dengan kecocokan tertinggi dari {{ $assessment->results->count() }} alternatif.
                            </p>
                        </div>
                        <span class="rounded-full bg-brand-50 px-3 py-1 text-xs font-semibold text-brand-700 dark:bg-brand-900/30 dark:text-brand-200">
                            Top 5 rekomendasi
                        </span>
                    </div>

                    <div class="student-panel-muted mt-5 rounded-xl border border-black/5 p-4 dark:border-white/10">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Pilihan program studi Anda</p>
                        <div class="mt-3 grid gap-3 md:grid-cols-3">
                            @foreach ($assessment->priorities->take(3) as $priority)
                                @php($priorityResult = $assessment->results->firstWhere('study_program_id', $priority->study_program_id))
                                @php($isPriorityRecommended = $priority->study_program_id === $assessment->recommended_program_id)
                                <div class="rounded-lg border border-black/5 bg-white p-3 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.04]">
                                    <div class="flex items-center justify-between gap-2">
                                        <span class="text-xs font-semibold uppercase tracking-wide text-brand-700 dark:text-brand-300">Pilihan {{ $priority->priority_order }}</span>
                                        <span class="font-mono text-xs font-bold tabular-nums text-gray-600 dark:text-gray-300">
                                            #{{ $priorityResult?->ranking ?? '-' }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-sm font-semibold leading-snug text-gray-900 dark:text-gray-100">
                                        {{ $priority->studyProgram?->full_name ?? '-' }}
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Peringkat {{ $priorityResult?->ranking ?? '-' }} dari {{ $assessment->results->count() }} prodi
                                        @if ($isPriorityRecommended)
                                            <span class="font-semibold text-brand-700 dark:text-brand-300">&middot; Rekomendasi utama</span>
                                        @endif
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Label Pilihan 1, 2, dan 3 tetap ditampilkan pada daftar, termasuk bila peringkatnya di luar lima besar.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="student-table min-w-full divide-y divide-black/5 text-sm dark:divide-white/10">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">#</th>
                                <th class="px-6 py-3">Program Studi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($assessment->results as $result)
                                @php($isRecommended = $result->study_program_id === $assessment->recommended_program_id)
                                @php($priorityOrder = $assessment->priorities->firstWhere('study_program_id', $result->study_program_id)?->priority_order)
                                <tr @if ($result->ranking > 5) x-cloak x-show="showAllRanks" @endif
                                    class="{{ $isRecommended ? 'bg-brand-50 dark:bg-brand-900/20' : '' }} text-gray-700 dark:text-gray-300">
                                    <td class="px-6 py-3 font-semibold">{{ $result->ranking }}</td>
                                    <td class="px-6 py-3">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $result->studyProgram->full_name }}
                                        </span>
                                        @if ($isRecommended)
                                            <span class="ms-2 rounded-full bg-brand-600 px-2 py-0.5 text-[10px] font-semibold uppercase text-white">
                                                Rekomendasi
                                            </span>
                                        @endif
                                        @if ($priorityOrder)
                                            <span class="ms-1 rounded-full bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase text-gray-700 dark:bg-gray-600 dark:text-gray-200">
                                                Pilihan {{ $priorityOrder }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

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
