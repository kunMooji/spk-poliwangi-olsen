@props([
    'current' => 1,
    'reactive' => false,
    'fractionExpression' => null,
    'stickyTop' => 'lg:top-0',
    'clickable' => false,
    'tipTitle' => 'Jawablah setiap pernyataan sesuai dengan kondisi diri Anda.',
    'tipBody' => 'Tidak ada jawaban benar atau salah.',
])

@php
    $steps = [
        1 => 'Biodata',
        2 => 'Nilai Rapor',
        3 => 'Prioritas Prodi',
        4 => 'Kepribadian (RIASEC)',
    ];
    $total = count($steps);

    // Persentase keseluruhan dihitung dari langkah yang sudah dilewati
    // ditambah kemajuan pecahan di dalam langkah yang sedang berjalan
    // (mis. jumlah soal RIASEC yang sudah terjawab), supaya kartu ini
    // benar-benar mencerminkan progres end-to-end, bukan cuma per halaman.
    $fractionOverallExpr = "((({$current} - 1) + ({$fractionExpression})) / {$total}) * 100";
@endphp

{{--
    Kartu kemajuan tes. Tinggi penuh 1 layar (h-screen) dan lebar 1/4 layar
    pada breakpoint lg, menempel (lg:sticky) di sisi kiri konten supaya
    calon mahasiswa selalu tahu posisinya dalam alur 5 tahap tanpa perlu
    menggulir ke atas. Mode `reactive` mengikuti $store.wizard.step (dipakai
    di formulir bertahap create.blade.php) tanpa perlu muat ulang halaman;
    mode statis (`:current`) dipakai di halaman yang sudah berdiri sendiri
    seperti kuesioner RIASEC — di sana `fraction-expression` (ekspresi
    Alpine dalam skala 0..1, mis. "answeredCount / total") menambal
    kemajuan di dalam step tersebut supaya persentasenya tetap naik
    seiring soal yang dijawab, bukan diam di satu angka.
--}}
<div x-data="{}" class="flex min-h-[30rem] w-full flex-col rounded-2xl border border-brand-100 bg-white p-6 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.06] dark:shadow-black/10 lg:sticky {{ $stickyTop }} lg:h-[calc(100vh-6rem)] lg:w-full">

    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
            Progress Assessment
        </h3>
        @if ($reactive)
            <span class="shrink-0 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400"
                  x-text="`${Math.round(($store.wizard.step / {{ $total }}) * 100)}%`"></span>
        @elseif ($fractionExpression)
            <span class="shrink-0 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400"
                  x-text="`${Math.round({{ $fractionOverallExpr }})}%`"></span>
        @else
            <span class="shrink-0 rounded-full bg-brand-50 px-2 py-0.5 text-xs font-semibold text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                {{ round($current / $total * 100) }}%
            </span>
        @endif
    </div>

    <div class="mt-3">
        <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
            @if ($reactive)
                <div class="h-full rounded-full bg-brand-600 transition-all duration-300"
                     :style="`width: ${($store.wizard.step / {{ $total }}) * 100}%`"></div>
            @elseif ($fractionExpression)
                <div class="h-full rounded-full bg-brand-600 transition-all duration-300"
                     :style="`width: ${ {{ $fractionOverallExpr }} }%`"></div>
            @else
                <div class="h-full rounded-full bg-brand-600" style="width: {{ $current / $total * 100 }}%"></div>
            @endif
        </div>
        <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-500">
            @if ($reactive)
                <span x-text="`Bagian ${$store.wizard.step} dari {{ $total }}`"></span>
            @else
                Bagian {{ $current }} dari {{ $total }}
            @endif
        </p>
    </div>

    <ol class="mt-5">
        @foreach ($steps as $number => $label)
            <li class="flex gap-3">
                {{-- Kolom ikon: bulatan nomor + garis penghubung yang meregang
                     mengikuti tinggi konten label di sampingnya (items-stretch
                     bawaan flex), supaya jarak antar bulatan selalu pas tanpa
                     perlu angka spasi tetap. --}}
                <div class="flex flex-col items-center">
                    @if ($reactive)
                        <span class="relative flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                              :class="{
                                  'bg-emerald-500 text-white': $store.wizard.step > {{ $number }},
                                  'bg-brand-600 text-white': $store.wizard.step === {{ $number }},
                                  'border-2 border-gray-200 text-gray-400 dark:border-gray-600 dark:text-gray-500': $store.wizard.step < {{ $number }},
                              }">
                            <template x-if="$store.wizard.step > {{ $number }}">
                                <x-heroicon-s-check class="h-4 w-4" />
                            </template>
                            <template x-if="$store.wizard.step <= {{ $number }}">
                                <span>{{ $number }}</span>
                            </template>
                        </span>
                    @else
                        <span @class([
                                'relative flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                'bg-emerald-500 text-white' => $number < $current,
                                'bg-brand-600 text-white' => $number === $current,
                                'border-2 border-gray-200 text-gray-400 dark:border-gray-600 dark:text-gray-500' => $number > $current,
                            ])>
                            @if ($number < $current)
                                <x-heroicon-s-check class="h-4 w-4" />
                            @else
                                {{ $number }}
                            @endif
                        </span>
                    @endif

                    @if ($number < $total)
                        @if ($reactive)
                            <span class="mt-1 w-px flex-1 rounded-full"
                                  :class="$store.wizard.step > {{ $number }} ? 'bg-emerald-500' : 'bg-gray-200 dark:bg-gray-600'"></span>
                        @else
                            <span @class([
                                    'mt-1 w-px flex-1 rounded-full',
                                    'bg-emerald-500' => $number < $current,
                                    'bg-gray-200 dark:bg-gray-600' => $number >= $current,
                                ])></span>
                        @endif
                    @endif
                </div>

                <div class="min-w-0 pb-5">
                    @if ($reactive)
                        {{--
                            Semua langkah 1-3 berbagi satu Alpine scope dengan
                            formulirnya (create.blade.php), jadi berpindah
                            langkah cukup mengganti $store.wizard.step — tidak
                            ada reload, tidak ada data yang hilang. Langkah 4
                            (RIASEC) sengaja tidak ikut diklik di sini karena
                            butuh ID sesi tes yang baru ada setelah formulir
                            ini disimpan.
                        --}}
                        @if ($number < $total)
                            <button type="button" @click="$store.wizard.step = {{ $number }}"
                                    class="block text-left text-sm font-medium transition hover:text-brand-600 dark:hover:text-brand-400"
                                    :class="$store.wizard.step >= {{ $number }}
                                        ? 'text-gray-900 dark:text-gray-100'
                                        : 'text-gray-400 dark:text-gray-500'">{{ $label }}</button>
                        @else
                            <p class="text-sm font-medium"
                               :class="$store.wizard.step >= {{ $number }}
                                    ? 'text-gray-900 dark:text-gray-100'
                                    : 'text-gray-400 dark:text-gray-500'">{{ $label }}</p>
                        @endif
                        <p class="text-xs"
                           :class="{
                               'text-emerald-600 dark:text-emerald-400': $store.wizard.step > {{ $number }},
                               'text-brand-600 dark:text-brand-400': $store.wizard.step === {{ $number }},
                               'text-gray-400 dark:text-gray-500': $store.wizard.step < {{ $number }},
                           }">
                            <template x-if="$store.wizard.step > {{ $number }}"><span>Selesai</span></template>
                            <template x-if="$store.wizard.step === {{ $number }}"><span>Sedang dikerjakan</span></template>
                            <template x-if="$store.wizard.step < {{ $number }}"><span>Belum dikerjakan</span></template>
                        </p>
                    @else
                        @if ($clickable && $number < $current)
                            <a href="{{ route('assessments.create', ['resume' => 1, 'step' => $number]) }}"
                               class="block text-sm font-medium transition hover:text-brand-600 dark:hover:text-brand-400">
                        @endif
                        <p @class([
                                'text-sm font-medium',
                                'text-gray-900 dark:text-gray-100' => $number <= $current,
                                'text-gray-400 dark:text-gray-500' => $number > $current,
                            ])>{{ $label }}</p>
                        <p @class([
                                'text-xs',
                                'text-emerald-600 dark:text-emerald-400' => $number < $current,
                                'text-brand-600 dark:text-brand-400' => $number === $current,
                                'text-gray-400 dark:text-gray-500' => $number > $current,
                            ])>
                            @if ($number < $current)
                                Selesai
                            @elseif ($number === $current)
                                Sedang dikerjakan
                            @else
                                Belum dikerjakan
                            @endif
                        </p>
                        @if ($clickable && $number < $current)
                            </a>
                        @endif
                    @endif
                </div>
            </li>
        @endforeach
    </ol>

    <div class="mt-auto pt-6">
        <div class="flex gap-2.5 rounded-lg bg-brand-50 p-3.5 dark:bg-brand-500/10">
            <x-heroicon-o-light-bulb class="mt-0.5 h-4 w-4 shrink-0 text-brand-500 dark:text-brand-400" />
            <p class="text-xs leading-relaxed text-brand-700 dark:text-brand-300">
                <span class="font-semibold">{{ $tipTitle }}</span>
                <br>
                <span class="text-brand-500 dark:text-brand-400">{{ $tipBody }}</span>
            </p>
        </div>
    </div>
</div>
