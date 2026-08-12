<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Ubah Program Studi &mdash; {{ $program->full_name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash />

            <form method="POST" action="{{ route('admin.study-programs.update', $program) }}">
                @csrf
                @method('PUT')
                @include('admin.study-programs.form')
            </form>
        </div>
    </div>
</x-app-layout>
