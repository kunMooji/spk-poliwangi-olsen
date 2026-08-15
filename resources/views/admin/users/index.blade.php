<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Akun Pengguna</h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                {{ $totalMahasiswa }} akun calon mahasiswa, {{ $totalNonaktif }} di antaranya nonaktif.
            </p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8"
             x-data="{ view: localStorage.getItem('spk-list-view') || 'table' }"
             x-init="$watch('view', v => localStorage.setItem('spk-list-view', v))">
            <x-flash />

            <x-alert type="info">
                Akun administrator hanya ditampilkan di sini dan tidak dapat diubah lewat antarmuka &mdash;
                pengelolaannya dilakukan lewat seeder atau langsung di basis data, supaya tidak ada jalur
                peningkatan hak akses dari halaman web.
            </x-alert>

            <form method="GET" class="grid gap-4 rounded-xl bg-white p-4 shadow-sm dark:bg-gray-800 sm:grid-cols-2 lg:grid-cols-4">
                <div class="lg:col-span-2">
                    <x-input-label for="q" value="Cari" />
                    <x-text-input id="q" name="q" type="search" class="mt-1 block w-full"
                                  :value="request('q')" placeholder="Nama atau surel" />
                </div>

                <div>
                    <x-input-label for="role" value="Peran" />
                    <select id="role" name="role"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        <option value="mahasiswa" @selected(request('role') === 'mahasiswa')>Calon mahasiswa</option>
                        <option value="admin" @selected(request('role') === 'admin')>Administrator</option>
                    </select>
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-brand-500 focus:ring-brand-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        <option value="aktif" @selected(request('status') === 'aktif')>Aktif</option>
                        <option value="nonaktif" @selected(request('status') === 'nonaktif')>Nonaktif</option>
                    </select>
                </div>

                <div class="flex items-end gap-3 sm:col-span-2 lg:col-span-4">
                    <button type="submit"
                            class="rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-700 dark:bg-gray-100 dark:text-gray-900 dark:hover:bg-white">
                        Terapkan Filter
                    </button>
                    @if (request()->hasAny(['q', 'role', 'status']))
                        <a href="{{ route('admin.users.index') }}"
                           class="rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                            Reset
                        </a>
                    @endif
                </div>
            </form>

            @if (! $users->isEmpty())
                <div class="flex justify-end">
                    <x-list-view-toggle />
                </div>
            @endif

            <div x-show="view === 'table'" class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
                @if ($users->isEmpty())
                    <p class="p-10 text-center text-gray-500 dark:text-gray-400">Tidak ada akun yang cocok dengan filter.</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500 dark:bg-gray-900/50 dark:text-gray-400">
                                <tr>
                                    <th class="px-6 py-3">Nama</th>
                                    <th class="px-6 py-3">Peran</th>
                                    <th class="px-6 py-3 text-right">Riwayat Tes</th>
                                    <th class="px-6 py-3">Terdaftar</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3 text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($users as $user)
                                    <tr class="text-gray-700 dark:text-gray-300">
                                        <td class="px-6 py-4">
                                            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $user->name }}</p>
                                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($user->isAdmin())
                                                <span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-medium text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">Administrator</span>
                                            @else
                                                <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">Calon mahasiswa</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right font-semibold tabular-nums">
                                            {{ $user->assessments_count }}
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-gray-500 dark:text-gray-400">
                                            {{ $user->created_at?->translatedFormat('d M Y') ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4">
                                            @if ($user->is_active)
                                                <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                                            @else
                                                <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-6 py-4 text-right">
                                            @if ($user->isAdmin() || $user->id === auth()->id())
                                                <span class="text-xs text-gray-400">Tidak dapat diubah</span>
                                            @else
                                                <div class="inline-flex items-center gap-1">
                                                    <form action="{{ route('admin.users.password', $user) }}" method="POST"
                                                          onsubmit="return confirm('Setel ulang kata sandi {{ $user->name }}? Kata sandi lama akan langsung tidak berlaku.')">
                                                        @csrf
                                                        @method('PUT')
                                                        <x-icon-button type="submit" color="brand" title="Reset Sandi">
                                                            <x-icon.key />
                                                        </x-icon-button>
                                                    </form>

                                                    <form action="{{ route('admin.users.status', $user) }}" method="POST"
                                                          onsubmit="return confirm('{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan kembali' }} akun {{ $user->name }}?')">
                                                        @csrf
                                                        @method('PUT')
                                                        <x-icon-button type="submit" :color="$user->is_active ? 'amber' : 'emerald'"
                                                                       :title="$user->is_active ? 'Nonaktifkan' : 'Aktifkan'">
                                                            <x-icon.power />
                                                        </x-icon-button>
                                                    </form>

                                                    @if ($user->assessments_count === 0)
                                                        <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                                              onsubmit="return confirm('Hapus akun {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.')">
                                                            @csrf
                                                            @method('DELETE')
                                                            <x-icon-button type="submit" color="rose" title="Hapus">
                                                                <x-icon.trash />
                                                            </x-icon-button>
                                                        </form>
                                                    @endif
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>

            @if (! $users->isEmpty())
                <div x-show="view === 'card'" x-cloak>
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($users as $user)
                            @php($roleColor = $user->isAdmin() ? '#7c3aed' : '#0284c7')
                            <div class="flex flex-col overflow-hidden rounded-xl bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-800">
                                <div class="relative overflow-hidden p-5 text-white"
                                     style="background-color: {{ $roleColor }}; background-image: linear-gradient(135deg, rgba(255,255,255,.20), rgba(0,0,0,.28));">
                                    <svg class="pointer-events-none absolute -right-4 -top-4 h-24 w-24 text-white/10" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" aria-hidden="true">
                                        @if ($user->isAdmin())
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                        @else
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" />
                                        @endif
                                    </svg>

                                    <div class="relative flex items-start gap-2.5">
                                        <span class="mt-0.5 flex h-9 w-9 flex-none items-center justify-center rounded-full bg-white/20 text-sm font-semibold backdrop-blur-sm">
                                            {{ \Illuminate\Support\Str::of($user->name)->substr(0, 1)->upper() }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate text-base font-semibold">{{ $user->name }}</p>
                                            <p class="mt-0.5 flex items-center gap-1 truncate text-xs text-white/80">
                                                <svg class="h-3.5 w-3.5 flex-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                                                </svg>
                                                {{ $user->email }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <dl class="grid grid-cols-2 gap-x-3 gap-y-2 p-5 pb-3 text-sm">
                                    <div>
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Riwayat Tes</dt>
                                        <dd class="font-semibold tabular-nums text-gray-900 dark:text-gray-100">{{ $user->assessments_count }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-xs text-gray-500 dark:text-gray-400">Terdaftar</dt>
                                        <dd class="text-gray-700 dark:text-gray-300">{{ $user->created_at?->translatedFormat('d M Y') ?? '-' }}</dd>
                                    </div>
                                </dl>

                                <div class="flex flex-wrap items-center gap-2 px-5 pb-3">
                                    @if ($user->isAdmin())
                                        <span class="rounded-full bg-violet-100 px-2.5 py-1 text-xs font-medium text-violet-700 dark:bg-violet-900/40 dark:text-violet-300">Administrator</span>
                                    @else
                                        <span class="rounded-full bg-sky-100 px-2.5 py-1 text-xs font-medium text-sky-700 dark:bg-sky-900/40 dark:text-sky-300">Calon mahasiswa</span>
                                    @endif

                                    @if ($user->is_active)
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Aktif</span>
                                    @else
                                        <span class="rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">Nonaktif</span>
                                    @endif
                                </div>

                                <div class="flex flex-wrap gap-x-3 gap-y-1 border-t border-gray-100 px-5 py-3 text-sm dark:border-gray-700">
                                    @if ($user->isAdmin() || $user->id === auth()->id())
                                        <span class="text-xs text-gray-400">Tidak dapat diubah</span>
                                    @else
                                        <div class="inline-flex items-center gap-1">
                                            <form action="{{ route('admin.users.password', $user) }}" method="POST"
                                                  onsubmit="return confirm('Setel ulang kata sandi {{ $user->name }}? Kata sandi lama akan langsung tidak berlaku.')">
                                                @csrf
                                                @method('PUT')
                                                <x-icon-button type="submit" color="brand" title="Reset Sandi">
                                                    <x-icon.key />
                                                </x-icon-button>
                                            </form>

                                            <form action="{{ route('admin.users.status', $user) }}" method="POST"
                                                  onsubmit="return confirm('{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan kembali' }} akun {{ $user->name }}?')">
                                                @csrf
                                                @method('PUT')
                                                <x-icon-button type="submit" :color="$user->is_active ? 'amber' : 'emerald'"
                                                               :title="$user->is_active ? 'Nonaktifkan' : 'Aktifkan'">
                                                    <x-icon.power />
                                                </x-icon-button>
                                            </form>

                                            @if ($user->assessments_count === 0)
                                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                                      onsubmit="return confirm('Hapus akun {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <x-icon-button type="submit" color="rose" title="Hapus">
                                                        <x-icon.trash />
                                                    </x-icon-button>
                                                </form>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
