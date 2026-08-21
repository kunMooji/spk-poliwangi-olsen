@use('App\Support\Riasec')

@php
    $departments = $programs->pluck('department')->filter()->unique()->sort()->values();
@endphp

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Sistem pendukung keputusan pemilihan program studi Politeknik Negeri Banyuwangi, menggabungkan tes minat bakat RIASEC dengan metode CoCoSo.">

        <title>{{ config('app.name') }} &middot; Politeknik Negeri Banyuwangi</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800|unbounded:400,600,700,800,900|space-mono:400,700" rel="stylesheet" />

        {{-- Landing page selalu terang — tidak ada toggle tema di sini, jadi
             tidak perlu skrip anti-flash pembaca localStorage seperti di
             layouts/app.blade.php & layouts/guest.blade.php. --}}
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="bg-porcelain-50 font-sans text-ink-900 antialiased">
        {{-- Sentinel 1px untuk mendeteksi kapan halaman mulai di-scroll (lihat initHeaderShadow di app.js). --}}
        <div id="scroll-sentinel" class="pointer-events-none absolute left-0 top-0 h-px w-full"></div>

        {{-- Bilah navigasi — struktur ala nefa: logo kiri, tautan tengah, aksi kanan,
             dengan hamburger Alpine di mobile. --}}
        <header data-site-header
                x-data="{ open: false }"
                class="sticky top-0 z-30 border-b border-black/5 bg-porcelain-50/90 shadow-transparent backdrop-blur transition-shadow duration-500 ease-brand-out">
            <nav id="navbar" class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between py-4">
                    <a href="{{ url('/') }}" class="flex items-center gap-2">
                        <img src="{{ asset('images/poliwangi_logo.png') }}" alt="Logo Politeknik Negeri Banyuwangi" class="h-9 w-9 object-contain">
                        <span class="font-display text-sm font-extrabold uppercase tracking-wide text-ink-900">
                            SPK <span class="text-brand-600">Poliwangi</span>
                        </span>
                    </a>

                    <div class="hidden items-center gap-8 font-mono text-xs uppercase tracking-widest text-ink-600 md:flex">
                        <a href="#cara-kerja" class="transition hover:text-brand-500">Cara Kerja</a>
                        <a href="#riasec" class="transition hover:text-brand-500">RIASEC</a>
                        <a href="#prodi" class="transition hover:text-brand-500">Program Studi</a>
                        <a href="#faq" class="transition hover:text-brand-500">FAQ</a>
                    </div>

                    <div class="hidden items-center gap-2 md:flex">
                        @auth
                            <a href="{{ route('dashboard') }}"
                               class="rounded-full bg-ink-900 px-5 py-2.5 text-sm font-semibold text-porcelain-50 transition hover:bg-ink-800">
                                Buka Dasbor
                            </a>
                        @else
                            <a href="{{ route('login') }}"
                               class="rounded-full px-4 py-2.5 text-sm font-semibold text-ink-700 transition hover:bg-black/5">
                                Masuk
                            </a>
                            <a href="{{ route('register') }}"
                               class="rounded-full bg-gradient-to-r from-brand-500 to-brand-700 px-5 py-2.5 text-sm font-semibold text-porcelain-50 shadow-md transition hover:-translate-y-0.5 hover:shadow-lg">
                                Daftar
                            </a>
                        @endauth
                    </div>

                    <button type="button" @click="open = !open" aria-label="Buka menu"
                            class="flex h-10 w-10 items-center justify-center rounded-lg text-ink-700 transition hover:bg-black/5 md:hidden">
                        <x-heroicon-o-bars-3 x-show="!open" class="h-6 w-6" />
                        <x-heroicon-o-x-mark x-show="open" x-cloak class="h-6 w-6" />
                    </button>
                </div>

                <div x-show="open" x-cloak x-transition
                     class="flex flex-col gap-1 border-t border-black/5 pb-4 pt-2 font-mono text-xs uppercase tracking-widest text-ink-600 md:hidden">
                    <a href="#cara-kerja" class="rounded-lg px-2 py-2.5 transition hover:bg-black/5 hover:text-brand-500">Cara Kerja</a>
                    <a href="#riasec" class="rounded-lg px-2 py-2.5 transition hover:bg-black/5 hover:text-brand-500">RIASEC</a>
                    <a href="#prodi" class="rounded-lg px-2 py-2.5 transition hover:bg-black/5 hover:text-brand-500">Program Studi</a>
                    <a href="#faq" class="rounded-lg px-2 py-2.5 transition hover:bg-black/5 hover:text-brand-500">FAQ</a>
                    <div class="mt-2 flex gap-2 px-2">
                        @auth
                            <a href="{{ route('dashboard') }}" class="w-full rounded-full bg-ink-900 px-4 py-2.5 text-center text-sm font-semibold normal-case tracking-normal text-porcelain-50">Buka Dasbor</a>
                        @else
                            <a href="{{ route('login') }}" class="w-full rounded-full border border-black/10 px-4 py-2.5 text-center text-sm font-semibold normal-case tracking-normal text-ink-700">Masuk</a>
                            <a href="{{ route('register') }}" class="w-full rounded-full bg-gradient-to-r from-brand-500 to-brand-700 px-4 py-2.5 text-center text-sm font-semibold normal-case tracking-normal text-porcelain-50">Daftar</a>
                        @endauth
                    </div>
                </div>
            </nav>
        </header>

        <main>
            {{-- Hero --}}
            <section id="hero" class="relative overflow-hidden bg-porcelain-50">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_120%_80%_at_50%_-10%,rgba(63,174,196,0.25),transparent),radial-gradient(ellipse_60%_60%_at_100%_100%,rgba(22,75,92,0.35),transparent)]"></div>
                <div class="absolute inset-0 bg-grain opacity-40 mix-blend-overlay"></div>

                {{-- Titik dekoratif ala pattern nefa — dibuat murni CSS (bukan
                     sprite gambar) supaya ringan & mengikuti token warna brand. --}}
                <span class="pointer-events-none absolute bottom-24 left-6 hidden h-2.5 w-2.5 rounded-full bg-gold-400 sm:block" aria-hidden="true"></span>
                <span class="pointer-events-none absolute right-1/3 top-12 hidden h-2 w-2 rounded-full bg-brand-400 sm:block" aria-hidden="true"></span>
                <span class="pointer-events-none absolute bottom-1/3 right-16 hidden h-2 w-2 rounded-full bg-gold-300 sm:block" aria-hidden="true"></span>
                <span class="pointer-events-none absolute right-8 top-24 hidden h-3 w-3 rounded-full bg-brand-300 sm:block" aria-hidden="true"></span>

                <div class="pointer-events-none absolute -left-24 top-10 h-72 w-72 animate-float rounded-full bg-brand-500/20 blur-3xl" aria-hidden="true"></div>
                <div class="pointer-events-none absolute -right-16 bottom-0 h-80 w-80 animate-float rounded-full bg-brand-400/10 blur-3xl [animation-delay:-4s]" aria-hidden="true"></div>

                <div class="relative mx-auto grid max-w-7xl grid-cols-1 items-center gap-10 px-4 pb-16 pt-14 sm:px-6 lg:grid-cols-12 lg:px-8 lg:pt-20">
                    <div class="animate-fade-up text-center lg:col-span-6 lg:text-left">
                        <span class="brand-shimmer inline-flex items-center gap-2 rounded-full bg-clip-text px-0 py-1 font-mono text-[11px] uppercase tracking-[0.2em] text-transparent">
                            <x-heroicon-o-sparkles class="h-3.5 w-3.5 text-brand-600" aria-hidden="true" />
                            Dossier Penjurusan &middot; CoCoSo &times; RIASEC
                        </span>

                        <h1 class="mt-5 font-display text-4xl font-extrabold uppercase leading-[1.05] tracking-tight sm:text-6xl">
                            <span data-split-reveal>Pilih jurusan</span>
                            <span class="block bg-gradient-to-r from-brand-600 via-brand-500 to-brand-400 bg-clip-text text-transparent">bukan tebakan.</span>
                        </h1>

                        <p class="mx-auto mt-6 max-w-xl text-lg leading-relaxed text-ink-600 lg:mx-0">
                            Nilai rapor, minat bakat, urutan pilihan, sampai serapan kerja alumni ditimbang
                            sekaligus dan diberi peringkat.
                            <strong class="font-semibold text-ink-900">Setiap angka bisa Anda bongkar alasannya</strong>,
                            bukan cuma percaya begitu saja.
                        </p>

                        <div class="mt-9 flex flex-col items-center gap-3 sm:flex-row sm:justify-center lg:justify-start">
                            @auth
                                <a href="{{ route('dashboard') }}"
                                   class="group inline-flex w-full items-center justify-center gap-3 rounded-full bg-brand-600 py-2 pl-6 pr-2 text-sm font-bold uppercase tracking-wide text-porcelain-50 shadow-[0_0_0_1px_rgba(124,205,220,0.4)] transition duration-500 ease-brand-out hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-lg active:scale-[0.98] sm:w-auto">
                                    Buka Dasbor Saya
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-ink-950/10 transition duration-500 ease-brand-out group-hover:translate-x-0.5">
                                        <x-heroicon-o-arrow-up-right class="h-4 w-4" />
                                    </span>
                                </a>
                            @else
                                <a href="{{ route('register') }}"
                                   class="group inline-flex w-full items-center justify-center gap-3 rounded-full bg-brand-600 py-2 pl-6 pr-2 text-sm font-bold uppercase tracking-wide text-porcelain-50 shadow-[0_0_0_1px_rgba(124,205,220,0.4)] transition duration-500 ease-brand-out hover:-translate-y-0.5 hover:bg-brand-700 hover:shadow-lg active:scale-[0.98] sm:w-auto">
                                    Mulai Tes Gratis
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-ink-950/10 transition duration-500 ease-brand-out group-hover:translate-x-0.5">
                                        <x-heroicon-o-arrow-up-right class="h-4 w-4" />
                                    </span>
                                </a>
                                <a href="{{ route('login') }}"
                                   class="inline-flex w-full items-center justify-center rounded-full border border-ink-900/25 px-6 py-3 text-sm font-semibold text-ink-900 transition duration-300 ease-brand-out hover:-translate-y-0.5 hover:border-brand-500/60 hover:bg-black/5 active:scale-[0.98] sm:w-auto">
                                    Sudah punya akun
                                </a>
                            @endauth
                        </div>

                        <p class="mt-4 font-mono text-xs text-ink-500/80">
                            &Oslash; pengisian &plusmn;10 menit &middot; hasil tersimpan &amp; bisa dibuka lagi kapan saja
                        </p>
                    </div>

                    <div class="lg:col-span-6">
                        <div class="relative mx-auto aspect-[6/5] w-full max-w-lg overflow-hidden rounded-[2rem] shadow-2xl shadow-ink-950/20">
                            <img src="{{ asset('images/3-mahasiswa.png') }}"
                                 alt="Tiga mahasiswa Politeknik Negeri Banyuwangi berbincang di area kampus"
                                 class="absolute inset-0 h-full w-full object-cover">
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-ink-950/70 via-transparent to-transparent"></div>
                            <div class="absolute bottom-4 left-4 right-4 flex items-center justify-between rounded-2xl border border-brand-300/30 bg-ink-950/80 px-4 py-3 shadow-xl backdrop-blur">
                                <div>
                                    <p class="font-mono text-[10px] uppercase tracking-widest text-porcelain-200/50">Peta minat</p>
                                    <p class="mt-0.5 text-sm font-semibold text-porcelain-50">{{ $programs->count() }} program studi</p>
                                </div>
                                <span class="flex items-center gap-2 font-mono text-[11px] font-semibold text-porcelain-50">
                                    <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
                                    Aktif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Kartu statistik mengambang — ala "crypto statistic" nefa,
                 sedikit menimpa batas hero/section berikutnya. --}}
            <div class="relative z-10 mx-auto -mt-8 max-w-6xl px-4 sm:-mt-10 sm:px-6 lg:px-8">
                <div class="reveal grid grid-cols-2 gap-6 rounded-[2rem] border border-black/5 bg-white px-6 py-8 shadow-xl shadow-ink-950/10 sm:grid-cols-4 sm:px-10">
                    @foreach ([
                        ['value' => $programs->count(), 'label' => 'Program studi'],
                        ['value' => $criteria->count(), 'label' => 'Kriteria penilaian'],
                        ['value' => $questionCount, 'label' => 'Pernyataan minat bakat'],
                        ['value' => count(Riasec::DIMENSIONS), 'label' => 'Dimensi kepribadian'],
                    ] as $stat)
                        <div class="text-center sm:border-l sm:border-black/5 sm:first:border-l-0 sm:pl-6 sm:first:pl-0">
                            <dt class="font-mono text-3xl font-bold text-brand-600">{{ $stat['value'] }}</dt>
                            <dd class="mt-1 text-xs uppercase tracking-wide text-ink-500">{{ $stat['label'] }}</dd>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Apa saja yang dinilai — teks & daftar kriteria di kiri, foto di kanan
                 (mengikuti pola dua-kolom "buy & trade" milik nefa). --}}
            <section id="cara-kerja" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8">
                <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12">
                    <div class="reveal lg:col-span-6">
                        <span class="font-mono text-xs uppercase tracking-[0.2em] text-brand-600">Neraca penilaian</span>
                        <h2 data-split-reveal class="mt-2 font-display text-2xl font-extrabold uppercase tracking-tight text-ink-900 sm:text-3xl">Apa saja yang dinilai?</h2>
                        <p class="mt-3 text-ink-600">
                            Setiap kriteria punya bobot tersendiri yang ditetapkan bersama pihak akademik. Bobot
                            yang berlaku saat Anda mengerjakan tes disimpan menyatu dengan hasil.
                        </p>

                        <ul class="mt-6 divide-y divide-black/10 rounded-[1.5rem] border border-black/10 bg-white">
                            @foreach ($criteria as $criterion)
                                <li class="p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex items-start gap-3">
                                            <span class="mt-0.5 shrink-0 rounded-md border border-brand-500/30 bg-brand-500/10 px-1.5 py-0.5 font-mono text-[11px] text-brand-700">
                                                {{ $criterion->code }}
                                            </span>
                                            <div>
                                                <h3 class="text-sm font-semibold leading-snug text-ink-900">{{ $criterion->name }}</h3>
                                                <p class="mt-0.5 text-xs text-ink-500">{{ $criterion->source_label }}</p>
                                            </div>
                                        </div>
                                        <span class="shrink-0 font-mono text-sm font-semibold text-brand-600">
                                            {{ number_format($criterion->weight * 100, 0) }}%
                                        </span>
                                    </div>
                                    <div class="mt-2.5 h-1.5 overflow-hidden rounded-full bg-black/10">
                                        <div class="h-full rounded-full bg-gradient-to-r from-brand-400 to-brand-300"
                                             style="width: {{ $criterion->weight * 100 }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="reveal lg:col-span-6">
                        <div class="relative aspect-[4/3] w-full overflow-hidden rounded-[2rem] shadow-xl shadow-ink-950/10">
                            <img src="{{ asset('images/perpustakaan.webp') }}" alt="" class="absolute inset-0 h-full w-full object-cover" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-ink-950/60 via-transparent to-transparent"></div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Banner "dipercaya lintas jurusan" — ala partner banner nefa,
                 diisi daftar jurusan sungguhan, bukan logo mitra. --}}
            <section class="relative mx-2 my-4 overflow-hidden rounded-[2rem] bg-ink-950 shadow sm:mx-6">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_80%_at_50%_0%,rgba(63,174,196,0.25),transparent)]"></div>
                <div class="absolute inset-0 bg-grain opacity-20 mix-blend-overlay"></div>
                <div class="relative flex flex-col items-center justify-center space-y-5 px-6 py-14 text-center sm:py-16">
                    <h3 class="reveal font-display text-xl font-extrabold uppercase tracking-tight text-porcelain-50 sm:text-2xl">Mencakup seluruh jurusan Poliwangi</h3>
                    <p class="reveal max-w-lg text-sm text-porcelain-200/70">Peringkat dihitung lintas jurusan, bukan hanya program studi favorit.</p>
                    <div class="reveal flex flex-wrap items-center justify-center gap-2">
                        @foreach ($departments as $department)
                            <span class="rounded-full border border-white/15 bg-white/5 px-4 py-1.5 font-mono text-[11px] uppercase tracking-wide text-porcelain-200/90">
                                {{ $department }}
                            </span>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- RIASEC: intro di atas, mosaik interaktif + panel deskripsi di bawah
                 (deck ala "credit card" section nefa, tapi memakai widget nyata). --}}
            <section id="riasec" class="overflow-hidden py-16 sm:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="reveal max-w-2xl">
                        <span class="font-mono text-xs uppercase tracking-[0.2em] text-brand-600">Profil kepribadian</span>
                        <h2 data-split-reveal class="mt-2 font-display text-3xl font-extrabold uppercase tracking-tight text-ink-900">Enam dimensi RIASEC</h2>
                        <p class="mt-3 max-w-2xl text-ink-600">
                            Model Holland membagi kecenderungan minat kerja menjadi enam tipe. Ketuk salah satu blok di
                            samping untuk membaca penjelasannya di sini.
                        </p>
                    </div>
                </div>

                @php($riasecWeight = ['R' => 27, 'I' => 46, 'A' => 27, 'S' => 30, 'E' => 44, 'C' => 26])

                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div x-data="{
                        open: null,
                        select(code) {
                            this.open = (this.open === code ? null : code);
                            this.$nextTick(() => window.riasecReveal && window.riasecReveal(this.open));
                        },
                     }" class="reveal mt-12 flex flex-col gap-6 lg:flex-row lg:items-stretch">

                    <div class="w-full lg:min-w-0 lg:flex-1">
                        <div class="relative isolate flex h-[460px] flex-col overflow-hidden rounded-[1.75rem] shadow-xl shadow-ink-950/20 sm:h-[560px] lg:h-[620px]">
                            <img src="{{ asset('images/perpustakaan.webp') }}" alt=""
                                 class="riasec-bg absolute inset-0 -z-20 h-full w-full object-cover" loading="lazy">
                            <div class="absolute inset-0 -z-10 bg-gradient-to-br from-ink-950/95 via-ink-950/85 to-brand-950/75"></div>
                            <div class="absolute inset-0 -z-10 bg-grain opacity-20 mix-blend-overlay"></div>
                            <div class="pointer-events-none absolute inset-0 -z-10 rounded-[1.75rem] ring-1 ring-inset ring-white/10"></div>

                            <div class="relative flex h-full flex-col p-8">
                                <div x-show="!open" data-riasec-panel="default" class="flex h-full flex-col items-start justify-center">
                                    <span class="font-display text-5xl font-black tracking-tight text-white/10 sm:text-6xl">R&middot;I&middot;A&middot;S&middot;E&middot;C</span>
                                    <p class="mt-4 max-w-sm text-porcelain-200/70">
                                        Ketuk salah satu blok di sebelah kanan. Penjelasan dimensinya akan tampil
                                        di sini.
                                    </p>
                                </div>

                                @foreach ($dimensions as $code => $label)
                                    <div x-show="open === '{{ $code }}'"
                                         x-cloak
                                         data-riasec-panel="{{ $code }}"
                                         class="flex h-full flex-col">
                                        <div class="flex items-start justify-between gap-4">
                                            <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl font-display text-2xl font-extrabold text-white shadow-lg"
                                                  style="background-color: {{ Riasec::color($code) }};">
                                                {{ $code }}
                                            </span>
                                            <button type="button" @click="select('{{ $code }}')" aria-label="Tutup"
                                                    class="flex h-9 w-9 items-center justify-center rounded-full text-porcelain-200/60 transition hover:bg-white/10 hover:text-white">
                                                <x-heroicon-o-x-mark class="h-5 w-5" />
                                            </button>
                                        </div>
                                        <span class="mt-5 font-mono text-xs uppercase tracking-widest text-porcelain-200/50">Dimensi {{ $loop->iteration }} dari 6</span>
                                        <h3 class="mt-1 font-display text-2xl font-extrabold uppercase tracking-tight text-porcelain-50">{{ $label }}</h3>
                                        <p class="mt-4 flex-1 overflow-y-auto text-sm leading-relaxed text-porcelain-200/80">
                                            {{ $descriptions[$code] }}
                                        </p>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <div class="w-full lg:w-[300px] lg:shrink-0 xl:w-[360px]" data-riasec-deck>
                        <div class="flex h-[460px] gap-[3px] overflow-hidden rounded-[1.75rem] border-[10px] border-ink-950 bg-ink-950 sm:h-[560px] lg:h-[620px]">
                            @foreach ([['R', 'I', 'A'], ['S', 'E', 'C']] as $column)
                                <div class="flex flex-1 flex-col gap-[3px]">
                                    @foreach ($column as $code)
                                        <button type="button"
                                                @click="select('{{ $code }}')"
                                                :aria-expanded="open === '{{ $code }}'"
                                                aria-label="{{ $dimensions[$code] }}"
                                                class="riasec-card group relative flex flex-col justify-between overflow-hidden p-4 text-left text-white sm:p-5"
                                                style="flex: {{ $riasecWeight[$code] }} 1 0%; background-color: {{ Riasec::color($code) }};"
                                                :class="open === '{{ $code }}' ? 'ring-4 ring-inset ring-brand-300' : 'ring-0'">
                                            <span class="riasec-code font-display text-4xl font-extrabold leading-none drop-shadow sm:text-5xl">{{ $code }}</span>
                                            <span class="text-xs font-semibold uppercase tracking-wide text-white/85 sm:text-sm">{{ $dimensions[$code] }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                </div>
            </section>

            {{-- Bagaimana cara kerjanya — band gelap dengan tiga blok fitur bernomor,
                 ala section "Advanced Trading Tools" milik nefa. --}}
            <section class="relative mx-2 my-4 overflow-hidden rounded-[2rem] bg-ink-950 shadow sm:mx-6">
                <div class="absolute inset-0 bg-[radial-gradient(ellipse_70%_70%_at_80%_0%,rgba(63,174,196,0.22),transparent)]"></div>
                <div class="absolute inset-0 bg-grain opacity-20 mix-blend-overlay"></div>

                <div class="relative mx-auto grid max-w-6xl grid-cols-1 gap-12 px-6 py-16 sm:px-10 sm:py-24 lg:grid-cols-12">
                    <div class="reveal lg:col-span-5">
                        <span class="font-mono text-xs uppercase tracking-[0.2em] text-brand-300">Alur &middot; 3 langkah</span>
                        <h2 data-split-reveal class="mt-2 font-display text-2xl font-extrabold uppercase tracking-tight text-porcelain-50 sm:text-3xl">Bagaimana cara kerjanya?</h2>
                        <p class="mt-3 text-porcelain-200/70">
                            Tiga langkah, satu kali duduk. Anda tidak perlu tahu rumusnya, tetapi seluruh
                            perhitungannya tetap dapat Anda buka bila ingin memeriksanya.
                        </p>
                        @guest
                            <a href="{{ route('register') }}"
                               class="group mt-8 inline-flex items-center gap-3 rounded-full bg-brand-300 py-2 pl-6 pr-2 text-sm font-bold uppercase tracking-wide text-ink-950 transition duration-500 ease-brand-out hover:-translate-y-0.5 hover:bg-brand-200 hover:shadow-lg active:scale-[0.98]">
                                Mulai Sekarang
                                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-ink-950/10 transition duration-500 ease-brand-out group-hover:translate-x-0.5">
                                    <x-heroicon-o-arrow-up-right class="h-4 w-4" />
                                </span>
                            </a>
                        @endguest
                    </div>

                    <ol class="reveal space-y-6 lg:col-span-7">
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
                            <li class="flex items-start gap-4 rounded-2xl border border-white/10 bg-white/5 p-5">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-500/20 font-mono text-sm font-bold text-brand-200 ring-1 ring-inset ring-brand-300/30">
                                    {{ $index + 1 }}
                                </span>
                                <div>
                                    <h3 class="text-base font-semibold text-porcelain-50">{{ $step['title'] }}</h3>
                                    <p class="mt-1 text-sm leading-relaxed text-porcelain-200/70">{{ $step['body'] }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </section>

            {{-- Program studi --}}
            <section id="prodi" class="py-16 text-ink-900 sm:py-24">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div class="reveal max-w-2xl">
                        <span class="font-mono text-xs uppercase tracking-[0.2em] text-brand-600">Katalog</span>
                        <h2 data-split-reveal class="mt-2 font-display text-3xl font-extrabold uppercase tracking-tight">Program studi yang dibandingkan</h2>
                        <p class="mt-3 text-ink-600">
                            Seluruh program studi di bawah ini ikut diperingkat, bukan hanya yang Anda pilih.
                            Cari atau saring menurut jurusan untuk menjajakinya.
                        </p>
                    </div>

                    @if ($programs->isEmpty())
                        <p class="mt-10 text-ink-500">Data program studi belum tersedia.</p>
                    @else
                        <div x-data="{
                                search: '',
                                dept: 'Semua',
                                matches(haystack, itemDept) {
                                    const q = this.search.trim().toLowerCase();
                                    return (this.dept === 'Semua' || itemDept === this.dept) && (q === '' || haystack.includes(q));
                                },
                            }" class="mt-10">
                            <div class="reveal flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <label class="relative w-full sm:max-w-xs">
                                    <span class="sr-only">Cari program studi</span>
                                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-400" />
                                    <input type="text" x-model="search" placeholder="Cari nama program studi&hellip;"
                                           class="w-full rounded-full border border-black/10 bg-black/5 py-2.5 pl-11 pr-4 text-sm text-ink-900 shadow-sm transition duration-300 ease-brand-out placeholder:text-ink-400 focus:border-brand-500/60 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                                </label>

                                <div class="flex flex-wrap gap-2">
                                    <button type="button" @click="dept = 'Semua'"
                                            :class="dept === 'Semua' ? 'border-brand-600 bg-brand-600 text-porcelain-50' : 'border-black/10 bg-black/5 text-ink-600 hover:border-brand-500/50 hover:text-brand-600'"
                                            class="rounded-full border px-4 py-1.5 text-xs font-semibold transition duration-300 ease-brand-out">
                                        Semua
                                    </button>
                                    @foreach ($departments as $department)
                                        <button type="button" @click="dept = '{{ $department }}'"
                                                :class="dept === '{{ $department }}' ? 'border-brand-600 bg-brand-600 text-porcelain-50' : 'border-black/10 bg-black/5 text-ink-600 hover:border-brand-500/50 hover:text-brand-600'"
                                                class="rounded-full border px-4 py-1.5 text-xs font-semibold transition duration-300 ease-brand-out">
                                            {{ $department }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="reveal mt-8 flex items-center gap-2 sm:hidden">
                                <button type="button" data-carousel-prev="prodi-carousel" aria-label="Geser ke kiri"
                                        class="flex h-10 w-10 items-center justify-center rounded-full border border-black/10 bg-black/5 text-ink-600 shadow-sm transition disabled:pointer-events-none disabled:opacity-30">
                                    <x-heroicon-o-chevron-left class="h-4 w-4" />
                                </button>
                                <button type="button" data-carousel-next="prodi-carousel" aria-label="Geser ke kanan"
                                        class="flex h-10 w-10 items-center justify-center rounded-full border border-black/10 bg-black/5 text-ink-600 shadow-sm transition disabled:pointer-events-none disabled:opacity-30">
                                    <x-heroicon-o-chevron-right class="h-4 w-4" />
                                </button>
                                <span class="font-mono text-xs text-ink-400">Geser untuk melihat lainnya</span>
                            </div>

                            <div class="relative mt-4 sm:mt-8">
                                <div class="pointer-events-none absolute inset-y-0 left-0 z-10 hidden w-16 bg-gradient-to-r from-porcelain-50 to-transparent sm:block"></div>
                                <button type="button" data-carousel-prev="prodi-carousel" aria-label="Geser ke kiri"
                                        class="absolute -left-2 top-1/2 z-20 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-black/10 bg-white text-ink-600 shadow-md transition duration-300 ease-brand-out hover:-translate-x-0.5 hover:border-brand-500/50 hover:text-brand-600 disabled:pointer-events-none disabled:opacity-0 sm:flex">
                                    <x-heroicon-o-chevron-left class="h-5 w-5" />
                                </button>

                                <div id="prodi-carousel" data-carousel class="no-scrollbar flex snap-x snap-mandatory cursor-grab gap-5 overflow-x-auto scroll-smooth pb-2">
                                    @foreach ($programs as $program)
                                        <div x-show="matches('{{ Str::lower("{$program->full_name} {$program->department}") }}', '{{ $program->department }}')"
                                             x-transition:enter="transition ease-brand-out duration-500"
                                             x-transition:enter-start="opacity-0 scale-95"
                                             x-transition:enter-end="opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-150"
                                             x-transition:leave-start="opacity-100"
                                             x-transition:leave-end="opacity-0"
                                             class="reveal w-[80%] shrink-0 snap-start sm:w-[55%] lg:w-[31%]">
                                            <div class="group h-full select-none rounded-[1.5rem] border border-black/10 bg-white p-6 shadow-lg shadow-black/10 transition duration-300 ease-brand-out hover:-translate-y-1 hover:border-brand-500/40">
                                                <div class="flex items-start justify-between gap-3">
                                                    <h3 class="font-semibold leading-snug text-ink-900">{{ $program->full_name }}</h3>
                                                    <span class="shrink-0 rounded-md border border-brand-500/30 bg-brand-500/10 px-2 py-0.5 font-mono text-[10px] text-brand-700">
                                                        {{ $program->holland_code }}
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-xs text-ink-500">{{ $program->department }}</p>
                                                <p class="mt-3 text-sm leading-relaxed text-ink-600">
                                                    {{ Str::limit($program->description, 120) }}
                                                </p>
                                                <p class="mt-4 flex items-center gap-1.5 font-mono text-xs font-medium text-emerald-600">
                                                    <x-heroicon-o-bolt class="h-3.5 w-3.5" />
                                                    {{ number_format($program->employment_percent, 0) }}% alumni terserap kerja
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" data-carousel-next="prodi-carousel" aria-label="Geser ke kanan"
                                        class="absolute -right-2 top-1/2 z-20 hidden h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full border border-black/10 bg-white text-ink-600 shadow-md transition duration-300 ease-brand-out hover:translate-x-0.5 hover:border-brand-500/50 hover:text-brand-600 disabled:pointer-events-none disabled:opacity-0 sm:flex">
                                    <x-heroicon-o-chevron-right class="h-5 w-5" />
                                </button>
                                <div class="pointer-events-none absolute inset-y-0 right-0 z-10 hidden w-16 bg-gradient-to-l from-porcelain-50 to-transparent sm:block"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </section>

            {{-- FAQ — image di kiri, accordion Alpine di kanan (ala section FAQ nefa). --}}
            <section id="faq" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
                <div class="grid grid-cols-1 items-center gap-12 lg:grid-cols-12">
                    <div class="reveal lg:col-span-5">
                        <div class="relative aspect-[4/3] w-full overflow-hidden rounded-[2rem] shadow-xl shadow-ink-950/10">
                            <img src="{{ asset('images/poliwangi-kampus.png') }}" alt="Gedung kampus Politeknik Negeri Banyuwangi"
                                 class="absolute inset-0 h-full w-full object-cover" loading="lazy">
                            <div class="absolute inset-0 bg-gradient-to-t from-ink-950/50 via-transparent to-transparent"></div>
                        </div>
                    </div>

                    <div class="reveal lg:col-span-7" x-data="{ openFaq: 0 }">
                        <span class="font-mono text-xs uppercase tracking-[0.2em] text-brand-600">Bantuan</span>
                        <h2 class="mt-2 font-display text-2xl font-extrabold uppercase tracking-tight text-ink-900 sm:text-3xl">Pertanyaan yang sering diajukan</h2>

                        <ul class="mt-8 divide-y divide-black/10 rounded-[1.5rem] border border-black/10 bg-white">
                            @foreach ([
                                [
                                    'title' => 'Apakah hasil tes ini menentukan jurusan saya?',
                                    'body' => 'Tidak. Sistem hanya memberi peringkat & rekomendasi berdasarkan data yang Anda masukkan. Keputusan akhir tetap di tangan Anda, orang tua, dan guru BK.',
                                ],
                                [
                                    'title' => 'Berapa lama waktu pengerjaan tesnya?',
                                    'body' => 'Sekitar 10 menit untuk mengisi nilai rapor & urutan pilihan, ditambah kuesioner minat bakat '.$questionCount.' pernyataan.',
                                ],
                                [
                                    'title' => 'Bagaimana cara sistem menentukan peringkat?',
                                    'body' => 'Menggunakan metode Combined Compromise Solution (CoCoSo): setiap kriteria (nilai rapor, profil RIASEC, urutan pilihan, serapan kerja) diberi bobot, lalu seluruh program studi dibandingkan sekaligus.',
                                ],
                                [
                                    'title' => 'Apakah hasil saya bisa dibuka lagi nanti?',
                                    'body' => 'Bisa. Setelah masuk, hasil tersimpan di dasbor akun Anda dan dapat dibuka kembali kapan pun, lengkap dengan rincian tiap kriteria.',
                                ],
                            ] as $index => $faq)
                                <li class="p-5">
                                    <button type="button" @click="openFaq = (openFaq === {{ $index }} ? null : {{ $index }})" class="flex w-full items-center justify-between gap-4 text-left">
                                        <span class="font-medium text-ink-900">{{ $faq['title'] }}</span>
                                        <x-heroicon-o-chevron-up x-show="openFaq === {{ $index }}" class="h-4 w-4 shrink-0 text-brand-600" />
                                        <x-heroicon-o-chevron-down x-show="openFaq !== {{ $index }}" class="h-4 w-4 shrink-0 text-ink-400" />
                                    </button>
                                    <div x-show="openFaq === {{ $index }}" x-cloak x-transition class="pt-3">
                                        <p class="text-sm leading-relaxed text-ink-600">{{ $faq['body'] }}</p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </section>

            {{-- Ajakan --}}
            <section class="mx-auto max-w-7xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
                <div class="reveal relative overflow-hidden rounded-[2rem] px-6 py-16 text-center text-white shadow-2xl sm:px-12 sm:py-20">
                    <img src="{{ asset('images/poliwangi-kampus.png') }}"
                         alt="Gedung kampus Politeknik Negeri Banyuwangi"
                         class="absolute inset-0 h-full w-full object-cover" loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-ink-950 via-ink-950/90 to-brand-950/60"></div>
                    <div class="absolute inset-0 bg-grain opacity-30 mix-blend-overlay"></div>
                    <div class="pointer-events-none absolute inset-0 rounded-[2rem] ring-1 ring-inset ring-brand-300/20"></div>

                    <div class="relative">
                        <span class="inline-flex items-center gap-2 font-mono text-xs uppercase tracking-[0.2em] text-brand-300">
                            <x-heroicon-o-shield-check class="h-4 w-4" />
                            Keputusan tetap di tangan Anda
                        </span>
                        <h2 data-split-reveal class="mt-3 font-display text-3xl font-extrabold uppercase tracking-tight sm:text-4xl">Siap tahu prodi paling cocok?</h2>
                        <p class="mx-auto mt-3 max-w-2xl text-porcelain-200/80">
                            Buat akun calon mahasiswa, kerjakan tesnya, dan bawa hasilnya untuk berdiskusi dengan guru
                            BK maupun orang tua.
                        </p>

                        @guest
                            <div class="mt-8 flex flex-wrap justify-center gap-3">
                                <a href="{{ route('register') }}"
                                   class="group inline-flex items-center gap-3 rounded-full bg-brand-300 py-2 pl-6 pr-2 text-sm font-bold uppercase tracking-wide text-ink-950 transition duration-500 ease-brand-out hover:-translate-y-0.5 hover:bg-brand-200 hover:shadow-lg active:scale-[0.98]">
                                    Daftar Sekarang
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-ink-950/10 transition duration-500 ease-brand-out group-hover:translate-x-0.5">
                                        <x-heroicon-o-arrow-up-right class="h-4 w-4" />
                                    </span>
                                </a>
                                <a href="{{ route('login') }}"
                                   class="inline-flex items-center rounded-full border border-white/25 px-6 py-3 text-sm font-semibold text-white transition duration-300 ease-brand-out hover:-translate-y-0.5 hover:border-brand-300/50 hover:bg-white/10 active:scale-[0.98]">
                                    Masuk
                                </a>
                            </div>
                        @else
                            <div class="mt-8">
                                <a href="{{ route('dashboard') }}"
                                   class="group inline-flex items-center gap-3 rounded-full bg-brand-300 py-2 pl-6 pr-2 text-sm font-bold uppercase tracking-wide text-ink-950 transition duration-500 ease-brand-out hover:-translate-y-0.5 hover:bg-brand-200 hover:shadow-lg active:scale-[0.98]">
                                    Buka Dasbor Saya
                                    <span class="flex h-8 w-8 items-center justify-center rounded-full bg-ink-950/10 transition duration-500 ease-brand-out group-hover:translate-x-0.5">
                                        <x-heroicon-o-arrow-up-right class="h-4 w-4" />
                                    </span>
                                </a>
                            </div>
                        @endguest
                    </div>
                </div>
            </section>

            {{-- Kembali ke atas — sentuhan kecil ala nefa. --}}
            <div class="mb-16 flex w-full justify-center">
                <a href="#navbar"
                   class="flex items-center space-x-2 rounded-full border border-black/10 bg-white px-6 py-3 text-sm text-ink-600 shadow-sm transition hover:border-brand-500/40 hover:text-brand-600 hover:shadow-md">
                    <span>Kembali ke atas</span>
                    <x-heroicon-o-arrow-up class="h-4 w-4" />
                </a>
            </div>
        </main>

        {{-- Footer — struktur 4 kolom ala nefa, diisi tautan & info yang sungguhan ada. --}}
        <footer class="border-t border-black/5 bg-porcelain-50">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 gap-10 border-t border-black/10 py-12 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <a href="{{ url('/') }}" class="flex items-center gap-2">
                            <img src="{{ asset('images/poliwangi_logo.png') }}" alt="Logo Politeknik Negeri Banyuwangi" class="h-9 w-9 object-contain">
                            <span class="font-display text-sm font-extrabold uppercase tracking-wide text-ink-900">
                                SPK <span class="text-brand-600">Poliwangi</span>
                            </span>
                        </a>
                        <p class="mt-4 max-w-xs text-sm leading-relaxed text-ink-500">
                            Sistem pendukung keputusan pemilihan program studi, menggabungkan tes minat bakat RIASEC
                            dengan metode Combined Compromise Solution (CoCoSo).
                        </p>
                    </div>

                    <div>
                        <h5 class="font-mono text-xs uppercase tracking-widest text-ink-400">Jelajahi</h5>
                        <ul class="mt-4 space-y-3 text-sm text-ink-600">
                            <li><a href="#cara-kerja" class="transition hover:text-brand-600">Cara Kerja</a></li>
                            <li><a href="#riasec" class="transition hover:text-brand-600">RIASEC</a></li>
                            <li><a href="#prodi" class="transition hover:text-brand-600">Program Studi</a></li>
                            <li><a href="#faq" class="transition hover:text-brand-600">FAQ</a></li>
                        </ul>
                    </div>

                    <div>
                        <h5 class="font-mono text-xs uppercase tracking-widest text-ink-400">Akun</h5>
                        <ul class="mt-4 space-y-3 text-sm text-ink-600">
                            @auth
                                <li><a href="{{ route('dashboard') }}" class="transition hover:text-brand-600">Dasbor</a></li>
                            @else
                                <li><a href="{{ route('login') }}" class="transition hover:text-brand-600">Masuk</a></li>
                                <li><a href="{{ route('register') }}" class="transition hover:text-brand-600">Daftar</a></li>
                            @endauth
                        </ul>
                    </div>

                    <div>
                        <h5 class="font-mono text-xs uppercase tracking-widest text-ink-400">Metode</h5>
                        <p class="mt-4 text-sm leading-relaxed text-ink-600">
                            Combined Compromise Solution (CoCoSo) &amp; RIASEC &mdash; bobot kriteria ditetapkan
                            bersama pihak akademik Politeknik Negeri Banyuwangi.
                        </p>
                    </div>
                </div>

                <div class="flex flex-col items-center justify-between gap-4 border-t border-black/5 py-8 font-mono text-xs text-ink-500 sm:flex-row">
                    <p>&copy; {{ date('Y') }} {{ config('app.name') }} &middot; Politeknik Negeri Banyuwangi</p>
                    <p>Combined Compromise Solution (CoCoSo) &amp; RIASEC</p>
                </div>
            </div>
        </footer>
    </body>
</html>
