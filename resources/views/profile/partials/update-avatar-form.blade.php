<section>
    <header>
        <p class="text-sm text-ink-600 dark:text-porcelain-300">
            {{ __('Unggah foto agar mudah dikenali di beranda dan menu navigasi.') }}
        </p>
    </header>

    <div class="mt-5 flex items-center gap-5" x-data="{ preview: null }">
        <div class="relative h-16 w-16 shrink-0 sm:h-20 sm:w-20">
            <img
                x-show="preview"
                :src="preview"
                x-cloak
                class="h-16 w-16 rounded-full object-cover ring-2 ring-white shadow sm:h-20 sm:w-20 dark:ring-gray-700"
                alt="Pratinjau foto profil"
            >
            @if ($user->avatar)
                <img
                    x-show="!preview"
                    src="{{ $user->avatar_url }}"
                    class="h-16 w-16 rounded-full object-cover ring-2 ring-white shadow sm:h-20 sm:w-20 dark:ring-gray-700"
                    alt="Foto profil {{ $user->name }}"
                >
            @else
                <div x-show="!preview" class="flex h-16 w-16 items-center justify-center rounded-full bg-brand-100 font-mono text-lg font-bold text-brand-800 ring-2 ring-white shadow sm:h-20 sm:w-20 sm:text-xl dark:bg-brand-500/20 dark:text-brand-200 dark:ring-gray-700">
                    {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                </div>
            @endif
        </div>

        <div class="flex-1 space-y-2.5">
            <div class="flex flex-wrap items-center gap-3">
                <form method="post" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data">
                    @csrf
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        <span>{{ __('Pilih foto...') }}</span>
                        <input
                            type="file"
                            name="avatar"
                            accept="image/*"
                            class="hidden"
                            required
                            @change="
                                const file = $event.target.files[0];
                                if (file) { preview = URL.createObjectURL(file); $el.closest('form').requestSubmit(); }
                            "
                        >
                    </label>
                </form>

                @if ($user->avatar)
                    <form method="post" action="{{ route('profile.avatar.destroy') }}" onsubmit="return confirm('{{ __('Hapus foto profil?') }}')">
                        @csrf
                        @method('delete')
                        <button type="submit" class="text-sm font-medium text-red-600 underline hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                            {{ __('Hapus foto') }}
                        </button>
                    </form>
                @endif
            </div>

            <x-input-error class="mt-2" :messages="$errors->get('avatar')" />

            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('JPG, PNG, atau WEBP. Maksimal 2MB.') }}</p>

            @if (session('status') === 'avatar-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="text-sm text-green-600 dark:text-green-400"
                >{{ __('Foto profil berhasil diperbarui.') }}</p>
            @endif
        </div>
    </div>
</section>
