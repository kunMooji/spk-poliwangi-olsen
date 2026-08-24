{{-- Formulir butir kuesioner, dipakai bersama oleh halaman tambah dan ubah. --}}
@php
    $dialogKey ??= $question->exists ? 'edit-'.$question->id : 'create';
    $useOld = old('_dialog') === $dialogKey;
    $val = fn (string $key, mixed $default = null) => $useOld ? old($key, $default) : $default;
@endphp
<div class="space-y-6">
    <input type="hidden" name="_dialog" value="{{ $dialogKey }}">
    <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <x-input-label for="statement" value="Pernyataan" />
                <textarea id="statement" name="statement" rows="3" required
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $val('statement', $question->statement) }}</textarea>
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Tulis sebagai kalimat pengalaman diri, misalnya &ldquo;Saya senang membongkar dan memperbaiki peralatan elektronik.&rdquo;
                </p>
                <x-input-error :messages="$errors->get('statement')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="dimension" value="Dimensi RIASEC" />
                <select id="dimension" name="dimension"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                    @foreach ($labels as $code => $label)
                        <option value="{{ $code }}" @selected($val('dimension', $question->dimension) === $code)>{{ $code }} &mdash; {{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('dimension')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="sort_order" value="Urutan Tampil" />
                <x-text-input id="sort_order" name="sort_order" type="number" min="0" max="255" class="mt-1 block w-full"
                              :value="$val('sort_order', $question->sort_order ?? 0)" required />
                <x-input-error :messages="$errors->get('sort_order')" class="mt-2" />
            </div>

            <div class="sm:col-span-2">
                <label class="inline-flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked($val('is_active', $question->is_active))
                           class="rounded border-gray-300 text-brand-600 shadow-sm focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900">
                    <span class="text-sm text-gray-700 dark:text-gray-300">Aktif &mdash; muncul pada kuesioner tes baru</span>
                </label>
            </div>
        </div>
    </section>

    <div class="flex items-center justify-end gap-3">
        @if ($isModal ?? false)
            <button type="button" @click="dialog = null" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Batal</button>
        @else
            <a href="{{ route('admin.questions.index') }}" class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">Batal</a>
        @endif
        <button type="submit"
                class="rounded-lg bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
            Simpan
        </button>
    </div>
</div>
