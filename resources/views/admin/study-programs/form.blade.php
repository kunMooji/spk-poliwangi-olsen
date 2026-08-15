{{-- Formulir prodi, dipakai bersama oleh halaman tambah dan ubah. --}}
<div class="space-y-6">
    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Identitas Program Studi</h3>

        <div class="mt-5 grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="code" value="Kode Prodi" />
                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full"
                              :value="old('code', $program->code)" required />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="level" value="Jenjang" />
                <select id="level" name="level"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    @foreach (['D2', 'D3', 'D4', 'S1'] as $level)
                        <option value="{{ $level }}" @selected(old('level', $program->level) === $level)>{{ $level }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('level')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="name" value="Nama Prodi" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                              :value="old('name', $program->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="department" value="Jurusan" />
                <x-text-input id="department" name="department" type="text" class="mt-1 block w-full"
                              :value="old('department', $program->department)" />
                <x-input-error :messages="$errors->get('department')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="description" value="Deskripsi" />
                <textarea id="description" name="description" rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('description', $program->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $program->is_active))
                           class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Aktif &mdash; ikut diperhitungkan sebagai alternatif pada tes baru</span>
                </label>
            </div>
        </div>
    </section>

    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Bobot Relevansi Mata Pelajaran (C1&ndash;C6)</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Rentang 0&ndash;1. Nilai rapor calon mahasiswa dikalikan bobot ini, sehingga kolom nilai rapor
            tidak lagi konstan antar prodi dan normalisasi CoCoSo dapat dihitung.
        </p>

        <div class="mt-5 grid gap-5 sm:grid-cols-3">
            @foreach ($subjects as $key => $label)
                <div>
                    <x-input-label :for="$key.'_relevance'" :value="$label" />
                    <x-text-input :id="$key.'_relevance'" :name="$key.'_relevance'" type="number" step="0.05" min="0" max="1"
                                  class="mt-1 block w-full" :value="old($key.'_relevance', $program->{$key.'_relevance'})" required />
                    <x-input-error :messages="$errors->get($key.'_relevance')" class="mt-2" />
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Profil Kepribadian RIASEC Prodi (C7)</h3>
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
                                  class="mt-1 block w-full" :value="old($field, $program->{$field})" required />
                    <x-input-error :messages="$errors->get($field)" class="mt-2" />
                </div>
            @endforeach
        </div>
    </section>

    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Tracer Study (C9)</h3>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Persentase serapan kerja dihitung otomatis dari jumlah alumni dan alumni yang terserap.
        </p>

        <div class="mt-5 grid gap-5 sm:grid-cols-3">
            <div>
                <x-input-label for="alumni_count" value="Jumlah Alumni" />
                <x-text-input id="alumni_count" name="alumni_count" type="number" min="0" class="mt-1 block w-full"
                              :value="old('alumni_count', $program->alumni_count ?? 0)" required />
                <x-input-error :messages="$errors->get('alumni_count')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="employed_count" value="Alumni Terserap Kerja" />
                <x-text-input id="employed_count" name="employed_count" type="number" min="0" class="mt-1 block w-full"
                              :value="old('employed_count', $program->employed_count ?? 0)" required />
                <x-input-error :messages="$errors->get('employed_count')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="tracer_year" value="Tahun Tracer Study" />
                <x-text-input id="tracer_year" name="tracer_year" type="number" min="2000" max="{{ date('Y') }}"
                              class="mt-1 block w-full" :value="old('tracer_year', $program->tracer_year)" />
                <x-input-error :messages="$errors->get('tracer_year')" class="mt-2" />
            </div>
        </div>
    </section>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.study-programs.index') }}"
           class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
            Batal
        </a>
        <button type="submit"
                class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
            Simpan
        </button>
    </div>
</div>
