<x-app-layout>
    <x-slot name="header">
        <div class="space-y-2">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                Biodata, Nilai Rapor &amp; Prioritas Prodi
            </h2>
            <x-assessment-steps :current="1" />
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            {{--
                Seluruh formulir berbagi satu lingkup Alpine: jenjang dan jurusan
                yang dipilih pada biodata menentukan mapel mana yang ditanyakan di
                bagian nilai pendukung, sementara prodi yang dipilih menentukan
                mapel mana yang disorot.
            --}}
            <form method="POST" action="{{ route('assessments.store') }}" class="space-y-6"
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
                  })">
                @csrf

                {{-- Biodata --}}
                <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Biodata</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Data ini muncul pada lembar hasil tes Anda.</p>

                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <x-input-label for="full_name" value="Nama Lengkap" />
                            <x-text-input id="full_name" name="full_name" type="text" class="mt-1 block w-full"
                                          :value="old('full_name', auth()->user()->name)" required autofocus />
                            <x-input-error :messages="$errors->get('full_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="gender" value="Jenis Kelamin" />
                            <select id="gender" name="gender"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="">— Pilih —</option>
                                <option value="L" @selected(old('gender') === 'L')>Laki-laki</option>
                                <option value="P" @selected(old('gender') === 'P')>Perempuan</option>
                            </select>
                            <x-input-error :messages="$errors->get('gender')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="phone" value="Nomor HP" />
                            <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full"
                                          :value="old('phone')" placeholder="08xxxxxxxxxx" />
                            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="school_name" value="Asal Sekolah" />
                            <x-text-input id="school_name" name="school_name" type="text" class="mt-1 block w-full"
                                          :value="old('school_name')" placeholder="SMA Negeri 1 Banyuwangi" />
                            <x-input-error :messages="$errors->get('school_name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="graduation_year" value="Tahun Lulus" />
                            <x-text-input id="graduation_year" name="graduation_year" type="number" class="mt-1 block w-full"
                                          :value="old('graduation_year', date('Y'))" min="2000" max="{{ date('Y') + 1 }}" />
                            <x-input-error :messages="$errors->get('graduation_year')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="education_level" value="Jenjang Sekolah" />
                            {{-- Jurusan dikosongkan saat jenjang berganti: pilihan SMA tidak berlaku di SMK. --}}
                            <select id="education_level" name="education_level" x-model="level" required
                                    x-init="$watch('level', () => { major = '' })"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                <option value="">— Pilih —</option>
                                @foreach ($educationLevels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('education_level')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="school_major"
                                           x-text="level === 'SMK' ? 'Rumpun Keahlian' : 'Jurusan'">Jurusan</x-input-label>
                            <select id="school_major" name="school_major" x-model="major" :disabled="! level"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 disabled:bg-gray-100 disabled:text-gray-400 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 dark:disabled:bg-gray-800">
                                <option value="" x-text="level ? '— Pilih —' : '— Pilih jenjang dulu —'">— Pilih jenjang dulu —</option>
                                <template x-for="option in availableMajors" :key="option">
                                    <option :value="option" x-text="option"></option>
                                </template>
                            </select>
                            <p class="mt-1 text-xs text-gray-400" x-show="level === 'SMK'" x-cloak>
                                Pilih rumpun sesuai konsentrasi keahlian yang tercantum di rapor Anda.
                            </p>
                            <x-input-error :messages="$errors->get('school_major')" class="mt-2" />
                        </div>
                    </div>
                </section>

                {{-- Komponen pertama SNBP: rerata rapor seluruh mapel per semester --}}
                <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Rerata Rapor per Semester</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Masukkan nilai rata-rata <strong>seluruh mata pelajaran</strong> pada setiap semester, skala
                        0&ndash;100. Semester terakhir memang tidak diminta &mdash; SNBP memeringkat berdasarkan semua
                        semester kecuali yang terakhir.
                    </p>

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

                <div class="space-y-6">

                    {{-- Prioritas prodi --}}
                    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Urutan Prioritas Program Studi</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Pilih minimal {{ $minPriorities }} program studi sesuai urutan minat Anda. Prioritas pertama
                            memperoleh skor minat tertinggi. Program studi tidak boleh dipilih dua kali.
                        </p>

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
                    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Nilai Mata Pelajaran Pendukung</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Daftar ini menyesuaikan jenjang dan jurusan yang Anda pilih di biodata, sehingga Anda tidak
                            diminta mengisi mata pelajaran yang tidak pernah Anda tempuh. Yang ditanyakan hanya mata
                            pelajaran pendukung dari <strong>program studi yang Anda jadikan prioritas</strong> di atas
                            &mdash; program studi lain tidak memerlukan isian di sini.
                            <strong>Kosongkan</strong> mata pelajaran yang tidak ada di rapor Anda &mdash; nilainya akan
                            digantikan rerata rapor Anda, bukan dianggap nol.
                        </p>

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

                <div class="flex items-center justify-end gap-3">
                    <a href="{{ route('dashboard') }}"
                       class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                        Batal
                    </a>
                    <button type="submit"
                            class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                        Lanjut ke Kuesioner
                    </button>
                </div>
            </form>
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
