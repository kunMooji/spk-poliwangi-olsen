@if (session('success'))
    <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
@endif

@if (session('info'))
    <x-alert type="info" class="mb-4">{{ session('info') }}</x-alert>
@endif

@if (session('warning'))
    <x-alert type="warning" class="mb-4">{{ session('warning') }}</x-alert>
@endif

@if (session('error'))
    <x-alert type="error" class="mb-4">{{ session('error') }}</x-alert>
@endif

@if ($errors->any())
    <x-alert type="error" class="mb-4">
        <p class="font-semibold">Periksa kembali isian berikut:</p>
        <ul class="mt-2 list-disc space-y-1 ps-5">
            @foreach ($errors->all() as $message)
                <li>{{ $message }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
