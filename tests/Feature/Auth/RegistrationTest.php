<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
        $response->assertSee('Daftar sebagai calon mahasiswa');
    }

    public function test_pendaftaran_mandiri_selalu_menghasilkan_akun_calon_mahasiswa(): void
    {
        $this->post('/register', [
            'name' => 'Calon Mahasiswa Baru',
            'email' => 'calon@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertSame(
            User::ROLE_MAHASISWA,
            User::query()->where('email', 'calon@example.com')->firstOrFail()->role,
        );
    }

    public function test_peran_admin_tidak_dapat_disusupkan_lewat_formulir_pendaftaran(): void
    {
        $this->post('/register', [
            'name' => 'Penyusup',
            'email' => 'penyusup@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $user = User::query()->where('email', 'penyusup@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_MAHASISWA, $user->role);
        $this->assertFalse($user->isAdmin());

        // Konsekuensinya: panel admin tetap tertutup untuk akun hasil pendaftaran.
        $this->actingAs($user)->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
