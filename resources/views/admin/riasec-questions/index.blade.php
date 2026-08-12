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
               class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700">
                Tambah Pernyataan
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($labels as $code => $label)
                    <a href="{{ route('admin.questions.index', ['dimension' => $code]) }}"
                       class="rounded-xl bg-white p-4 shadow-sm transition hover:ring-2 hover:ring-indigo-500 dark:bg-gray-800 {{ request('dimension') === $code ? 'ring-2 ring-indigo-500' : '' }}">
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
                <a href="{{ route('admin.questions.index') }}" class="inline-block text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                    &larr; Tampilkan semua dimensi
                </a>
            @endif

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
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
                                            <a href="{{ route('admin.questions.edit', $question) }}"
                                               class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">Ubah</a>

                                            <form action="{{ route('admin.questions.destroy', $question) }}" method="POST" class="ms-3 inline"
                                                  onsubmit="return confirm('Hapus pernyataan ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="font-medium text-rose-600 hover:underline dark:text-rose-400">Hapus</button>
                                            </form>
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
        </div>
    </div>
</x-app-layout>
