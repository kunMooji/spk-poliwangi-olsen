<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Langkah 1 dari 2 &mdash; Biodata, Nilai Rapor &amp; Prioritas Prodi
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="POST" action="{{ route('assessments.store') }}" class="space-y-6">
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

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="school_major" value="Jurusan" />
                                <select id="school_major" name="school_major"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    <option value="">— Pilih —</option>
                                    @foreach (['IPA', 'IPS', 'Bahasa', 'SMK', 'Lainnya'] as $major)
                                        <option value="{{ $major }}" @selected(old('school_major') === $major)>{{ $major }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('school_major')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="graduation_year" value="Tahun Lulus" />
                                <x-text-input id="graduation_year" name="graduation_year" type="number" class="mt-1 block w-full"
                                              :value="old('graduation_year', date('Y'))" min="2000" max="{{ date('Y') + 1 }}" />
                                <x-input-error :messages="$errors->get('graduation_year')" class="mt-2" />
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Nilai rapor --}}
                <section class="rounded-xl bg-white p-6 shadow-sm dark:bg-gray-800">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Nilai Rapor</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Masukkan nilai rata-rata rapor pada skala 0&ndash;100. Setiap nilai akan dikalikan bobot
                        relevansi mata pelajaran pada masing-masing program studi.
                    </p>

                    <div class="mt-5 grid gap-5 sm:grid-cols-3">
                        @foreach ($subjects as $key => $label)
                            <div>
                                <x-input-label :for="$key.'_score'" :value="$label" />
                                <x-text-input :id="$key.'_score'" :name="$key.'_score'" type="number" step="0.01" min="0" max="100"
                                              class="mt-1 block w-full" :value="old($key.'_score')" required />
                                <x-input-error :messages="$errors->get($key.'_score')" class="mt-2" />
                            </div>
                        @endforeach
                    </div>
                </section>

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
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                        @if ($i < $minPriorities) required @endif>
                                    <option value="">— Pilih program studi —</option>
                                    @foreach ($programs as $program)
                                        <option value="{{ $program->id }}" @selected(old('priorities.'.$i) == $program->id)>
                                            {{ $program->full_name }} ({{ $program->department }})
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('priorities.'.$i)" class="mt-2" />
                            </div>
                        @endfor
                    </div>
                </section>

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
</x-app-layout>
