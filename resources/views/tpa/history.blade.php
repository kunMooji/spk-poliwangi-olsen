@extends('tpa.layout')

@section('title', 'Riwayat')

@section('content')
    <section class="rounded-[1.5rem] border border-black/5 bg-white p-5 shadow-sm dark:border-white/10 dark:bg-ink-900/60">
        <h1 class="text-xl font-extrabold tracking-tight">Riwayat tryout</h1>
        <p class="text-sm text-ink-500 dark:text-porcelain-300/80">Buka tryout yang sudah selesai untuk melihat lagi jawaban dan pembahasannya.</p>
        @include('tpa.partials.tryout-list', ['tryouts' => $tryouts])
        <div class="mt-4">{{ $tryouts->links() }}</div>
    </section>
@endsection
