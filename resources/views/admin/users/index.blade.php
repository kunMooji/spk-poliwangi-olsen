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
        <div class="mx-auto max-w-7xl space-y-4 px-4 sm:px-6 lg:px-8">
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
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                        <option value="">Semua</option>
                        <option value="mahasiswa" @selected(request('role') === 'mahasiswa')>Calon mahasiswa</option>
                        <option value="admin" @selected(request('role') === 'admin')>Administrator</option>
                    </select>
                </div>

                <div>
                    <x-input-label for="status" value="Status" />
                    <select id="status" name="status"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
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

            <div class="overflow-hidden rounded-xl bg-white shadow-sm dark:bg-gray-800">
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
                                                <form action="{{ route('admin.users.password', $user) }}" method="POST" class="inline"
                                                      onsubmit="return confirm('Setel ulang kata sandi {{ $user->name }}? Kata sandi lama akan langsung tidak berlaku.')">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="font-medium text-indigo-600 hover:underline dark:text-indigo-400">Reset Sandi</button>
                                                </form>

                                                <form action="{{ route('admin.users.status', $user) }}" method="POST" class="ms-3 inline"
                                                      onsubmit="return confirm('{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan kembali' }} akun {{ $user->name }}?')">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="font-medium {{ $user->is_active ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400' }} hover:underline">
                                                        {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                    </button>
                                                </form>

                                                @if ($user->assessments_count === 0)
                                                    <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="ms-3 inline"
                                                          onsubmit="return confirm('Hapus akun {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="font-medium text-rose-600 hover:underline dark:text-rose-400">Hapus</button>
                                                    </form>
                                                @endif
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
        </div>
    </div>
</x-app-layout>
