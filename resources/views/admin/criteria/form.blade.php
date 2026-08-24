{{--
    Formulir kriteria, dipakai bersama oleh halaman tambah dan ubah, serta oleh
    setiap dialog tambah/ubah pada halaman indeks. Karena beberapa dialog bisa
    dirender sekaligus (satu per kriteria) dengan input old() yang sama-sama
    global, `$dialogKey` menandai dialog mana yang sedang direstore setelah
    validasi gagal — dialog lain tetap memakai nilai model aslinya supaya
    tidak ikut "tercemar" input dialog yang gagal.
--}}
@php
    $dialogKey ??= $criterion->exists ? 'edit' : 'create';
    $useOld = old('_dialog') === $dialogKey;
    $val = fn (string $key, mixed $default = null) => $useOld ? old($key, $default) : $default;
@endphp
<div class="space-y-6">
    <input type="hidden" name="_dialog" value="{{ $dialogKey }}">

    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <x-input-label for="code" value="Kode Kriteria" />
                <x-text-input id="code" name="code" type="text" class="mt-1 block w-full"
                              :value="$val('code', $criterion->code)" placeholder="C10" required />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="sort_order" value="Urutan Tampil" />
                <x-text-input id="sort_order" name="sort_order" type="number" min="0" max="255" class="mt-1 block w-full"
                              :value="$val('sort_order', $criterion->sort_order ?? 0)" required />
                <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="name" value="Nama Kriteria" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                              :value="$val('name', $criterion->name)" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="source" value="Sumber Nilai" />
                <select id="source" name="source"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    @foreach ($sources as $value => $label)
                        <option value="{{ $value }}" @selected($val('source', $criterion->source) === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Menentukan dari mana nilai x<sub>ij</sub> diambil saat menyusun matriks keputusan.
                </p>
                <x-input-error :messages="$errors->get('source')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="weight" value="Bobot (0 – 1)" />
                <x-text-input id="weight" name="weight" type="number" step="0.01" min="0" max="1" class="mt-1 block w-full"
                              :value="$val('weight', $criterion->weight ?? 0)" required />
                <x-input-error :messages="$errors->get('weight')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="type" value="Jenis Kriteria" />
                <select id="type" name="type"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    <option value="benefit" @selected($val('type', $criterion->type) === 'benefit')>Benefit (semakin besar semakin baik)</option>
                    <option value="cost" @selected($val('type', $criterion->type) === 'cost')>Cost (semakin kecil semakin baik)</option>
                </select>
                <x-input-error :messages="$errors->get('type')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="unit" value="Satuan" />
                <x-text-input id="unit" name="unit" type="text" class="mt-1 block w-full"
                              :value="$val('unit', $criterion->unit)" placeholder="poin / persen" />
                <x-input-error :messages="$errors->get('unit')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="description" value="Keterangan" />
                <textarea id="description" name="description" rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $val('description', $criterion->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked($val('is_active', $criterion->is_active))
                           class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Aktif &mdash; ikut diperhitungkan pada tes baru</span>
                </label>
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
            <a href="{{ route('admin.criteria.index') }}"
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
