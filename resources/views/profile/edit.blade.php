<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="font-mono text-[9px] font-bold uppercase tracking-[0.18em] text-brand-600 dark:text-brand-300">Pengaturan akun</p>
            <h2 class="font-display text-base font-bold text-ink-900 dark:text-porcelain-50 sm:text-lg">Profil saya</h2>
        </div>
    </x-slot>

    <div class="lg:h-[calc(100dvh-4.125rem)] lg:overflow-hidden">
        <div class="mx-auto flex h-full max-w-[1440px] flex-col px-4 py-5 sm:px-6 lg:px-8 lg:py-6">
            <div class="mb-5 shrink-0 lg:mb-4">
                <p class="font-mono text-[10px] font-bold uppercase tracking-[0.16em] text-brand-600 dark:text-brand-300">Ruang pribadi</p>
                <h1 class="mt-1 font-display text-2xl font-bold tracking-tight text-ink-900 dark:text-porcelain-50 sm:text-3xl">Kelola profil dan keamanan akun</h1>
                <p class="mt-1 text-sm text-ink-600 dark:text-porcelain-300">Perbarui informasi yang tampil di akun SPK Anda.</p>
            </div>

            <div class="grid min-h-0 flex-1 gap-4 lg:grid-cols-12 lg:grid-rows-1">
                <section class="student-panel flex min-h-0 flex-col p-5 sm:p-6 lg:col-span-4" aria-labelledby="profile-photo-heading">
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <p class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-brand-600 dark:text-brand-300">Identitas</p>
                            <h2 id="profile-photo-heading" class="mt-1 text-lg font-bold text-ink-900 dark:text-porcelain-50">Foto profil</h2>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 dark:bg-brand-500/15 dark:text-brand-300">
                            <x-heroicon-o-user-circle class="h-5 w-5" />
                        </span>
                    </div>
                    <div class="min-h-0 flex-1">
                        @include('profile.partials.update-avatar-form')
                    </div>
                </section>

                <section class="student-panel min-h-0 p-5 sm:p-6 lg:col-span-4" aria-labelledby="profile-info-heading">
                    <div class="mb-5 flex items-center justify-between">
                        <div>
                            <p class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-brand-600 dark:text-brand-300">Data akun</p>
                            <h2 id="profile-info-heading" class="mt-1 text-lg font-bold text-ink-900 dark:text-porcelain-50">Informasi profil</h2>
                        </div>
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                            <x-heroicon-o-identification class="h-5 w-5" />
                        </span>
                    </div>
                    @include('profile.partials.update-profile-information-form')
                </section>

                <div class="grid min-h-0 gap-4 lg:col-span-4 lg:grid-rows-[minmax(0,1fr)_auto]">
                    <section class="student-panel min-h-0 p-5 sm:p-6" aria-labelledby="password-heading">
                        <div class="mb-5 flex items-center justify-between">
                            <div>
                                <p class="font-mono text-[9px] font-bold uppercase tracking-[0.16em] text-brand-600 dark:text-brand-300">Keamanan</p>
                                <h2 id="password-heading" class="mt-1 text-lg font-bold text-ink-900 dark:text-porcelain-50">Kata sandi</h2>
                            </div>
                            <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/15 dark:text-emerald-300">
                                <x-heroicon-o-lock-closed class="h-5 w-5" />
                            </span>
                        </div>
                        @include('profile.partials.update-password-form')
                    </section>

                    <section class="rounded-2xl border border-red-100 bg-red-50/65 p-5 dark:border-red-500/20 dark:bg-red-500/10" aria-labelledby="delete-account-heading">
                        @include('profile.partials.delete-user-form')
                    </section>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
