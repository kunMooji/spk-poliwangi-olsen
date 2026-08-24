<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Kuesioner Minat Bakat RIASEC
        </h2>
    </x-slot>

    @php
        // Jawaban yang sudah tersimpan di server dipakai sebagai nilai awal;
        // draft di localStorage hanya menambal butir yang belum tersimpan.
        $initial = $saved->mapWithKeys(fn ($score, $id) => [(string) $id => (int) $score]);
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

            <section class="relative rounded-[1.75rem] border border-brand-100 bg-[radial-gradient(circle_at_86%_0%,rgba(179,227,236,.65),transparent_28%),linear-gradient(135deg,#ffffff,#eff9fb)] p-5 shadow-xl shadow-ink-950/5 dark:border-white/10 dark:bg-[radial-gradient(circle_at_78%_7%,rgba(27,137,163,.30),transparent_24%),linear-gradient(135deg,#071b29,#0b1627_55%,#14243a)] dark:shadow-2xl dark:shadow-ink-950/20 sm:p-7 lg:p-9">
                <div class="relative flex flex-wrap items-center justify-between gap-5">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white"><x-heroicon-o-clipboard-document-check class="h-6 w-6" /></span>
                        <div><p class="font-mono text-[10px] font-bold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-200">Langkah 4 dari 4</p><h1 class="mt-1 text-xl font-bold tracking-tight text-ink-950 dark:text-white sm:text-2xl">Kuesioner Minat Bakat RIASEC</h1><p class="mt-1 max-w-xl text-sm leading-relaxed text-ink-500 dark:text-porcelain-200/75">Jawab setiap pernyataan sesuai diri Anda untuk melengkapi rekomendasi program studi.</p></div>
                    </div>
                    <div class="hidden items-end gap-3 sm:flex"><p class="max-w-[9.5rem] -rotate-3 font-script text-lg leading-tight text-brand-600 dark:text-brand-300">Kenali diri, tentukan arahmu</p><img src="{{ asset('images/ilastrasi_toga.png') }}" alt="" class="h-20 w-auto shrink-0"></div>
                </div>

            <div class="relative mt-8 grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)] lg:items-start">
                <x-assessment-progress :current="4" fraction-expression="answeredCount / total" sticky-top="lg:top-20" clickable />

                <div class="min-w-0">
                <div class="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.06] dark:shadow-black/10 sm:p-8">
                    <p class="font-mono text-[10px] font-bold uppercase tracking-[0.2em] text-brand-600 dark:text-brand-400">Sebelum mulai</p>
                    <h3 class="mt-2 text-2xl font-semibold tracking-tight text-gray-950 dark:text-gray-100">Petunjuk pengisian</h3>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600 dark:text-gray-300">
                        Terdapat <strong>{{ $questions->count() }} pernyataan</strong>. Untuk setiap pernyataan, pilih
                        angka yang paling menggambarkan diri Anda pada skala 1 sampai 5. Tidak ada jawaban benar
                        atau salah &mdash; jawablah sejujurnya sesuai keadaan Anda.
                    </p>

                    <div class="mt-4 flex flex-wrap gap-2 text-xs">
                        @foreach ($likert as $value => $label)
                            <span class="rounded-md bg-gray-100 px-2.5 py-1 text-gray-700 dark:bg-gray-700 dark:text-gray-200">
                                <strong>{{ $value }}</strong> = {{ $label }}
                            </span>
                        @endforeach
                    </div>

                    <p class="mt-4 rounded-lg bg-sky-50 px-3 py-2 text-xs leading-relaxed text-sky-800 dark:bg-sky-900/30 dark:text-sky-200">
                        Jawaban Anda <strong>tersimpan otomatis</strong> beberapa saat setelah dipilih. Bila halaman
                        tertutup atau Anda berpindah perangkat, pengisian dapat dilanjutkan dari butir terakhir
                        &mdash; tidak perlu mengulang dari awal.
                        @if ($saved->isNotEmpty())
                            Saat ini <strong>{{ $saved->count() }} jawaban</strong> Anda sudah tersimpan.
                        @endif
                    </p>
                </div>

        {{-- Bilah kemajuan yang menempel di atas saat digulir --}}
        <div class="sticky top-0 z-10 mt-6 rounded-xl border border-brand-100 bg-white/95 px-4 backdrop-blur dark:border-white/10 dark:bg-ink-900/95">
            <div class="mx-auto max-w-none px-0 py-4">
                <div class="flex items-center justify-between text-sm">
                    <span class="font-medium text-gray-700 dark:text-gray-200">
                        Terjawab <span x-text="answeredCount"></span> dari {{ $questions->count() }}
                    </span>
                    <div class="flex items-center gap-3">
                        {{-- Penanda simpan otomatis: calon mahasiswa perlu tahu
                             jawabannya sudah aman di server, bukan sekadar di layar. --}}
                        <span class="text-xs" x-show="saveState !== 'idle'" x-cloak
                              :class="{
                                  'text-gray-500 dark:text-gray-400': saveState === 'saving',
                                  'text-emerald-600 dark:text-emerald-400': saveState === 'saved',
                                  'text-rose-600 dark:text-rose-400': saveState === 'error',
                              }"
                              x-text="saveMessage"></span>
                        <span class="font-semibold text-brand-600 dark:text-brand-400" x-text="percent + '%'"></span>
                    </div>
                </div>
                <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                    <div class="h-full rounded-full bg-brand-600 transition-all duration-300" :style="`width: ${percent}%`"></div>
                </div>
            </div>
        </div>

        <div class="mx-auto max-w-none px-0">
            <form method="POST" action="{{ route('assessments.answers.store', $assessment) }}"
                  @submit="onSubmit($event)" class="mt-6 space-y-4">
                @csrf

                @foreach ($questions as $index => $question)
                    <div class="rounded-2xl border border-brand-100 bg-white p-5 shadow-sm shadow-ink-950/5 transition hover:border-brand-300 dark:border-white/10 dark:bg-white/[0.06] dark:shadow-black/10 sm:p-6"
                         :class="answers['{{ $question->id }}'] ? '' : 'ring-1 ring-amber-300 dark:ring-amber-700'">
                        <div class="flex gap-3">
                            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                {{ $index + 1 }}
                            </span>
                            <p class="text-sm leading-relaxed text-gray-800 dark:text-gray-100">{{ $question->statement }}</p>
                        </div>

                        <div class="mt-4 grid grid-cols-5 gap-2 ps-10">
                            @foreach ($likert as $value => $label)
                                <label class="cursor-pointer">
                                    <input type="radio" class="peer sr-only"
                                           name="answers[{{ $question->id }}]" value="{{ $value }}"
                                           x-model="answers['{{ $question->id }}']">
                                    <span class="block rounded-lg border border-gray-300 px-2 py-2 text-center text-xs transition
                                                 hover:border-brand-400 hover:bg-brand-50
                                                 peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white
                                                 dark:border-gray-600 dark:hover:bg-gray-700 dark:peer-checked:border-brand-500 dark:peer-checked:bg-brand-600">
                                        <span class="block text-base font-bold">{{ $value }}</span>
                                        <span class="mt-0.5 block leading-tight">{{ $label }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="sticky bottom-0 rounded-2xl border border-brand-100 bg-white/95 p-4 shadow-lg shadow-ink-950/10 backdrop-blur dark:border-white/10 dark:bg-ink-900/95 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-4">
                            <a href="{{ route('assessments.create', ['resume' => 1, 'step' => 3]) }}"
                               class="inline-flex shrink-0 items-center gap-2 rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:-translate-y-0.5 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                <x-heroicon-o-arrow-left class="h-4 w-4" />
                                Kembali ke Prioritas Prodi
                            </a>

                            <p class="text-sm text-gray-500 dark:text-gray-400" x-show="!isComplete">
                                Masih ada <span class="font-semibold" x-text="{{ $questions->count() }} - answeredCount"></span>
                                pernyataan yang belum dijawab.
                            </p>
                            <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400" x-show="isComplete" x-cloak>
                                Semua pernyataan sudah terjawab.
                            </p>
                        </div>

                        <button type="submit" :disabled="!isComplete"
                                class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-gray-300 dark:disabled:bg-gray-600">
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

                        // Draft lokal yang belum sempat terkirim langsung
                        // disusulkan ke server begitu halaman dibuka lagi.
                        if (Object.keys(draft).some((id) => !(id in initial))) {
                            this.schedule();
                        }

                        this.$watch('answers', (value) => {
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
