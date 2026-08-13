<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Catatan Perubahan</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $total }} perubahan data master tercatat. Bobot kriteria dan parameter algoritma memengaruhi
                rekomendasi seluruh tes sesudahnya, sehingga perubahannya perlu dapat ditelusuri.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="GET" class="grid gap-4 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <x-input-label for="subject" value="Jenis Data" />
                    <select id="subject" name="subject"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        @foreach ($subjects as $class => $label)
                            <option value="{{ $class }}" @selected(request('subject') === $class)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="action" value="Tindakan" />
                    <select id="action" name="action"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        @foreach ($actions as $value => $label)
                            <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="user" value="Pelaku" />
                    <select id="user" name="user"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        @foreach ($admins as $admin)
                            <option value="{{ $admin->id }}" @selected(request('user') == $admin->id)>{{ $admin->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="from" value="Dari Tanggal" />
                    <x-text-input id="from" name="from" type="date" class="mt-1 block w-full" :value="request('from')" />
                </div>

                <div>
                    <x-input-label for="to" value="Sampai Tanggal" />
                    <x-text-input id="to" name="to" type="date" class="mt-1 block w-full" :value="request('to')" />
                </div>

                <div class="flex items-end gap-3 sm:col-span-2 lg:col-span-5">
                    <button type="submit"
                            class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                        Terapkan Filter
                    </button>
                    @if (request()->hasAny(['subject', 'action', 'user', 'from', 'to']))
                        <a href="{{ route('admin.activity-logs.index') }}"
                           class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                @if ($logs->isEmpty())
                    <p class="p-10 text-center text-gray-500 dark:text-gray-400">
                        Belum ada perubahan yang tercatat dengan filter ini.
                    </p>
                @else
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach ($logs as $log)
                            <li class="p-5">
                                <div class="flex flex-wrap items-baseline justify-between gap-2">
                                    <div class="text-sm">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-medium
                                            @class([
                                                'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' => $log->action === 'created',
                                                'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300' => $log->action === 'updated',
                                                'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300' => $log->action === 'deleted',
                                            ])">
                                            {{ $log->action_label }}
                                        </span>
                                        <span class="ms-2 text-gray-500 dark:text-gray-400">{{ $log->subject_label_name }}</span>
                                        <span class="ms-1 font-medium text-gray-900 dark:text-gray-100">{{ $log->subject_label }}</span>
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        oleh <span class="font-medium text-gray-700 dark:text-gray-300">{{ $log->user?->name ?? $log->user_name ?? 'Tidak diketahui' }}</span>
                                        &middot; {{ $log->created_at->translatedFormat('d M Y, H:i') }}
                                    </div>
                                </div>

                                @if ($log->changes)
                                    <div class="mt-3 overflow-x-auto">
                                        <table class="min-w-full text-xs">
                                            <thead class="text-left uppercase tracking-wide text-gray-400">
                                                <tr>
                                                    <th class="py-1 pe-4">Kolom</th>
                                                    <th class="py-1 pe-4">Sebelum</th>
                                                    <th class="py-1">Sesudah</th>
                                                </tr>
                                            </thead>
                                            <tbody class="text-gray-600 dark:text-gray-300">
                                                @foreach ($log->changes as $field => $diff)
                                                    <tr>
                                                        <td class="py-1 pe-4 font-mono">{{ $field }}</td>
                                                        <td class="py-1 pe-4">
                                                            <span class="rounded bg-rose-50 px-1.5 py-0.5 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
                                                                {{ \Illuminate\Support\Str::limit((string) ($diff['from'] ?? '—'), 60) }}
                                                            </span>
                                                        </td>
                                                        <td class="py-1">
                                                            <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                                {{ \Illuminate\Support\Str::limit((string) ($diff['to'] ?? '—'), 60) }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>

                    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
