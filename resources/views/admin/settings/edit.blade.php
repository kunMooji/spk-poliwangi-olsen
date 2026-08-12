<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Pengaturan Algoritma</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Parameter perhitungan RIASEC dan CoCoSo. Berlaku untuk tes berikutnya.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                @csrf
                @method('PUT')

                <section class="divide-y divide-gray-200 rounded-xl bg-white shadow-sm dark:divide-gray-700 dark:bg-gray-800">
                    @foreach ($settings as $setting)
                        @php($field = 'settings.'.$setting->key)
                        <div class="grid gap-4 p-6 sm:grid-cols-3">
                            <div class="sm:col-span-2">
                                <x-input-label :for="$setting->key" :value="$setting->label" />
                                <p class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-gray-400">{{ $setting->description }}</p>
                                <p class="mt-1 font-mono text-xs text-gray-400">{{ $setting->key }}</p>
                            </div>

                            <div>
                                @if (isset($choices[$setting->key]))
                                    <select id="{{ $setting->key }}" name="settings[{{ $setting->key }}]"
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                        @foreach ($choices[$setting->key] as $value => $label)
                                            <option value="{{ $value }}" @selected(old($field, $values[$setting->key]) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <x-text-input :id="$setting->key" :name="'settings['.$setting->key.']'"
                                                  type="number"
                                                  step="{{ $setting->type === 'integer' ? '1' : 'any' }}"
                                                  class="block w-full"
                                                  :value="old($field, $values[$setting->key])" required />
                                @endif
                                <x-input-error :messages="$errors->get($field)" class="mt-2" />
                            </div>
                        </div>
                    @endforeach
                </section>

                <div class="flex items-center justify-end">
                    <button type="submit"
                            class="rounded-lg bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                        Simpan Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
