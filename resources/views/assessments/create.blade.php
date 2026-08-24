<x-app-layout>
    @php
        // Langkah awal wizard mengikuti letak error validasi (kalau ada), supaya
        // pengguna yang gagal validasi langsung diarahkan ke langkah yang
        // bermasalah alih-alih selalu kembali ke langkah 1.
        $initialStep = max(1, min(3, (int) request()->query('step', 1)));

        $raporHasError = $errors->has('rapor_semesters')
            || collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'rapor_semesters.'));

        $prodiHasError = $errors->has('priorities')
            || $errors->has('subject_scores')
            || collect($errors->keys())->contains(fn ($key) => str_starts_with($key, 'priorities.') || str_starts_with($key, 'subject_scores.'));

        $biodataFields = ['full_name', 'gender', 'phone', 'school_name', 'graduation_year', 'education_level', 'school_major'];
        $biodataHasError = collect($biodataFields)->contains(fn ($field) => $errors->has($field));

        if ($prodiHasError) {
            $initialStep = 3;
        }
        if ($raporHasError) {
            $initialStep = 2;
        }
        if ($biodataHasError) {
            $initialStep = 1;
        }
    @endphp

    <div class="py-8 sm:py-10">
        <div class="mx-auto flex max-w-none flex-col gap-6 px-5 sm:px-8 lg:px-10 xl:px-12">
            <x-flash />

            {{-- Banner halaman: menggantikan judul polos bawaan layout supaya
                 wizard tes terasa sebagai "acara" tersendiri, bukan halaman
                 admin biasa. Ilustrasi & catatan tangan di kanan disembunyikan
                 di layar sempit karena hanya dekoratif. --}}
            <section class="relative shrink-0 rounded-[1.75rem] border border-brand-100 bg-[radial-gradient(circle_at_86%_0%,rgba(179,227,236,.65),transparent_28%),linear-gradient(135deg,#ffffff,#eff9fb)] p-6 shadow-xl shadow-ink-950/5 dark:border-white/10 dark:bg-[radial-gradient(circle_at_78%_7%,rgba(27,137,163,.30),transparent_24%),linear-gradient(135deg,#071b29,#0b1627_55%,#14243a)] dark:shadow-2xl dark:shadow-ink-950/20 sm:p-8">
                <div class="relative flex flex-wrap items-center justify-between gap-6">
                    <div class="flex items-start gap-4">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-brand-600 text-white">
                            <x-heroicon-o-user-group class="h-6 w-6" />
                        </span>
                        <div>
                            <h1 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
                                Biodata, Nilai Rapor &amp; Prioritas Prodi
                            </h1>
                            <p class="mt-1 max-w-xl text-sm text-gray-500 dark:text-gray-400">
                                Lengkapi data diri dan nilai rapor Anda untuk mendapatkan rekomendasi prodi yang
                                sesuai dengan potensi dan minat Anda.
                            </p>
                        </div>
                    </div>

                    <div class="hidden shrink-0 items-end gap-3 sm:flex">
                        <p class="max-w-[9.5rem] -rotate-3 font-script text-lg leading-tight text-brand-600 dark:text-brand-400">
                            Langkah kecil menuju masa depan besar
                        </p>
                        <img src="{{ asset('images/ilastrasi_toga.png') }}" alt="" class="h-20 w-auto shrink-0">
                    </div>
                </div>

          <div class="relative mt-8 grid gap-6 lg:grid-cols-[280px_minmax(0,1fr)] lg:items-start">
                <x-assessment-progress reactive sticky-top="lg:top-20"
                    tip-title="Pastikan data yang Anda isi benar"
                    tip-body="Data ini akan digunakan untuk proses rekomendasi prodi yang lebih akurat." />

                {{--
                    Seluruh formulir berbagi satu lingkup Alpine: jenjang dan jurusan
                    yang dipilih pada biodata menentukan mapel mana yang ditanyakan di
                    bagian nilai pendukung, sementara prodi yang dipilih menentukan
                    mapel mana yang disorot. Langkah aktif disimpan di $store.wizard
                    (global), bukan state lokal, supaya kartu kemajuan di kiri ikut
                    bereaksi meski berada di luar cakupan x-data formulir ini.
                --}}
                <form method="POST" action="{{ route('assessments.store') }}" class="min-w-0"
                      x-data="raporForm({
                          programSubjects: @js($programSubjectMap),
                          programNames: @js($programs->pluck('full_name', 'id')),
                          priorities: @js(array_values((array) old('priorities', []))),
                          added: @js($addedSubjects),
                          subjectProfiles: @js($subjectProfiles),
                          extraSubjects: @js($extraSubjects),
                          majorOptions: @js($majorOptions),
                          level: @js(old('education_level', '')),
                          major: @js(old('school_major', '')),
                          commonGroup: @js(\App\Support\Rapor::COMMON_GROUP),
                      })"
                      x-init="$store.wizard.step = {{ $initialStep }}; $nextTick(() => restoreDraft())"
                      @submit="saveDraft($event)">
                    @csrf

                {{--
                    Sengaja TANPA space-y-6 di sini. Ketiga panel langkah
                    (Biodata, Nilai Rapor, panel langkah 3) saling menyembunyikan
                    lewat x-show — hanya satu yang tampak sekaligus — tapi
                    elemen yang disembunyikan (display:none) tetap dihitung
                    sebagai "sibling" oleh selector space-y. Akibatnya panel
                    manapun yang BUKAN anak pertama tetap kebagian margin-top
                    dari panel tersembunyi di depannya, sehingga kartunya
                    turun dan tidak sejajar dengan kartu Progress Assessment
                    di sampingnya begitu ia ditampilkan. Jarak ke baris tombol
                    di bawah cukup diberi mt-6 langsung di elemen tombolnya.
                --}}
                <div>

                {{-- Langkah 1: Biodata --}}
                <section x-show="$store.wizard.step === 1" x-cloak data-step-panel="1"
                         class="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.06] dark:shadow-black/10">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                            <x-heroicon-o-user class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Biodata</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Data ini muncul pada lembar hasil tes Anda.</p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="full_name">Nama Lengkap <span class="text-rose-500">*</span></x-input-label>
                            <div class="relative mt-1">
                                <x-heroicon-o-user class="pointer-events-none absolute inset-y-0 left-3 my-auto h-4 w-4 text-gray-400" />
                                <x-text-input id="full_name" name="full_name" type="text" class="block w-full pl-9"
                                              :value="old('full_name', auth()->user()->name)" required autofocus />
                            </div>
                            <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="gender">Jenis Kelamin <span class="text-rose-500">*</span></x-input-label>
                            <div class="relative mt-1">
                                <x-heroicon-o-identification class="pointer-events-none absolute inset-y-0 left-3 my-auto h-4 w-4 text-gray-400" />
                                <select id="gender" name="gender"
                                        class="block w-full rounded-lg border-gray-300 pl-9 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    <option value="">— Pilih —</option>
                                    <option value="L" @selected(old('gender') === 'L')>Laki-laki</option>
                                    <option value="P" @selected(old('gender') === 'P')>Perempuan</option>
                                </select>
                            </div>
                            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="phone">Nomor HP <span class="text-rose-500">*</span></x-input-label>
                            <div class="relative mt-1">
                                <x-heroicon-o-phone class="pointer-events-none absolute inset-y-0 left-3 my-auto h-4 w-4 text-gray-400" />
                                <x-text-input id="phone" name="phone" type="text" class="block w-full pl-9"
                                              :value="old('phone')" placeholder="08xxxxxxxxxx" />
                            </div>
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="school_name">Asal Sekolah <span class="text-rose-500">*</span></x-input-label>
                            <div class="relative mt-1">
                                <x-heroicon-o-building-library class="pointer-events-none absolute inset-y-0 left-3 my-auto h-4 w-4 text-gray-400" />
                                <x-text-input id="school_name" name="school_name" type="text" class="block w-full pl-9"
                                              :value="old('school_name')" placeholder="SMA Negeri 1 Banyuwangi" />
                            </div>
                            <x-input-error :messages="$errors->get('school_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="graduation_year">Tahun Lulus <span class="text-rose-500">*</span></x-input-label>
                            <div class="relative mt-1">
                                <x-heroicon-o-calendar class="pointer-events-none absolute inset-y-0 left-3 my-auto h-4 w-4 text-gray-400" />
                                <x-text-input id="graduation_year" name="graduation_year" type="number" class="block w-full pl-9"
                                              :value="old('graduation_year', date('Y'))" min="2000" max="{{ date('Y') + 1 }}" />
                            </div>
                            <x-input-error :messages="$errors->get('graduation_year')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="education_level">Jenjang Sekolah <span class="text-rose-500">*</span></x-input-label>
                            {{-- Jurusan dikosongkan saat jenjang berganti: pilihan SMA tidak berlaku di SMK. --}}
                            <div class="relative mt-1">
                                <x-heroicon-o-academic-cap class="pointer-events-none absolute inset-y-0 left-3 my-auto h-4 w-4 text-gray-400" />
                                <select id="education_level" name="education_level" x-model="level" required
                                        x-init="$watch('level', () => { major = '' })"
                                        class="block w-full rounded-lg border-gray-300 pl-9 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    <option value="">— Pilih —</option>
                                    @foreach ($educationLevels as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <x-input-error :messages="$errors->get('education_level')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="school_major">
                                <span x-text="level === 'SMK' ? 'Rumpun Keahlian' : 'Jurusan'">Jurusan</span>
                                <span class="text-rose-500">*</span>
                            </x-input-label>
                            <div class="relative mt-1">
                                <x-heroicon-o-book-open class="pointer-events-none absolute inset-y-0 left-3 my-auto h-4 w-4 text-gray-400" />
                                <select id="school_major" name="school_major" x-model="major" :disabled="! level"
                                        class="block w-full rounded-lg border-gray-300 pl-9 shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-100 disabled:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:disabled:bg-gray-800">
                                    <option value="" x-text="level ? '— Pilih —' : '— Pilih jenjang dulu —'">— Pilih jenjang dulu —</option>
                                    <template x-for="option in availableMajors" :key="option">
                                        <option :value="option" x-text="option"></option>
                                    </template>
                                </select>
                            </div>
                            <p class="mt-1 text-xs text-gray-400" x-show="level === 'SMK'" x-cloak>
                                Pilih rumpun sesuai konsentrasi keahlian yang tercantum di rapor Anda.
                            </p>
                            <x-input-error :messages="$errors->get('school_major')" class="mt-2" />
                        </div>
                    </div>
                </section>

                {{-- Langkah 2: Komponen pertama SNBP — rerata rapor seluruh mapel per semester --}}
                <section x-show="$store.wizard.step === 2" x-cloak data-step-panel="2"
                         class="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.06] dark:shadow-black/10">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                            <x-heroicon-o-chart-bar class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rerata Rapor per Semester</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                Masukkan nilai rata-rata <strong>seluruh mata pelajaran</strong> pada setiap semester,
                                skala 0&ndash;100. Semester terakhir memang tidak diminta &mdash; SNBP memeringkat
                                berdasarkan semua semester kecuali yang terakhir.
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-5 sm:grid-cols-5">
                        @foreach ($semesters as $semester)
                            <div>
                                <x-input-label :for="'rapor_semesters_'.$semester" :value="'Semester '.$semester" />
                                <x-text-input :id="'rapor_semesters_'.$semester" :name="'rapor_semesters['.$semester.']'"
                                              type="number" step="0.01" min="0" max="100"
                                              class="mt-1 block w-full" :value="old('rapor_semesters.'.$semester)" required />
                                <x-input-error :messages="$errors->get('rapor_semesters.'.$semester)" class="mt-2" />
                            </div>
                        @endforeach
                    </div>
                </section>

                {{-- Langkah 3: pemilihan program studi & nilai mapel pendukung --}}
                <div x-show="$store.wizard.step === 3" x-cloak data-step-panel="3" class="space-y-6">

                    {{-- Prioritas prodi --}}
                    <section class="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.06] dark:shadow-black/10">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                <x-heroicon-o-list-bullet class="h-5 w-5" />
                            </span>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Urutan Prioritas Program Studi</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Pilih minimal {{ $minPriorities }} program studi sesuai urutan minat Anda. Prioritas
                                    pertama memperoleh skor minat tertinggi. Program studi tidak boleh dipilih dua kali.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 space-y-4">
                            @for ($i = 0; $i < $maxPriorities; $i++)
                                <div>
                                    <x-input-label :for="'priority_'.$i">
                                        Prioritas ke-{{ $i + 1 }}
                                        @if ($i < $minPriorities)
                                            <span class="text-rose-500">*</span>
                                        @else
                                            <span class="text-xs font-normal text-gray-400">(opsional)</span>
                                        @endif
                                    </x-input-label>
                                    <select id="priority_{{ $i }}" name="priorities[{{ $i }}]"
                                            x-model="priorities[{{ $i }}]"
                                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                            @if ($i < $minPriorities) required @endif>
                                        <option value="">— Pilih program studi —</option>
                                        @foreach ($programs as $program)
                                            <option value="{{ $program->id }}">
                                                {{ $program->full_name }} ({{ $program->department }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('priorities.'.$i)" class="mt-2" />
                                </div>
                            @endfor
                        </div>
                    </section>

                    {{-- Komponen kedua SNBP: nilai mapel pendukung prodi --}}
                    <section class="rounded-2xl border border-brand-100 bg-white p-6 shadow-sm shadow-ink-950/5 dark:border-white/10 dark:bg-white/[0.06] dark:shadow-black/10">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                                <x-heroicon-o-book-open class="h-5 w-5" />
                            </span>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Nilai Mata Pelajaran Pendukung</h3>
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Daftar ini menyesuaikan jenjang dan jurusan yang Anda pilih di biodata, sehingga
                                    Anda tidak diminta mengisi mata pelajaran yang tidak pernah Anda tempuh. Yang
                                    ditanyakan hanya mata pelajaran pendukung dari
                                    <strong>program studi yang Anda jadikan prioritas</strong> di atas &mdash; program
                                    studi lain tidak memerlukan isian di sini. <strong>Kosongkan</strong> mata
                                    pelajaran yang tidak ada di rapor Anda &mdash; nilainya akan digantikan rerata
                                    rapor Anda, bukan dianggap nol.
                                </p>
                            </div>
                        </div>

                        <p class="mt-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300"
                           x-show="! level" x-cloak>
                            Pilih <strong>jenjang sekolah</strong> pada biodata di atas untuk menampilkan daftar mata
                            pelajaran yang sesuai.
                        </p>

                        <p class="mt-3 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:bg-amber-500/10 dark:text-amber-300"
                           x-show="level && selectedPrograms.length === 0" x-cloak>
                            Pilih <strong>program studi prioritas</strong> di atas untuk menampilkan mata pelajaran
                            pendukungnya.
                        </p>

                        <div class="mt-5 grid gap-5 sm:grid-cols-3">
                            @foreach ($supportSubjects as $subject)
                                {{--
                                    Hanya mapel pendukung milik prodi prioritas yang ditanyakan.
                                    Prodi lain tidak dirugikan karena tidak diisi: C2-nya diambil
                                    dari nilai semester terendah responden (lihat
                                    DecisionMatrixBuilder::supportSubjectValue), bukan rerata —
                                    supaya prodi yang memang tidak dipertimbangkan responden tidak
                                    diam-diam diuntungkan dan mengalahkan prodi prioritasnya sendiri.
                                --}}
                                <div x-show="matchesLevel({{ $subject->id }}) && isRelevant({{ $subject->id }})">
                                    <x-input-label :for="'subject_scores_'.$subject->id" :value="$subject->display_name" />

                                    <p class="mt-0.5 text-xs font-medium text-brand-600 dark:text-brand-400"
                                       x-text="'Pendukung ' + relevantLabel({{ $subject->id }})"></p>

                                    <x-text-input :id="'subject_scores_'.$subject->id" :name="'subject_scores['.$subject->id.']'"
                                                  type="number" step="0.01" min="0" max="100"
                                                  class="mt-1 block w-full" :value="old('subject_scores.'.$subject->id)" />
                                    <x-input-error :messages="$errors->get('subject_scores.'.$subject->id)" class="mt-2" />
                                </div>
                            @endforeach

                            {{-- Mapel yang ditambahkan sendiri oleh responden. --}}
                            <template x-for="subject in added" :key="subject.id">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                                           :for="'subject_scores_' + subject.id" x-text="subject.name"></label>
                                    <p class="mt-0.5 text-xs text-gray-400">ditambahkan sendiri</p>
                                    <div class="mt-1 flex gap-2">
                                        <input type="number" step="0.01" min="0" max="100"
                                               :id="'subject_scores_' + subject.id"
                                               :name="'subject_scores[' + subject.id + ']'"
                                               :value="subject.score"
                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        <button type="button" @click="remove(subject.id)"
                                                class="rounded-md border border-gray-300 px-3 text-sm text-gray-500 transition hover:bg-gray-50 dark:border-gray-600 dark:hover:bg-gray-700"
                                                title="Hapus mata pelajaran ini">&times;</button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        @if ($extraSubjects->isNotEmpty())
                            <template x-if="availableExtraSubjects.length > 0">
                                <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-gray-100 pt-5 dark:border-gray-700">
                                    <label class="text-sm text-gray-500 dark:text-gray-400" for="add_subject">
                                        Mata pelajaran Anda tidak ada di daftar?
                                    </label>
                                    <select id="add_subject" @change="add($event)"
                                            class="rounded-md border-gray-300 text-sm shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        <option value="">— Tambahkan mata pelajaran —</option>
                                        <template x-for="subject in availableExtraSubjects" :key="subject.id">
                                            <option :value="subject.id" :data-name="subject.name" x-text="subject.name"></option>
                                        </template>
                                    </select>
                                </div>
                            </template>
                        @endif

                        <x-input-error :messages="$errors->get('subject_scores')" class="mt-3" />
                    </section>
                </div>

                <div class="mt-6 flex items-center justify-between gap-3">
                    <div>
                        <a href="{{ route('dashboard') }}" x-show="$store.wizard.step === 1"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            <x-heroicon-o-arrow-left class="h-4 w-4" />
                            Batal
                        </a>
                        <button type="button" @click="prevStep()" x-show="$store.wizard.step > 1" x-cloak
                                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            <x-heroicon-o-arrow-left class="h-4 w-4" />
                            Kembali
                        </button>
                    </div>

                    <button type="button" @click="nextStep()" x-show="$store.wizard.step < 3"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                        Lanjut
                        <x-heroicon-o-arrow-right class="h-4 w-4" />
                    </button>
                    <button type="submit" x-show="$store.wizard.step === 3" x-cloak
                            class="inline-flex items-center gap-1.5 rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                        Lanjut ke Kuesioner
                        <x-heroicon-o-arrow-right class="h-4 w-4" />
                    </button>
                </div>
                </div>
                </form>
            </div>
            </section>
        </div>
    </div>

    @push('scripts')
        <script>
            /**
             * Menyaring mata pelajaran menurut jenjang dan jurusan responden,
             * menyorot mapel pendukung milik prodi yang sedang dipilih, serta
             * menampung mapel yang ditambahkan sendiri responden.
             *
             * Penyorotan hanya mengubah urutan dan penandaan. Penyaringan jenjang
             * benar-benar menyembunyikan isian: mapel yang tidak ditempuh tidak
             * terkirim, sehingga tersimpan sebagai null dan diperlakukan sama
             * dengan mapel yang sengaja dikosongkan.
             */
            function raporForm({
                programSubjects, programNames, priorities, added,
                subjectProfiles, extraSubjects, majorOptions, level, major, commonGroup,
            }) {
                return {
                    programSubjects,
                    programNames,
                    priorities: priorities ?? [],
                    added: added ?? [],
                    subjectProfiles,
                    extraSubjects,
                    majorOptions,
                    commonGroup,
                    level: level ?? '',
                    major: major ?? '',

                    restoreDraft() {
                        try {
                            const raw = sessionStorage.getItem('spk-assessment-draft');
                            if (!raw) return;

                            const values = JSON.parse(raw);
                            Object.entries(values).forEach(([name, entries]) => {
                                const fields = this.$root.querySelectorAll(`[name="${name}"]`);
                                fields.forEach((field) => {
                                    const value = entries.find((entry) => entry.value === field.value)?.value ?? entries[0]?.value ?? '';
                                    if (field.type === 'checkbox' || field.type === 'radio') {
                                        field.checked = entries.some((entry) => entry.value === field.value);
                                    } else {
                                        field.value = value;
                                    }
                                    field.dispatchEvent(new Event('input', { bubbles: true }));
                                    field.dispatchEvent(new Event('change', { bubbles: true }));
                                });
                            });

                            const restored = JSON.parse(raw);
                            this.priorities = Object.entries(restored)
                                .filter(([name]) => name.startsWith('priorities['))
                                .sort(([left], [right]) => left.localeCompare(right, undefined, { numeric: true }))
                                .map(([, entries]) => entries[0]?.value ?? '');
                            this.level = restored.education_level?.[0]?.value ?? this.level;
                            this.major = restored.school_major?.[0]?.value ?? this.major;
                        } catch (error) {
                            sessionStorage.removeItem('spk-assessment-draft');
                        }
                    },

                    saveDraft(event) {
                        const values = {};
                        new FormData(event.currentTarget).forEach((value, name) => {
                            (values[name] ??= []).push({ value: String(value) });
                        });
                        sessionStorage.setItem('spk-assessment-draft', JSON.stringify(values));
                    },

                    /**
                     * Pindah ke langkah berikutnya, ditolak kalau ada isian wajib
                     * yang belum valid pada panel yang sedang tampil. Memakai
                     * `:invalid` bawaan HTML (bukan pengecekan manual per field)
                     * supaya aturan seperti `required`, `min`, `max` pada input
                     * tetap satu sumber kebenaran dengan validasi peramban.
                     */
                    nextStep() {
                        const panel = this.$root.querySelector(`[data-step-panel="${this.$store.wizard.step}"]`);
                        const invalid = panel?.querySelector(':invalid');

                        if (invalid) {
                            invalid.reportValidity();
                            return;
                        }

                        if (this.$store.wizard.step < 3) {
                            this.$store.wizard.step++;
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    },

                    prevStep() {
                        if (this.$store.wizard.step > 1) {
                            this.$store.wizard.step--;
                            window.scrollTo({ top: 0, behavior: 'smooth' });
                        }
                    },

                    /** Jurusan atau rumpun keahlian yang tersedia pada jenjang terpilih. */
                    get availableMajors() {
                        return this.majorOptions[this.level] ?? [];
                    },

                    /**
                     * Mapel berjenjang "umum" ditempuh siapa pun. Selebihnya harus
                     * sejenjang, dan kelompoknya entah wajib pada jenjang itu atau
                     * sama dengan jurusan responden. Selama jurusan belum dipilih,
                     * seluruh mapel sejenjang ditampilkan agar daftarnya tidak kosong.
                     */
                    matchesLevel(subjectId) {
                        const profile = this.subjectProfiles[subjectId];

                        if (! profile) {
                            return true;
                        }

                        if (profile.level === 'umum') {
                            return true;
                        }

                        // Selama jenjang belum dipilih, hanya mapel umum yang tampil.
                        if (profile.level !== this.level) {
                            return false;
                        }

                        return ! this.major
                            || profile.group === this.commonGroup
                            || profile.group === this.major;
                    },

                    /** Mapel tambahan yang relevan dengan jenjang responden saja. */
                    get availableExtraSubjects() {
                        return this.extraSubjects.filter((subject) => {
                            if (subject.level === 'umum') {
                                return true;
                            }

                            if (subject.level !== this.level) {
                                return false;
                            }

                            return ! this.major
                                || subject.group === this.commonGroup
                                || subject.group === this.major;
                        });
                    },

                    /** Prodi terpilih, urut mengikuti nomor prioritas, tanpa slot kosong. */
                    get selectedPrograms() {
                        return this.priorities.filter((id) => id !== '' && id !== null);
                    },

                    /** Nomor prioritas prodi yang memakai mapel ini. */
                    usedBy(subjectId) {
                        return this.selectedPrograms
                            .map((programId, index) => ({ programId, order: index + 1 }))
                            .filter(({ programId }) => (this.programSubjects[programId] ?? []).includes(subjectId));
                    },

                    isRelevant(subjectId) {
                        return this.usedBy(subjectId).length > 0;
                    },

                    relevantLabel(subjectId) {
                        return this.usedBy(subjectId)
                            .map(({ programId, order }) => `pilihan ${order} — ${this.programNames[programId]}`)
                            .join(', ');
                    },

                    add(event) {
                        const option = event.target.selectedOptions[0];
                        const id = Number(event.target.value);

                        event.target.value = '';

                        if (! id || this.added.some((subject) => subject.id === id)) {
                            return;
                        }

                        this.added.push({ id, name: option.dataset.name, score: '' });
                    },

                    remove(id) {
                        this.added = this.added.filter((subject) => subject.id !== id);
                    },
                };
            }
        </script>
    @endpush
</x-app-layout>
