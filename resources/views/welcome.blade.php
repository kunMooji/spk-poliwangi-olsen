@use('App\Support\Riasec')

<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Sistem pendukung keputusan pemilihan program studi Politeknik Negeri Banyuwangi, menggabungkan tes minat bakat RIASEC dengan metode CoCoSo.">

        <title>{{ config('app.name') }} &mdash; Politeknik Negeri Banyuwangi</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="bg-white font-sans text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100">
        {{-- Bilah navigasi --}}
        <header class="sticky top-0 z-30 border-b border-gray-100 bg-white/90 backdrop-blur dark:border-gray-800 dark:bg-gray-900/90">
            <nav class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                <a href="{{ route('home') }}" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white">SPK</span>
                    <span class="hidden text-sm font-semibold leading-tight sm:block">
                        {{ config('app.name') }}
                        <span class="block text-xs font-normal text-gray-500 dark:text-gray-400">Politeknik Negeri Banyuwangi</span>
                    </span>
                </a>

                <div class="hidden items-center gap-8 text-sm font-medium text-gray-600 md:flex dark:text-gray-300">
                    <a href="#cara-kerja" class="transition hover:text-indigo-600 dark:hover:text-indigo-400">Cara Kerja</a>
                    <a href="#kriteria" class="transition hover:text-indigo-600 dark:hover:text-indigo-400">Kriteria</a>
                    <a href="#riasec" class="transition hover:text-indigo-600 dark:hover:text-indigo-400">RIASEC</a>
                    <a href="#prodi" class="transition hover:text-indigo-600 dark:hover:text-indigo-400">Program Studi</a>
                </div>

                <div class="flex items-center gap-2">
                    @auth
                        <a href="{{ route('dashboard') }}"
                           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            Buka Dasbor
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-800">
                            Masuk
                        </a>
                        <a href="{{ route('register') }}"
                           class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                            Daftar
                        </a>
                    @endauth
                </div>
            </nav>
        </header>

        <main>
            {{-- Hero --}}
            <section class="relative overflow-hidden">
                <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 via-white to-violet-50 dark:from-gray-900 dark:via-gray-900 dark:to-indigo-950"></div>

                <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
                    <div class="grid items-center gap-12 lg:grid-cols-2">
                        <div>
                            <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                Metode CoCoSo &middot; Kepribadian RIASEC
                            </span>

                            <h1 class="mt-5 text-4xl font-extrabold leading-tight tracking-tight sm:text-5xl">
                                Pilih program studi dengan
                                <span class="text-indigo-600 dark:text-indigo-400">dasar yang terukur</span>,
                                bukan sekadar ikut teman.
                            </h1>

                            <p class="mt-5 max-w-xl text-lg leading-relaxed text-gray-600 dark:text-gray-300">
                                Sistem ini menimbang nilai rapor, minat bakat, urutan pilihan Anda, dan data serapan
                                kerja alumni sekaligus &mdash; lalu memberi peringkat program studi beserta
                                <strong class="font-semibold text-gray-900 dark:text-gray-100">alasan di balik setiap angkanya</strong>.
                            </p>

                            <div class="mt-8 flex flex-wrap gap-3">
                                @auth
                                    <a href="{{ route('dashboard') }}"
                                       class="inline-flex items-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                                        Buka Dasbor Saya
                                    </a>
                                @else
                                    <a href="{{ route('register') }}"
                                       class="inline-flex items-center rounded-xl bg-indigo-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                                        Mulai Tes Gratis
                                    </a>
                                    <a href="{{ route('login') }}"
                                       class="inline-flex items-center rounded-xl border border-gray-300 px-6 py-3 text-sm font-semibold text-gray-700 transition hover:bg-white dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-800">
                                        Sudah punya akun
                                    </a>
                                @endauth
                            </div>

                            <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                                Pengisian sekitar 10 menit. Hasilnya tersimpan dan dapat dibuka kembali kapan saja.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            @foreach ([
                                ['value' => $programs->count(), 'label' => 'Program studi dibandingkan'],
                                ['value' => $criteria->count(), 'label' => 'Kriteria penilaian'],
                                ['value' => $questionCount, 'label' => 'Pernyataan minat bakat'],
                                ['value' => count(Riasec::DIMENSIONS), 'label' => 'Dimensi kepribadian'],
                            ] as $stat)
                                <div class="rounded-2xl border border-gray-100 bg-white/80 p-6 shadow-sm backdrop-blur dark:border-gray-800 dark:bg-gray-800/80">
                                    <p class="text-4xl font-extrabold text-indigo-600 dark:text-indigo-400">{{ $stat['value'] }}</p>
                                    <p class="mt-2 text-sm leading-snug text-gray-600 dark:text-gray-300">{{ $stat['label'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>

            {{-- Cara kerja --}}
            <section id="cara-kerja" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="max-w-2xl">
                    <h2 class="text-3xl font-bold tracking-tight">Bagaimana cara kerjanya?</h2>
                    <p class="mt-3 text-gray-600 dark:text-gray-300">
                        Tiga langkah, satu kali duduk. Anda tidak perlu tahu rumusnya &mdash; tetapi seluruh
                        perhitungannya tetap dapat Anda buka bila ingin memeriksanya.
                    </p>
                </div>

                <ol class="mt-12 grid gap-6 md:grid-cols-3">
                    @foreach ([
                        [
                            'title' => 'Isi biodata & nilai rapor',
                            'body' => 'Masukkan nilai enam mata pelajaran dan urutkan program studi sesuai minat Anda. Urutan ini ikut diperhitungkan, jadi pilihan Anda tetap punya bobot.',
                        ],
                        [
                            'title' => 'Kerjakan kuesioner minat bakat',
                            'body' => $questionCount.' pernyataan dengan skala 1 sampai 5. Hasilnya adalah profil kepribadian RIASEC Anda, lengkap dengan kode Holland tiga huruf.',
                        ],
                        [
                            'title' => 'Terima peringkat & alasannya',
                            'body' => 'Sistem memberi peringkat seluruh program studi, menjelaskan kriteria mana yang mengangkat dan menahan tiap prodi, serta membandingkannya dengan pilihan pertama Anda.',
                        ],
                    ] as $index => $step)
                        <li class="relative rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-800">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-600 text-lg font-bold text-white">
                                {{ $index + 1 }}
                            </span>
                            <h3 class="mt-4 text-lg font-semibold">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">{{ $step['body'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </section>

            {{-- Kriteria --}}
            <section id="kriteria" class="border-y border-gray-100 bg-gray-50 py-20 dark:border-gray-800 dark:bg-gray-800/40">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                        <h2 class="text-3xl font-bold tracking-tight">Apa saja yang dinilai?</h2>
                        <p class="mt-3 text-gray-600 dark:text-gray-300">
                            Setiap kriteria punya bobot tersendiri yang ditetapkan bersama pihak akademik. Bobot yang
                            berlaku saat Anda mengerjakan tes disimpan menyatu dengan hasil, sehingga hasil lama tidak
                            pernah berubah meski bobotnya kelak diperbarui.
                        </p>
                    </div>

                    <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($criteria as $criterion)
                            <div class="rounded-xl border border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <span class="font-mono text-xs text-gray-400">{{ $criterion->code }}</span>
                                        <h3 class="mt-0.5 font-semibold leading-snug">{{ $criterion->name }}</h3>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                                        {{ number_format($criterion->weight * 100, 0) }}%
                                    </span>
                                </div>
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $criterion->source_label }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- RIASEC --}}
            <section id="riasec" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="max-w-2xl">
                    <h2 class="text-3xl font-bold tracking-tight">Enam dimensi kepribadian RIASEC</h2>
                    <p class="mt-3 text-gray-600 dark:text-gray-300">
                        Model Holland membagi kecenderungan minat kerja menjadi enam tipe. Profil Anda dibandingkan
                        dengan profil tiap program studi untuk mengukur seberapa sejalan keduanya.
                    </p>
                </div>

                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($dimensions as $code => $label)
                        <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-gray-800 dark:bg-gray-800">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl text-lg font-bold text-white"
                                  style="background-color: {{ Riasec::color($code) }}">
                                {{ $code }}
                            </span>
                            <h3 class="mt-4 font-semibold">{{ $label }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                {{ $descriptions[$code] }}
                            </p>
                        </div>
                    @endforeach
                </div>
            </section>

            {{-- Program studi --}}
            <section id="prodi" class="border-y border-gray-100 bg-gray-50 py-20 dark:border-gray-800 dark:bg-gray-800/40">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="max-w-2xl">
                        <h2 class="text-3xl font-bold tracking-tight">Program studi yang dibandingkan</h2>
                        <p class="mt-3 text-gray-600 dark:text-gray-300">
                            Seluruh program studi di bawah ini ikut diperingkat &mdash; bukan hanya yang Anda pilih.
                            Bisa jadi ada prodi yang belum terpikirkan namun ternyata paling cocok.
                        </p>
                    </div>

                    @if ($programs->isEmpty())
                        <p class="mt-10 text-gray-500 dark:text-gray-400">Data program studi belum tersedia.</p>
                    @else
                        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($programs as $program)
                                <div class="flex flex-col rounded-xl border border-gray-100 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
                                    <div class="flex items-start justify-between gap-3">
                                        <h3 class="font-semibold leading-snug">{{ $program->full_name }}</h3>
                                        <span class="shrink-0 rounded-md bg-gray-100 px-2 py-0.5 font-mono text-[10px] text-gray-500 dark:bg-gray-700 dark:text-gray-300">
                                            {{ $program->holland_code }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $program->department }}</p>
                                    <p class="mt-3 flex-1 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                                        {{ Str::limit($program->description, 120) }}
                                    </p>
                                    <p class="mt-4 text-xs font-medium text-emerald-700 dark:text-emerald-400">
                                        {{ number_format($program->employment_percent, 0) }}% alumni terserap kerja
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </section>

            {{-- Ajakan --}}
            <section class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
                <div class="rounded-3xl bg-gradient-to-br from-indigo-600 to-violet-700 px-6 py-14 text-center text-white sm:px-12">
                    <h2 class="text-3xl font-bold tracking-tight">Siap mengetahui prodi yang paling cocok?</h2>
                    <p class="mx-auto mt-3 max-w-2xl text-indigo-100">
                        Buat akun calon mahasiswa, kerjakan tesnya, dan bawa hasilnya untuk berdiskusi dengan guru
                        BK maupun orang tua. Keputusan akhir tetap di tangan Anda.
                    </p>

                    @guest
                        <div class="mt-8 flex flex-wrap justify-center gap-3">
                            <a href="{{ route('register') }}"
                               class="inline-flex items-center rounded-xl bg-white px-6 py-3 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50">
                                Daftar Sekarang
                            </a>
                            <a href="{{ route('login') }}"
                               class="inline-flex items-center rounded-xl border border-white/40 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                                Masuk
                            </a>
                        </div>
                    @else
                        <div class="mt-8">
                            <a href="{{ route('dashboard') }}"
                               class="inline-flex items-center rounded-xl bg-white px-6 py-3 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-50">
                                Buka Dasbor Saya
                            </a>
                        </div>
                    @endguest
                </div>
            </section>
        </main>

        <footer class="border-t border-gray-100 py-10 dark:border-gray-800">
            <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 text-sm text-gray-500 sm:flex-row sm:px-6 lg:px-8 dark:text-gray-400">
                <p>&copy; {{ date('Y') }} {{ config('app.name') }} &middot; Politeknik Negeri Banyuwangi</p>
                <p>Metode Combined Compromise Solution (CoCoSo) &amp; model kepribadian RIASEC</p>
            </div>
        </footer>
    </body>
</html>
