{{--
    Formulir prodi, dipakai bersama oleh halaman tambah dan ubah, serta oleh
    setiap dialog tambah/ubah pada halaman indeks. Karena beberapa dialog bisa
    dirender sekaligus (satu per prodi) dengan input old() yang sama-sama
    global, `$dialogKey` menandai dialog mana yang sedang direstore setelah
    validasi gagal — dialog lain tetap memakai nilai model aslinya supaya
    tidak ikut "tercemar" input dialog yang gagal.
--}}
@php
    $dialogKey ??= $program->exists ? 'edit' : 'create';
    $useOld = old('_dialog') === $dialogKey;
    $val = fn (string $key, mixed $default = null) => $useOld ? old($key, $default) : $default;
@endphp
<div class="space-y-6">
    <input type="hidden" name="_dialog" value="{{ $dialogKey }}">

    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Identitas Program Studi</h3>

        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="code" value="Kode Prodi" />
                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full"
                              :value="$val('code', $program->code)" required />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="level" value="Jenjang" />
                <select id="level" name="level"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    @foreach (['D2', 'D3', 'D4', 'S1'] as $level)
                        <option value="{{ $level }}" @selected($val('level', $program->level) === $level)>{{ $level }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('level')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="name" value="Nama Prodi" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                              :value="$val('name', $program->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="department" value="Jurusan" />
                <x-text-input id="department" name="department" type="text" class="mt-1 block w-full"
                              :value="$val('department', $program->department)" />
                <x-input-error :messages="$errors->get('department')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="description" value="Deskripsi" />
                <textarea id="description" name="description" rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $val('description', $program->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked($val('is_active', $program->is_active))
                           class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Aktif &mdash; ikut diperhitungkan sebagai alternatif pada tes baru</span>
                </label>
            </div>
        </div>
    </section>

    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Mata Pelajaran Pendukung (C2)</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Nilai calon mahasiswa pada mata pelajaran ini dirata-ratakan menjadi C2 &mdash; satu-satunya kriteria
            nilai rapor yang membedakan antar prodi. Boleh dikosongkan; bila kosong, C2 memakai rerata rapor umum.
        </p>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Batas SNBP <strong>{{ $maxSupportSubjects }} mata pelajaran berlaku per asal sekolah pendaftar</strong>,
            bukan per prodi. Prodi ini boleh memakai mapel SMA sekaligus mapel SMK dari beberapa rumpun keahlian,
            asalkan untuk tiap jenjang dan jurusan yang cocok tidak lebih dari {{ $maxSupportSubjects }}. Mapel
            berjenjang &ldquo;Umum&rdquo; ikut terhitung pada semua asal sekolah.
        </p>

        @php
            $initialSupport = array_values(array_filter(
                (array) $val('support_subjects', $selectedSubjects),
                fn ($id) => $id !== null && $id !== '',
            ));

            // Prodi baru dibuka dengan slot kosong sebanyak batas SNBP, sekadar
            // titik mulai yang wajar — admin bebas menambah untuk jenjang lain.
            $initialSupport = $initialSupport === []
                ? array_fill(0, $maxSupportSubjects, '')
                : $initialSupport;

            $subjectGroups = $subjects->groupBy(fn ($subject) => trim(
                ($subject->education_level === 'umum' ? 'Umum' : $subject->education_level)
                .($subject->group && $subject->education_level !== 'umum' ? ' · '.$subject->group : '')
            ));
        @endphp

        <div class="mt-5 space-y-3" x-data="{ rows: @js($initialSupport) }">
            <template x-for="(row, index) in rows" :key="index">
                <div class="flex items-start gap-3">
                    <select name="support_subjects[]" x-model="rows[index]"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">— Tidak dipakai —</option>
                        @foreach ($subjectGroups as $label => $groupSubjects)
                            <optgroup label="{{ $label }}">
                                @foreach ($groupSubjects as $subject)
                                    <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>

                    <button type="button" x-on:click="rows.splice(index, 1)"
                            class="mt-1 shrink-0 rounded-md px-3 py-2 text-sm text-gray-500 hover:bg-gray-100 hover:text-red-600 dark:text-gray-400 dark:hover:bg-gray-700">
                        Hapus
                    </button>
                </div>
            </template>

            <button type="button" x-on:click="rows.push('')"
                    class="rounded-md border border-dashed border-gray-300 px-4 py-2 text-sm font-medium text-gray-600 hover:border-brand-400 hover:text-brand-600 dark:border-gray-600 dark:text-gray-300">
                + Tambah mata pelajaran pendukung
            </button>
        </div>

        <x-input-error :messages="$errors->get('support_subjects')" class="mt-2" />
    </section>

    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Profil Kepribadian RIASEC Prodi (C3)</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Rentang 0&ndash;100 per dimensi. Vektor ini dibandingkan dengan profil calon mahasiswa
            menggunakan cosine similarity.
        </p>

        <div class="mt-5 grid gap-5 sm:grid-cols-3">
            @foreach ($dimensions as $code => $label)
                @php($field = 'riasec_'.strtolower($code))
                <div>
                    <x-input-label :for="$field" :value="$label" />
                    <x-text-input :id="$field" :name="$field" type="number" min="0" max="100"
                                  class="mt-1 block w-full" :value="$val($field, $program->{$field})" required />
                    <x-input-error :messages="$errors->get($field)" class="mt-2" />
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Tracer Study (C5)</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Persentase serapan kerja dihitung otomatis dari jumlah alumni dan alumni yang terserap.
        </p>

        <div class="mt-5 grid gap-5 sm:grid-cols-3">
            <div>
                <x-input-label for="alumni_count" value="Jumlah Alumni" />
                <x-text-input id="alumni_count" name="alumni_count" type="number" min="0" class="mt-1 block w-full"
                              :value="$val('alumni_count', $program->alumni_count ?? 0)" required />
                <x-input-error :messages="$errors->get('alumni_count')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="employed_count" value="Alumni Terserap Kerja" />
                <x-text-input id="employed_count" name="employed_count" type="number" min="0" class="mt-1 block w-full"
                              :value="$val('employed_count', $program->employed_count ?? 0)" required />
                <x-input-error :messages="$errors->get('employed_count')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="tracer_year" value="Tahun Tracer Study" />
                <x-text-input id="tracer_year" name="tracer_year" type="number" min="2000" max="{{ date('Y') }}"
                              class="mt-1 block w-full" :value="$val('tracer_year', $program->tracer_year)" />
                <x-input-error :messages="$errors->get('tracer_year')" class="mt-2" />
            </div>
        </div>
    </section>

    <div class="flex items-center justify-end gap-3">
        @if ($isModal ?? false)
            <button type="button" @click="dialog = null"
                    class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                Batal
            </button>
        @else
            <a href="{{ route('admin.study-programs.index') }}"
               class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                Batal
            </a>
        @endif
        <button type="submit"
                class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
            Simpan
        </button>
    </div>
</div>
