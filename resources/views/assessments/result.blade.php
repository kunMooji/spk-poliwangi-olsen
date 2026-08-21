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
            <section class="rounded-[2rem] bg-white p-2 shadow-sm ring-1 ring-gray-900/5 dark:bg-gray-800 dark:ring-white/10">
                <div class="overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-brand-600 to-brand-900 p-6 text-white shadow-inner sm:p-8">
                    <p class="text-sm font-medium text-brand-100">Rekomendasi Utama</p>
                    <h3 class="mt-1 text-3xl font-bold">{{ $assessment->recommendedProgram?->full_name ?? '-' }}</h3>
                    <p class="mt-2 max-w-3xl text-sm leading-relaxed text-brand-100">
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
                                        <span class="ms-1 font-mono text-xs text-gray-400">{{ $row['code'] }}</span>
                                        <span class="ms-1 text-xs text-gray-400">bobot {{ number_format($row['weight'], 2) }}</span>
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

            {{-- Bar chart RIASEC --}}
            <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Profil Kepribadian RIASEC</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Kode Holland Anda
                            <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $assessment->holland_code }}</span>
                            &mdash; tipe dominan
                            <span class="font-semibold text-gray-800 dark:text-gray-200">
                                {{ Riasec::name($assessment->dominant_type) }}
                            </span>.
                        </p>
                    </div>
                    <dl class="flex gap-5 text-sm">
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">Skor Likert Total</dt>
                            <dd class="mt-0.5 font-semibold tabular-nums text-gray-800 dark:text-gray-200">{{ array_sum($assessment->riasecScores()) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs uppercase tracking-wide text-gray-400 dark:text-gray-500">Tanggal Tes</dt>
                            <dd class="mt-0.5 font-semibold text-gray-800 dark:text-gray-200">
                                {{ $assessment->completed_at?->translatedFormat('d F Y, H:i') }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <div class="mt-5 h-72">
                    <canvas data-riasec-chart="{{ json_encode($chart) }}"></canvas>
                </div>

                <p class="mt-4 rounded-lg bg-gray-50 p-3 text-sm leading-relaxed text-gray-600 dark:bg-gray-900/40 dark:text-gray-300">
                    {{ Riasec::description($assessment->dominant_type) }}
                </p>
            </section>

            {{-- Rincian dimensi --}}
            <section>
                <div class="flex flex-wrap items-baseline justify-between gap-2">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rincian Dimensi</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Enam dimensi minat &amp; kepribadian menurut model Holland.</p>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach (Riasec::DIMENSIONS as $dimension)
                        @php($color = Riasec::color($dimension))
                        @php($isDominant = $dimension === $assessment->dominant_type)
                        <div @class([
                                'group relative flex flex-col overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-black/5 transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-800 dark:ring-white/10',
                                'ring-2' => $isDominant,
                            ])
                             @style(["--tw-ring-color: {$color}" => $isDominant])>
                            <div class="relative overflow-hidden p-5 text-white"
                                 style="background-color: {{ $color }}; background-image: linear-gradient(135deg, rgba(255,255,255,.20), rgba(0,0,0,.28));">
                                <span class="pointer-events-none absolute -right-2 -top-8 select-none text-9xl font-black leading-none text-white/10">
                                    {{ $dimension }}
                                </span>

                                <div class="relative">
                                    <p class="text-xl font-bold">{{ Riasec::name($dimension) }}</p>
                                    <p class="mt-0.5 text-xs font-medium uppercase tracking-wide text-white/75">
                                        {{ Riasec::label($dimension) }}
                                    </p>

                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-white/20 px-2.5 py-1 text-xs font-semibold backdrop-blur-sm">
                                            {{ number_format($percentages[$dimension], 1) }}%
                                        </span>
                                        @if ($isDominant)
                                            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold" style="color: {{ $color }}">
                                                Tipe Dominan
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-1 flex-col p-5">
                                <p class="flex-1 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                    {{ Riasec::description($dimension) }}
                                </p>

                                <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                                    <div class="h-full rounded-full transition-all"
                                         style="width: {{ $percentages[$dimension] }}%; background-color: {{ $color }}"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Tabel peringkat --}}
            <section class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Peringkat Program Studi</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Diurutkan dari yang paling sesuai dengan profil Anda.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                            <tr>
                                <th class="px-6 py-3">#</th>
                                <th class="px-6 py-3">Program Studi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($assessment->results as $result)
                                @php($isRecommended = $result->study_program_id === $assessment->recommended_program_id)
                                <tr class="{{ $isRecommended ? 'bg-brand-50 dark:bg-brand-900/20' : '' }} text-gray-700 dark:text-gray-300">
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
                                        @if ($result->study_program_id === $assessment->primary_program_id)
                                            <span class="ms-1 rounded-full bg-gray-200 px-2 py-0.5 text-[10px] font-semibold uppercase text-gray-700 dark:bg-gray-600 dark:text-gray-200">
                                                Pilihan 1
                                            </span>
                                        @endif
                                    </td>
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
