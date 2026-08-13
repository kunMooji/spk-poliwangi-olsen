<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Ubah Gelombang &mdash; {{ $period->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="POST" action="{{ route('admin.periods.update', $period) }}">
                @csrf
                @method('PUT')
                @include('admin.periods.form')
            </form>
        </div>
    </div>
</x-app-layout>
