{{-- Formulir gelombang, dipakai bersama oleh halaman tambah dan ubah. --}}
<div class="space-y-6">
    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="name" value="Nama Gelombang" />
                <x-text-input id="name" name="name" type="text" class="mt-1 block w-full"
                              :value="old('name', $period->name)" placeholder="Gelombang 1" required />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="academic_year" value="Tahun Akademik" />
                <x-text-input id="academic_year" name="academic_year" type="text" class="mt-1 block w-full"
                              :value="old('academic_year', $period->academic_year)" placeholder="2026/2027" required />
                <x-input-error :messages="$errors->get('academic_year')" class="mt-2" />
            </div>

            <div></div>

            <div>
                <x-input-label for="starts_at" value="Tanggal Mulai" />
                <x-text-input id="starts_at" name="starts_at" type="date" class="mt-1 block w-full"
                              :value="old('starts_at', $period->starts_at?->format('Y-m-d'))" />
                <x-input-error :messages="$errors->get('starts_at')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="ends_at" value="Tanggal Selesai" />
                <x-text-input id="ends_at" name="ends_at" type="date" class="mt-1 block w-full"
                              :value="old('ends_at', $period->ends_at?->format('Y-m-d'))" />
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Boleh dikosongkan bila belum ditentukan.</p>
                <x-input-error :messages="$errors->get('ends_at')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <x-input-label for="description" value="Keterangan" />
                <textarea id="description" name="description" rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ old('description', $period->description) }}</textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <label class="inline-flex items-start gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $period->is_active))
                           class="mt-0.5 rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900">
                    <span class="text-sm text-gray-700 dark:text-gray-300">
                        Jadikan gelombang aktif
                        <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">
                            Tes baru akan ditandai gelombang ini. Gelombang lain yang sedang aktif otomatis dinonaktifkan
                            &mdash; hanya boleh ada satu gelombang aktif.
                        </span>
                    </span>
                </label>
            </div>
        </div>
    </section>

    <div class="flex items-center justify-end gap-3">
        <a href="{{ route('admin.periods.index') }}"
           class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
            Batal
        </a>
        <button type="submit"
                class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
            Simpan
        </button>
    </div>
</div>
