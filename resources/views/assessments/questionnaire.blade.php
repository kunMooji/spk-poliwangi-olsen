<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Kuesioner Minat Bakat RIASEC
        </h2>
    </x-slot>

    {{-- Bilah kemajuan di app bar (HP): 3 langkah awal sudah selesai (75%),
         sisanya 25% bertambah setiap satu pernyataan dijawab. --}}
    <x-slot name="progressBar">
        <x-assessment-progress-bar expression="((3 + $store.wizard.fraction) / 4) * 100" />
    </x-slot>

    @php
        // Jawaban yang sudah tersimpan di server dipakai sebagai nilai awal;
        // draft di localStorage hanya menambal butir yang belum tersimpan.
        $initial = $saved->mapWithKeys(fn ($score, $id) => [(string) $id => (int) $score]);
        // Palet pertanyaan mengikuti urutan warna kartu rekomendasi; tiap
        // dimensi RIASEC mendapat aksen tetap agar mudah dikenali saat mengisi.
        $questionPalettes = [
            'R' => '#42A5F5',
            'I' => '#26C281',
            'A' => '#AB47BC',
            'S' => '#FFA200',
            'E' => '#EF5350',
            'C' => '#26C6DA',
        ];
        // Label kedua ujung skala, ditampilkan di bawah deretan angka.
        $likertFirst = $likert[array_key_first($likert)];
        $likertLast = $likert[array_key_last($likert)];
        $questionIcons = [
            'R' => 'wrench-screwdriver',
            'I' => 'magnifying-glass',
            'A' => 'paint-brush',
            'S' => 'user-group',
            'E' => 'arrow-trending-up',
            'C' => 'clipboard-document-list',
        ];
    @endphp

    <div class="py-6 sm:py-8"
         x-data="questionnaire({
             total: {{ $questions->count() }},
             storageKey: 'spk-draft-{{ $assessment->code }}',
             initial: {{ Js::from($initial) }},
             autosaveUrl: '{{ route('assessments.answers.autosave', $assessment) }}',
         })"
         x-init="init()">

        <div class="mx-auto max-w-none px-5 sm:px-8 lg:px-10 xl:px-12">
            <x-flash />

            <section class="mobile-flat relative rounded-[1.75rem] border border-brand-100 bg-[radial-gradient(circle_at_86%_0%,rgba(179,227,236,.65),transparent_28%),linear-gradient(135deg,#ffffff,#eff9fb)] p-5 shadow-xl shadow-ink-950/5 dark:border-white/10 dark:bg-[radial-gradient(circle_at_78%_7%,rgba(27,137,163,.30),transparent_24%),linear-gradient(135deg,#071b29,#0b1627_55%,#14243a)] dark:shadow-2xl dark:shadow-ink-950/20 sm:p-7 lg:p-9">
                <div class="relative flex flex-wrap items-center justify-between gap-5">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white"><x-heroicon-o-clipboard-document-check class="h-6 w-6" /></span>
                        <div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-200">Langkah 4 dari 4</p><h1 class="mt-1 text-xl font-bold tracking-tight text-ink-950 dark:text-white sm:text-2xl">Kuesioner Minat Bakat RIASEC</h1><p class="mt-1 max-w-xl text-sm leading-relaxed text-ink-500 dark:text-porcelain-200/75">Jawab setiap pernyataan sesuai diri Anda untuk melengkapi rekomendasi program studi.</p></div>
                    </div>
                    <div class="hidden items-end gap-3 sm:flex"><p class="max-w-[9.5rem] -rotate-3 font-script text-lg leading-tight text-brand-600 dark:text-brand-300">Kenali diri, tentukan arahmu</p><img src="{{ asset('images/ilastrasi_toga.png') }}" alt="" class="h-20 w-auto shrink-0"></div>
                </div>

            <div class="relative mt-8 grid grid-cols-1 gap-6 lg:grid-cols-[280px_minmax(0,1fr)] lg:items-start">
                <x-assessment-progress :current="4" fraction-expression="answeredCount / total" sticky-top="lg:top-20" clickable hide-on-mobile />

                <div class="min-w-0">
                <div class="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.06] dark:shadow-black/10 sm:p-6">
                    <h3 class="text-lg font-semibold tracking-tight text-gray-950 dark:text-gray-100 sm:text-xl">Petunjuk pengisian</h3>
                    <p class="mt-1.5 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        Pilih angka {{ $likertMin }}&ndash;{{ $likertMax }} yang paling menggambarkan diri Anda pada
                        <strong>{{ $questions->count() }} pernyataan</strong> berikut. Tidak ada jawaban benar atau salah.
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2 text-xs text-gray-700 dark:text-gray-200">
                        <span class="rounded-md bg-gray-100 px-2.5 py-1 dark:bg-gray-700"><strong>{{ $likertMin }}</strong> = {{ $likertFirst }}</span>
                        <span class="rounded-md bg-gray-100 px-2.5 py-1 dark:bg-gray-700"><strong>{{ $likertMax }}</strong> = {{ $likertLast }}</span>
                    </div>
                </div>

        {{-- Di HP/tablet kemajuan cukup ditunjukkan bilah tipis di app bar; yang
             perlu tetap tampil di sini hanya peringatan gagal simpan. --}}
        <p class="mt-4 rounded-lg bg-rose-50 px-3 py-2 text-xs text-rose-700 dark:bg-rose-500/10 dark:text-rose-300 lg:hidden"
           x-show="saveState === 'error'" x-cloak x-text="saveMessage"></p>

        {{-- Bilah kemajuan desktop yang menempel di bawah app bar (sticky top-0 z-30)
             saat digulir. Offset 65px = tinggi app bar, supaya tidak tertutup. --}}
        <div class="sticky top-[65px] z-20 mt-6 hidden rounded-xl border border-brand-100 bg-white/95 px-4 py-3 backdrop-blur dark:border-white/10 dark:bg-ink-900/95 lg:block">
            <div class="flex items-center gap-3 text-sm">
                <span class="shrink-0 font-medium text-gray-700 dark:text-gray-200">
                    <span x-text="answeredCount"></span>/{{ $questions->count() }}
                    <span class="hidden sm:inline">terjawab</span>
                </span>
                <div class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-full rounded-full bg-brand-600 transition-all duration-300" :style="`width: ${percent}%`"></div>
                </div>
                <span class="shrink-0 font-semibold text-brand-600 dark:text-brand-400" x-text="percent + '%'"></span>
            </div>
            {{-- Penanda simpan otomatis: calon mahasiswa perlu tahu jawabannya sudah aman di server. --}}
            <p class="mt-1.5 text-xs" x-show="saveState !== 'idle'" x-cloak
               :class="{
                   'text-gray-500 dark:text-gray-400': saveState === 'saving',
                   'text-emerald-600 dark:text-emerald-400': saveState === 'saved',
                   'text-rose-600 dark:text-rose-400': saveState === 'error',
               }"
               x-text="saveMessage"></p>
        </div>

        <div class="mx-auto max-w-none px-0">
            <form method="POST" action="{{ route('assessments.answers.store', $assessment) }}"
                  @submit="onSubmit($event)" class="mt-4 space-y-3 sm:mt-6 sm:space-y-4">
                @csrf

                @foreach ($questions as $index => $question)
                    @php($questionColor = $questionPalettes[$question->dimension] ?? '#42A5F5')
                    @php($questionIcon = $questionIcons[$question->dimension] ?? 'sparkles')
                    <div id="question-{{ $index }}" data-question-id="{{ $question->id }}"
                         class="riasec-question-card relative scroll-mt-32 overflow-hidden rounded-2xl border bg-white p-4 shadow-sm shadow-ink-950/5 transition dark:bg-white/[0.06] dark:shadow-black/10 sm:p-6"
                         style="--question-color: {{ $questionColor }}">
                        <x-dynamic-component :component="'heroicon-o-'.$questionIcon" class="riasec-question-silhouette pointer-events-none absolute -bottom-7 -right-5 hidden h-32 w-32 sm:block" aria-hidden="true" />

                        {{-- Tanpa nomor soal: teks pernyataan dan deretan lingkaran sama-sama
                             mulai dari tepi kiri kartu, jadi tidak ada indentasi. --}}
                        <p class="relative z-10 text-[15px] font-medium leading-relaxed text-gray-800 dark:text-gray-100">{{ $question->statement }}</p>

                        {{-- Skala berupa lingkaran bernomor (target sentuh 44px) yang
                             disebar rata kiri-kanan selebar kartu, dengan label kedua
                             ujung di bawahnya pada garis tepi yang sama. --}}
                        <div class="relative z-10 mt-4 sm:inline-block">
                            <div class="flex items-center justify-between gap-1.5 sm:justify-start sm:gap-3"
                                 role="radiogroup" aria-label="Jawaban pernyataan {{ $index + 1 }}">
                                @foreach ($likert as $value => $label)
                                    <label class="cursor-pointer" title="{{ $label }}">
                                        <input type="radio" class="peer sr-only"
                                               name="answers[{{ $question->id }}]" value="{{ $value }}"
                                               aria-label="{{ $value }} &mdash; {{ $label }}"
                                               x-model="answers['{{ $question->id }}']"
                                               @change="advance({{ $index }})">
                                        <span class="flex h-11 w-11 items-center justify-center rounded-full border-2 border-gray-300 bg-white text-base font-semibold text-gray-600 transition active:scale-95
                                                     hover:border-brand-400 hover:bg-brand-50
                                                     peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/50
                                                     peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white
                                                     dark:border-gray-600 dark:bg-transparent dark:text-gray-300 dark:hover:bg-white/10 dark:peer-checked:border-brand-500 dark:peer-checked:bg-brand-600 dark:peer-checked:text-white
                                                     sm:h-12 sm:w-12">
                                            {{ $value }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                            <div class="mt-2 flex justify-between gap-4 text-[11px] leading-tight text-gray-500 dark:text-gray-400">
                                <span>{{ $likertFirst }}</span>
                                <span class="text-right">{{ $likertLast }}</span>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Area kirim berada di aliran halaman (bukan sticky) supaya tidak
                     menimpa kartu pertanyaan di layar sempit. --}}
                <div id="questionnaire-submit" class="rounded-2xl border border-brand-100 bg-white p-4 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.06] sm:p-5">
                    <p class="text-sm text-gray-600 dark:text-gray-300" x-show="!isComplete">
                        Masih ada <span class="font-semibold" x-text="{{ $questions->count() }} - answeredCount"></span>
                        pernyataan belum dijawab.
                        <button type="button" @click="goToUnanswered()"
                                class="ms-1 font-semibold text-brand-600 underline-offset-2 hover:underline dark:text-brand-400">
                            Lihat
                        </button>
                    </p>
                    <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400" x-show="isComplete" x-cloak>
                        Semua pernyataan sudah terjawab.
                    </p>

                    <div class="mt-3 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <a href="{{ route('assessments.create', ['resume' => 1, 'step' => 3]) }}"
                           class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            <x-heroicon-o-arrow-left class="h-4 w-4" />
                            Kembali ke Prioritas Prodi
                        </a>

                        <button type="submit" :disabled="!isComplete"
                                class="w-full rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-gray-300 dark:disabled:bg-gray-600 sm:w-auto">
                            Proses &amp; Lihat Hasil
                        </button>
                    </div>
                </div>
            </form>
        </div>
                </div>
            </div>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            function questionnaire({ total, storageKey, initial, autosaveUrl }) {
                return {
                    total,
                    storageKey,
                    autosaveUrl,
                    answers: {},
                    saveState: 'idle',
                    saveMessage: '',
                    timer: null,
                    pending: false,

                    init() {
                        // Draft lokal mencegah jawaban hilang bila halaman tertutup
                        // sebelum sempat dikirim. Data server tetap menang.
                        let draft = {};
                        try {
                            draft = JSON.parse(localStorage.getItem(this.storageKey) || '{}');
                        } catch (error) {
                            draft = {};
                        }

                        this.answers = { ...draft, ...initial };

                        // Bilah kemajuan di app bar membaca store global, karena
                        // berada di luar cakupan x-data halaman ini.
                        this.$store.wizard.fraction = this.total ? this.answeredCount / this.total : 0;

                        // Draft lokal yang belum sempat terkirim langsung
                        // disusulkan ke server begitu halaman dibuka lagi.
                        if (Object.keys(draft).some((id) => !(id in initial))) {
                            this.schedule();
                        }

                        this.$watch('answers', (value) => {
                            this.$store.wizard.fraction = this.total ? this.answeredCount / this.total : 0;
                            localStorage.setItem(this.storageKey, JSON.stringify(value));
                            this.schedule();
                        });
                    },

                    /**
                     * Menunda pengiriman sebentar supaya rentetan klik cepat
                     * menjadi satu permintaan, bukan satu permintaan per butir.
                     */
                    schedule() {
                        clearTimeout(this.timer);
                        this.timer = setTimeout(() => this.save(), 1200);
                    },

                    async save() {
                        if (this.pending) {
                            // Satu pengiriman sedang berjalan; coba lagi setelahnya
                            // agar jawaban terakhir tidak terlewat.
                            this.schedule();
                            return;
                        }

                        const payload = Object.fromEntries(
                            Object.entries(this.answers).filter(([, value]) => value !== '' && value !== null)
                        );

                        if (Object.keys(payload).length === 0) {
                            return;
                        }

                        this.pending = true;
                        this.saveState = 'saving';
                        this.saveMessage = 'Menyimpan…';

                        try {
                            const response = await fetch(this.autosaveUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({ answers: payload }),
                            });

                            if (!response.ok) {
                                throw new Error('Gagal menyimpan');
                            }

                            const data = await response.json();
                            this.saveState = 'saved';
                            this.saveMessage = `Tersimpan otomatis ${data.saved_at}`;
                        } catch (error) {
                            // Jawaban tetap aman di localStorage, jadi kegagalan
                            // jaringan cukup diberitahukan tanpa menghentikan apa pun.
                            this.saveState = 'error';
                            this.saveMessage = 'Gagal tersimpan — jawaban masih tersimpan di perangkat ini';
                        } finally {
                            this.pending = false;
                        }
                    },

                    /**
                     * Di HP, setelah menjawab, gulir halus ke pernyataan berikutnya
                     * bila belum dijawab — pengguna tidak perlu menggulir manual
                     * untuk tiap butir. Jeda singkat memberi waktu umpan balik
                     * pilihan terlihat dan x-model selesai memperbarui jawaban.
                     */
                    advance(index) {
                        if (!window.matchMedia('(max-width: 639px)').matches) {
                            return;
                        }

                        setTimeout(() => {
                            const next = document.getElementById(`question-${index + 1}`);

                            if (next && !this.answers[next.dataset.questionId]) {
                                next.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        }, 220);
                    },

                    /** Lompat ke pernyataan pertama yang belum dijawab. */
                    goToUnanswered() {
                        const target = [...document.querySelectorAll('[data-question-id]')]
                            .find((card) => !this.answers[card.dataset.questionId]);

                        target?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    },

                    get answeredCount() {
                        return Object.values(this.answers).filter((value) => value !== '' && value !== null).length;
                    },

                    get percent() {
                        return this.total ? Math.round((this.answeredCount / this.total) * 100) : 0;
                    },

                    get isComplete() {
                        return this.answeredCount >= this.total;
                    },

                    onSubmit(event) {
                        if (!this.isComplete) {
                            event.preventDefault();
                            return;
                        }

                        localStorage.removeItem(this.storageKey);
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
