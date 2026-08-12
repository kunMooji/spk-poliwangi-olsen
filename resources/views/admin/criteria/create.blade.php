<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">Tambah Kriteria</h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="POST" action="{{ route('admin.criteria.store') }}">
                @csrf
                @include('admin.criteria.form')
            </form>
        </div>
    </div>
</x-app-layout>
