<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Kerangka halaman untuk pengunjung yang belum masuk.
 *
 * Judul dan keterangan diserahkan tiap halaman supaya panel formulirnya
 * menjelaskan diri sendiri, bukan sekadar kotak kosong.
 */
class GuestLayout extends Component
{
    public function __construct(
        public ?string $title = null,
        public ?string $subtitle = null,
    ) {}

    public function render(): View
    {
        return view('layouts.guest');
    }
}
