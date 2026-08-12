<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Beranda
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                <div class="p-6 sm:p-8">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Selamat datang,</p>
                    <h3 class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ auth()->user()->name }}</h3>
                    <p class="mt-3 max-w-2xl text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        Sistem ini membantu Anda menemukan program studi yang paling sesuai dengan menggabungkan
                        nilai rapor, minat bakat berdasarkan model kepribadian RIASEC, urutan prioritas pilihan Anda,
                        serta data prospek kerja alumni. Perhitungan menggunakan metode
                        <span class="font-medium">Combined Compromise Solution (CoCoSo)</span>.
                    </p>

                    <div class="mt-6 flex flex-wrap gap-3">
                        @if ($unfinished)
                            <a href="{{ route('assessments.questionnaire', $unfinished) }}"
                               class="inline-flex items-center rounded-lg bg-amber-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-amber-700">
                                Lanjutkan Tes yang Belum Selesai
                            </a>
                        @else
                            <a href="{{ route('assessments.create') }}"
                               class="inline-flex items-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                                Mulai Tes Rekomendasi
                            </a>
                        @endif

                        <a href="{{ route('assessments.index') }}"
                           class="inline-flex items-center rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Lihat Riwayat Tes
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-3">
                @foreach ([
                    ['label' => 'Tes Selesai', 'value' => $totalCompleted, 'unit' => 'sesi'],
                    ['label' => 'Program Studi', 'value' => $programCount, 'unit' => 'alternatif'],
                    ['label' => 'Pernyataan Kuesioner', 'value' => $questionCount, 'unit' => 'butir'],
                ] as $stat)
                    <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-gray-800">
                        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $stat['value'] }}
                            <span class="text-sm font-normal text-gray-400">{{ $stat['unit'] }}</span>
                        </p>
                    </div>
                @endforeach
            </div>

            @if ($latest?->isCompleted())
                <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Rekomendasi terakhir Anda</p>
                            <h4 class="mt-1 text-xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $latest->recommendedProgram?->full_name ?? '-' }}
                            </h4>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Kode Holland <span class="font-semibold">{{ $latest->holland_code }}</span>
                                &middot; {{ $latest->completed_at?->translatedFormat('d F Y') }}
                            </p>
                        </div>
                        <a href="{{ route('assessments.result', $latest) }}"
                           class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                            Buka Hasil
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
