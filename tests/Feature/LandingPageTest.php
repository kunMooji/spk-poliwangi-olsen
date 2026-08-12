<?php

namespace Tests\Feature;

use App\Models\Criteria;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_depan_menampilkan_data_master_yang_sebenarnya(): void
    {
        $this->seed();

        $response = $this->get(route('home'))->assertOk();

        $response->assertSee('Politeknik Negeri Banyuwangi');
        $response->assertSee('Enam dimensi kepribadian RIASEC');

        // Angka pada halaman dibaca dari basis data, bukan ditulis tetap.
        $this->assertSame(
            StudyProgram::query()->active()->count(),
            $response->viewData('programs')->count(),
        );
        $this->assertSame(
            Criteria::query()->active()->count(),
            $response->viewData('criteria')->count(),
        );

        $response->assertSee(StudyProgram::query()->active()->orderBy('name')->first()->name);
    }

    public function test_tamu_melihat_ajakan_daftar_dan_masuk(): void
    {
        $this->seed();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Mulai Tes Gratis')
            ->assertSee(route('register'), false)
            ->assertDontSee('Buka Dasbor Saya');
    }

    public function test_pengguna_yang_sudah_masuk_diarahkan_ke_dasbor(): void
    {
        $this->seed();

        $this->actingAs(User::query()->where('role', User::ROLE_MAHASISWA)->firstOrFail())
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Buka Dasbor Saya')
            ->assertDontSee('Mulai Tes Gratis');
    }

    public function test_halaman_depan_tetap_terbuka_saat_data_master_kosong(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Data program studi belum tersedia');
    }

    public function test_halaman_masuk_dan_daftar_memakai_kerangka_tamu(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk ke akun Anda')
            ->assertSee('Kembali ke halaman depan');

        $this->get(route('register'))
            ->assertOk()
            ->assertSee('Daftar sebagai calon mahasiswa')
            ->assertSee('Akun administrator tidak dapat dibuat melalui halaman ini');
    }
}
