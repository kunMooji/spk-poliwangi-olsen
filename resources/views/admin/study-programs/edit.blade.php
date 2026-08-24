<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            Ubah Program Studi &mdash; {{ $program->full_name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="mx-auto max-w-none space-y-6 px-5 sm:px-8 lg:px-10 xl:px-12">
            <x-flash />

            <form method="POST" action="{{ route('admin.study-programs.update', $program) }}">
                @csrf
                @method('PUT')
                @include('admin.study-programs.form')
            </form>
        </div>
    </div>
</x-app-layout>
