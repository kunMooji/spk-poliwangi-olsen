@use('App\Support\Riasec')

<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Hasil Rekomendasi Program Studi
            </h2>
            <span class="rounded-md bg-gray-100 px-3 py-1 font-mono text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                {{ $assessment->code }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            {{-- Kartu rekomendasi utama --}}
            <section class="overflow-hidden rounded-xl bg-gradient-to-br from-indigo-600 to-violet-700 p-6 text-white shadow-sm sm:p-8">
                <p class="text-sm font-medium text-indigo-100">Rekomendasi Utama</p>
                <h3 class="mt-1 text-3xl font-bold">{{ $assessment->recommendedProgram?->full_name ?? '-' }}</h3>
                <p class="mt-2 max-w-3xl text-sm leading-relaxed text-indigo-100">
                    {{ $assessment->recommendedProgram?->description }}
                </p>

                <div class="mt-5 flex flex-wrap gap-3 text-sm">
                    <span class="rounded-lg bg-white/15 px-3 py-1.5">
                        Nilai K&nbsp;: <strong>{{ number_format($assessment->recommended_k_value, 6) }}</strong>
                    </span>
                    <span class="rounded-lg bg-white/15 px-3 py-1.5">
                        Skala 0&ndash;100&nbsp;: <strong>{{ number_format($assessment->recommended_k_normal, 2) }}</strong>
                    </span>
                    <span class="rounded-lg bg-white/15 px-3 py-1.5">
                        Ambang batas&nbsp;: <strong>{{ number_format($assessment->threshold_used, 2) }}</strong>
                    </span>
                </div>
            </section>

            {{-- Penjelasan kesesuaian dengan pilihan --}}
            @if ($assessment->matches_preference)
                <x-alert type="success">
                    Program studi pilihan pertama Anda,
                    <strong>{{ $assessment->primaryProgram?->full_name }}</strong>,
                    mencapai nilai
                    <strong>{{ number_format($primaryResult?->k_normal ?? 0, 2) }}</strong>
                    dan telah memenuhi ambang batas
                    <strong>{{ number_format($assessment->threshold_used, 2) }}</strong>.
                    Karena itu pilihan Anda tetap dijadikan rekomendasi utama.
                </x-alert>
            @else
                <x-alert type="warning">
                    Program studi pilihan pertama Anda,
                    <strong>{{ $assessment->primaryProgram?->full_name ?? '-' }}</strong>,
                    memperoleh nilai
                    <strong>{{ number_format($primaryResult?->k_normal ?? 0, 2) }}</strong>
                    dan belum mencapai ambang batas
                    <strong>{{ number_format($assessment->threshold_used, 2) }}</strong>.
                    Sistem menyarankan
                    <strong>{{ $assessment->recommendedProgram?->full_name }}</strong>
                    yang memperoleh nilai tertinggi. Keputusan akhir tetap berada di tangan Anda.
                </x-alert>
            @endif

            {{-- Penjelasan: kriteria mana yang mengangkat dan menahan prodi rekomendasi --}}
            @if ($contributions !== [])
                <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
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
                            @endphp
                            <div>
                                <div class="flex flex-wrap items-baseline justify-between gap-2 text-sm">
                                    <span class="text-gray-700 dark:text-gray-300">
                                        {{ $row['name'] }}
                                        <span class="ms-1 font-mono text-xs text-gray-400">{{ $row['code'] }}</span>
                                        <span class="ms-1 text-xs text-gray-400">bobot {{ number_format($row['weight'], 2) }}</span>
                                    </span>
                                    <span class="tabular-nums text-gray-500 dark:text-gray-400">
                                        {{ $row['share'] }}% dari nilai
                                        <span class="ms-1 rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
                                              style="color: {{ $tone }}; background-color: {{ $tone }}1a">
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

            {{-- Penjelasan: kenapa pilihan pertama kalah --}}
            @if ($comparison !== [])
                <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Mengapa {{ $assessment->primaryProgram?->full_name }} tidak menjadi rekomendasi?
                    </h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Selisih terbesar antara pilihan pertama Anda dan prodi yang direkomendasikan.
                        Angka negatif berarti pilihan pertama Anda tertinggal pada kriteria tersebut.
                    </p>

                    <div class="mt-5 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-4 py-3">Kriteria</th>
                                    <th class="px-4 py-3 text-right">{{ $assessment->primaryProgram?->code }}</th>
                                    <th class="px-4 py-3 text-right">{{ $assessment->recommendedProgram?->code }}</th>
                                    <th class="px-4 py-3 text-right">Selisih</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($comparison as $row)
                                    <tr class="text-gray-700 dark:text-gray-300">
                                        <td class="px-4 py-3">
                                            {{ $row['name'] }}
                                            <span class="ms-1 font-mono text-xs text-gray-400">{{ $row['code'] }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row['subject'], 4) }}</td>
                                        <td class="px-4 py-3 text-right tabular-nums">{{ number_format($row['against'], 4) }}</td>
                                        <td class="px-4 py-3 text-right font-semibold tabular-nums {{ $row['delta'] < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                                            {{ $row['delta'] > 0 ? '+' : '' }}{{ number_format($row['delta'], 4) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <p class="mt-4 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        Rekomendasi ini bersifat saran. Bila Anda tetap yakin pada pilihan pertama, gunakan tabel di
                        atas untuk mengetahui bagian mana yang perlu Anda perkuat.
                    </p>
                </section>
            @endif

            <div class="grid gap-6 lg:grid-cols-5">
                {{-- Bar chart RIASEC --}}
                <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800 lg:col-span-3">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Profil Kepribadian RIASEC</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Kode Holland Anda
                        <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $assessment->holland_code }}</span>
                        &mdash; tipe dominan
                        <span class="font-semibold text-gray-800 dark:text-gray-200">
                            {{ Riasec::name($assessment->dominant_type) }}
                        </span>.
                    </p>

                    <div class="mt-5 h-72">
                        <canvas data-riasec-chart="{{ json_encode($chart) }}"></canvas>
                    </div>

                    <p class="mt-4 rounded-lg bg-gray-50 p-3 text-sm leading-relaxed text-gray-600 dark:bg-gray-900/40 dark:text-gray-300">
                        {{ Riasec::description($assessment->dominant_type) }}
                    </p>
                </section>

                {{-- Rincian dimensi --}}
                <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800 lg:col-span-2">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rincian Dimensi</h3>

                    <ul class="mt-4 space-y-3">
                        @foreach (Riasec::DIMENSIONS as $dimension)
                            <li>
                                <div class="flex items-center justify-between text-sm">
                                    <span class="font-medium text-gray-700 dark:text-gray-200">
                                        {{ Riasec::label($dimension) }}
                                    </span>
                                    <span class="tabular-nums text-gray-500 dark:text-gray-400">
                                        {{ number_format($percentages[$dimension], 2) }}%
                                    </span>
                                </div>
                                <div class="mt-1.5 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div class="h-full rounded-full"
                                         style="width: {{ $percentages[$dimension] }}%; background-color: {{ Riasec::color($dimension) }}"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    <dl class="mt-6 space-y-2 border-t border-gray-200 pt-4 text-sm dark:border-gray-700">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Skor Likert total</dt>
                            <dd class="font-medium text-gray-800 dark:text-gray-200">{{ array_sum($assessment->riasecScores()) }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Tanggal tes</dt>
                            <dd class="font-medium text-gray-800 dark:text-gray-200">
                                {{ $assessment->completed_at?->translatedFormat('d F Y, H:i') }}
                            </dd>
                        </div>
                    </dl>
                </section>
            </div>

            {{-- Tabel peringkat --}}
            <section class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="flex flex-wrap items-center justify-between gap-3 p-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Peringkat Program Studi</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Diurutkan berdasarkan nilai akhir K hasil perhitungan CoCoSo.
                        </p>
                    </div>
                    <a href="{{ route('assessments.calculation', $assessment) }}"
                       class="inline-flex items-center rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        Lihat Detail Perhitungan
                    </a>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">#</th>
                                <th class="px-6 py-3">Program Studi</th>
                                <th class="px-6 py-3">Kode Holland</th>
                                <th class="px-6 py-3 text-right">S<sub>i</sub></th>
                                <th class="px-6 py-3 text-right">P<sub>i</sub></th>
                                <th class="px-6 py-3 text-right">K<sub>i</sub></th>
                                <th class="px-6 py-3 text-right">Skala 0&ndash;100</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($assessment->results as $result)
                                @php $isRecommended = $result->study_program_id === $assessment->recommended_program_id; @endphp
                                <tr class="{{ $isRecommended ? 'bg-indigo-50 dark:bg-indigo-900/20' : '' }} text-gray-700 dark:text-gray-300">
                                    <td class="px-6 py-3 font-semibold">{{ $result->ranking }}</td>
                                    <td class="px-6 py-3">
                                        <span class="font-medium text-gray-900 dark:text-gray-100">
                                            {{ $result->studyProgram->full_name }}
                                        </span>
                                        @if ($isRecommended)
                                            <span class="ms-2 rounded-full bg-indigo-600 px-2 py-0.5 text-[10px] font-semibold uppercase text-white">
                                                Rekomendasi
                                            </span>
                                        @endif
                                        @if ($result->study_program_id === $assessment->primary_program_id)
                                            <span class="ms-1 rounded-full bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase text-gray-700 dark:bg-gray-600 dark:text-gray-200">
                                                Pilihan 1
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-3">{{ $result->studyProgram->holland_code }}</td>
                                    <td class="px-6 py-3 text-right tabular-nums">{{ number_format($result->s_value, 6) }}</td>
                                    <td class="px-6 py-3 text-right tabular-nums">{{ number_format($result->p_value, 6) }}</td>
                                    <td class="px-6 py-3 text-right font-semibold tabular-nums">{{ number_format($result->k_value, 6) }}</td>
                                    <td class="px-6 py-3 text-right tabular-nums">{{ number_format($result->k_normal, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <div class="flex flex-wrap gap-3">
                <a href="{{ route('assessments.index') }}"
                   class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                    Kembali ke Riwayat
                </a>
                <a href="{{ route('assessments.create') }}"
                   class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    Ikuti Tes Lagi
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
