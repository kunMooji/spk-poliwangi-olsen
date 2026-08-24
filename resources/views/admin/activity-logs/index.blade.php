<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Catatan Perubahan</h2>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-none space-y-4 px-5 sm:px-8 lg:px-10 xl:px-12"
             x-data="{ view: localStorage.getItem('spk-list-view') || 'table' }"
             x-init="$watch('view', v => localStorage.setItem('spk-list-view', v))">
            <x-flash />

            <form method="GET" class="grid gap-4 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800 sm:grid-cols-2 lg:grid-cols-5">
                <div>
                    <x-input-label for="subject" value="Jenis Data" />
                    <select id="subject" name="subject"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        @foreach ($subjects as $class => $label)
                            <option value="{{ $class }}" @selected(request('subject') === $class)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="action" value="Tindakan" />
                    <select id="action" name="action"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        @foreach ($actions as $value => $label)
                            <option value="{{ $value }}" @selected(request('action') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-input-label for="user" value="Pelaku" />
                    <select id="user" name="user"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
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

            @if (! $logs->isEmpty())
                <div class="flex justify-end">
                    <x-list-view-toggle />
                </div>
            @endif

            <div x-show="view === 'table'" class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
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

            @if (! $logs->isEmpty())
                <div x-show="view === 'card'" x-cloak>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($logs as $log)
                            @php
                                $actionColor = match ($log->action) {
                                    'created' => '#059669',
                                    'updated' => '#0284c7',
                                    'deleted' => '#e11d48',
                                    default => '#6b7280',
                                };
                            @endphp
                            <div class="flex flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-800">
                                <div class="relative overflow-hidden p-5 text-white"
                                     style="background-color: {{ $actionColor }}; background-image: linear-gradient(135deg, rgba(255,255,255,.20), rgba(0,0,0,.28));">
                                    <svg class="pointer-events-none absolute -right-4 -top-4 h-24 w-24 text-white/10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                        @if ($log->action === 'created')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        @elseif ($log->action === 'updated')
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                        @endif
                                    </svg>

                                    <div class="relative flex items-start justify-between gap-2">
                                        <span class="flex h-9 w-9 flex-none items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                                            <svg class="h-4.5 w-4.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                @if ($log->action === 'created')
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                                @elseif ($log->action === 'updated')
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487z" />
                                                @else
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                                @endif
                                            </svg>
                                        </span>
                                        <span class="whitespace-nowrap rounded-full bg-white/20 px-2.5 py-1 text-xs font-semibold backdrop-blur-sm">
                                            {{ $log->action_label }}
                                        </span>
                                    </div>

                                    <div class="relative mt-3">
                                        <p class="text-xs font-medium uppercase tracking-wide text-white/70">{{ $log->subject_label_name }}</p>
                                        <p class="mt-0.5 truncate text-base font-semibold">{{ $log->subject_label }}</p>
                                    </div>
                                </div>

                                <div class="flex flex-1 flex-col gap-2 p-5">
                                    <p class="flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                        <svg class="h-3.5 w-3.5 flex-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        </svg>
                                        oleh <span class="font-medium text-gray-700 dark:text-gray-300">{{ $log->user?->name ?? $log->user_name ?? 'Tidak diketahui' }}</span>
                                    </p>
                                    <p class="flex items-center gap-1.5 text-xs text-gray-400 dark:text-gray-500">
                                        <svg class="h-3.5 w-3.5 flex-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ $log->created_at->translatedFormat('d M Y, H:i') }}
                                    </p>

                                    @if ($log->changes)
                                        <ul class="mt-1 space-y-1 border-t border-gray-100 pt-3 text-xs dark:border-gray-700">
                                            @foreach (array_slice($log->changes, 0, 4, true) as $field => $diff)
                                                <li class="flex flex-wrap items-center gap-1">
                                                    <span class="font-mono text-gray-500 dark:text-gray-400">{{ $field }}:</span>
                                                    <span class="rounded bg-rose-50 px-1.5 py-0.5 text-rose-700 dark:bg-rose-900/30 dark:text-rose-300">
                                                        {{ \Illuminate\Support\Str::limit((string) ($diff['from'] ?? '—'), 24) }}
                                                    </span>
                                                    <span class="text-gray-400">&rarr;</span>
                                                    <span class="rounded bg-emerald-50 px-1.5 py-0.5 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                                        {{ \Illuminate\Support\Str::limit((string) ($diff['to'] ?? '—'), 24) }}
                                                    </span>
                                                </li>
                                            @endforeach
                                            @if (count($log->changes) > 4)
                                                <li class="text-gray-400">+{{ count($log->changes) - 4 }} kolom lainnya</li>
                                            @endif
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $logs->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
