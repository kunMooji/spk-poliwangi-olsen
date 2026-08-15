<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Pernyataan Kuesioner RIASEC</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    Dijawab calon mahasiswa dengan skala Likert 1&ndash;5.
                </p>
            </div>
            <a href="{{ route('admin.questions.create') }}"
               class="inline-flex items-center rounded-lg bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">
                Tambah Pernyataan
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8"
             x-data="{ view: localStorage.getItem('spk-list-view') || 'table' }"
             x-init="$watch('view', v => localStorage.setItem('spk-list-view', v))">
            <x-flash />

            <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($labels as $code => $label)
                    <a href="{{ route('admin.questions.index', ['dimension' => $code]) }}"
                       class="rounded-xl bg-white p-4 shadow-sm transition hover:ring-2 hover:ring-brand-500 dark:bg-gray-800 {{ request('dimension') === $code ? 'ring-2 ring-brand-500' : '' }}">
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $counts[$code] ?? 0 }}
                            <span class="text-xs font-normal text-gray-400">butir aktif</span>
                        </p>
                    </a>
                @endforeach
            </div>

            @php($perDimension = collect(array_keys($labels))->map(fn ($code) => $counts[$code] ?? 0))

            @if ($perDimension->unique()->count() > 1)
                <x-alert type="warning">
                    Jumlah butir aktif antar dimensi belum seimbang. Persentase RIASEC dihitung relatif terhadap
                    jumlah butir per dimensi, namun jumlah yang setara membuat perbandingan antar dimensi lebih adil.
                </x-alert>
            @endif

            @if (request('dimension'))
                <a href="{{ route('admin.questions.index') }}" class="inline-block text-sm font-medium text-brand-600 hover:underline dark:text-brand-400">
                    &larr; Tampilkan semua dimensi
                </a>
            @endif

            @if (! $questions->isEmpty())
                <div class="flex justify-end">
                    <x-list-view-toggle />
                </div>
            @endif

            <div x-show="view === 'table'" class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                @if ($questions->isEmpty())
                    <p class="p-10 text-center text-gray-500 dark:text-gray-400">Belum ada pernyataan pada dimensi ini.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Urutan</th>
                                    <th class="px-6 py-3">Pernyataan</th>
                                    <th class="px-6 py-3">Dimensi</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($questions as $question)
                                    <tr class="text-gray-700 dark:text-gray-300">
                                        <td class="whitespace-nowrap px-6 py-4">{{ $question->sort_order }}</td>
                                        <td class="px-6 py-4 text-gray-900 dark:text-gray-100">{{ $question->statement }}</td>
                                        <td class="whitespace-nowrap px-6 py-4">
                                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold text-white"
                                                  style="background-color: {{ \App\Support\Riasec::color($question->dimension) }}">
                                                {{ $question->dimension }}
                                            </span>
                                            <span class="ms-1 text-xs text-gray-500 dark:text-gray-400">{{ $question->dimension_name }}</span>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($question->is_active)
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                                            @else
                                                <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            <div class="inline-flex items-center gap-1">
                                                <x-icon-button :href="route('admin.questions.edit', $question)" color="brand" title="Ubah">
                                                    <x-icon.pencil />
                                                </x-icon-button>

                                                <form action="{{ route('admin.questions.destroy', $question) }}" method="POST"
                                                      onsubmit="return confirm('Hapus pernyataan ini?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-icon-button type="submit" color="rose" title="Hapus">
                                                        <x-icon.trash />
                                                    </x-icon-button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                        {{ $questions->links() }}
                    </div>
                @endif
            </div>

            @if (! $questions->isEmpty())
                <div x-show="view === 'card'" x-cloak>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($questions as $question)
                            @php($qColor = \App\Support\Riasec::color($question->dimension))
                            <div class="flex flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-800">
                                <div class="relative overflow-hidden p-5 text-white"
                                     style="background-color: {{ $qColor }}; background-image: linear-gradient(135deg, rgba(255,255,255,.20), rgba(0,0,0,.28));">
                                    <span class="pointer-events-none absolute -right-2 -top-8 select-none text-8xl font-black leading-none text-white/10">
                                        {{ $question->dimension }}
                                    </span>

                                    <div class="relative flex items-center justify-between gap-2">
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/20 px-2.5 py-1 text-xs font-semibold backdrop-blur-sm">
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 17.25h.007v.008H12v-.008z" />
                                            </svg>
                                            {{ $question->dimension }} &middot; {{ $question->dimension_name }}
                                        </span>
                                        <span class="whitespace-nowrap rounded-full bg-white/20 px-2 py-1 text-[11px] font-medium backdrop-blur-sm">
                                            Urutan {{ $question->sort_order }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex flex-1 flex-col p-5">
                                    <p class="flex-1 text-sm text-gray-900 dark:text-gray-100">{{ $question->statement }}</p>

                                    <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-700">
                                        @if ($question->is_active)
                                            <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                                        @else
                                            <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">Nonaktif</span>
                                        @endif

                                        <div class="inline-flex items-center gap-1">
                                            <x-icon-button :href="route('admin.questions.edit', $question)" color="brand" title="Ubah">
                                                <x-icon.pencil />
                                            </x-icon-button>

                                            <form action="{{ route('admin.questions.destroy', $question) }}" method="POST"
                                                  onsubmit="return confirm('Hapus pernyataan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <x-icon-button type="submit" color="rose" title="Hapus">
                                                    <x-icon.trash />
                                                </x-icon-button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $questions->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
