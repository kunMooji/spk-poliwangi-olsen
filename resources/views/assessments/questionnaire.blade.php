<x-app-layout>
    <x-slot name="header">
        <div class="space-y-2">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Kuesioner Minat Bakat RIASEC
            </h2>
            <x-assessment-steps :current="2" />
        </div>
    </x-slot>

    @php
        // Jawaban yang sudah tersimpan di server dipakai sebagai nilai awal;
        // draft di localStorage hanya menambal butir yang belum tersimpan.
        $initial = $saved->mapWithKeys(fn ($score, $id) => [(string) $id => (int) $score]);
    @endphp

    <div class="py-8"
         x-data="questionnaire({
             total: {{ $questions->count() }},
             storageKey: 'spk-draft-{{ $assessment->code }}',
             initial: {{ Js::from($initial) }},
             autosaveUrl: '{{ route('assessments.answers.autosave', $assessment) }}',
         })"
         x-init="init()">

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Petunjuk Pengisian</h3>
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
        </div>

        {{-- Bilah kemajuan yang menempel di atas saat digulir --}}
        <div class="sticky top-0 z-10 mt-6 border-y border-gray-200 bg-white/95 backdrop-blur dark:border-gray-700 dark:bg-gray-800/95">
            <div class="mx-auto max-w-4xl px-4 py-3 sm:px-6 lg:px-8">
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

        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('assessments.answers.store', $assessment) }}"
                  @submit="onSubmit($event)" class="mt-6 space-y-4">
                @csrf

                @foreach ($questions as $index => $question)
                    <div class="rounded-xl bg-white p-5 shadow-sm transition dark:bg-gray-800"
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

                <div class="sticky bottom-0 rounded-t-xl border-t border-gray-200 bg-white/95 p-4 backdrop-blur dark:border-gray-700 dark:bg-gray-800/95">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-gray-500 dark:text-gray-400" x-show="!isComplete">
                            Masih ada <span class="font-semibold" x-text="{{ $questions->count() }} - answeredCount"></span>
                            pernyataan yang belum dijawab.
                        </p>
                        <p class="text-sm font-medium text-emerald-600 dark:text-emerald-400" x-show="isComplete" x-cloak>
                            Semua pernyataan sudah terjawab.
                        </p>

                        <button type="submit" :disabled="!isComplete"
                                class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:bg-gray-300 dark:disabled:bg-gray-600">
                            Proses &amp; Lihat Hasil
                        </button>
                    </div>
                </div>
            </form>
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
