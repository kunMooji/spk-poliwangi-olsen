<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Assessment;
use App\Models\AssessmentAnswer;
use App\Models\Criteria;
use App\Models\Period;
use App\Models\RiasecQuestion;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Models\User;
use App\Support\Rapor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fitur lanjutan: gelombang PMB, manajemen akun, catatan perubahan,
 * ekspor CSV, lembar cetak, simpan sebagian jawaban, dan perbandingan sesi.
 */
class NewFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Setting::forgetCache();

        $this->admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
        $this->student = User::query()->where('role', User::ROLE_MAHASISWA)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function biodataPayload(): array
    {
        return [
            'full_name' => 'Rizky Calon Mahasiswa',
            'gender' => 'L',
            'school_name' => 'SMA Negeri 1 Banyuwangi',
            'education_level' => 'SMA',
            'school_major' => 'IPA',
            'graduation_year' => (int) date('Y'),
            'phone' => '081234567890',
            'rapor_semesters' => array_fill_keys(Rapor::SEMESTERS, 85),
            'subject_scores' => Rapor::supportSubjects()->mapWithKeys(fn ($subject) => [$subject->id => 85])->all(),
            'priorities' => StudyProgram::query()->active()->orderBy('code')->take(3)->pluck('id')->all(),
        ];
    }

    private function completeAssessment(?User $user = null, int $score = 4): Assessment
    {
        $user ??= $this->student;

        $this->actingAs($user)->post(route('assessments.store'), $this->biodataPayload());

        $assessment = Assessment::query()->where('user_id', $user->id)->latest()->firstOrFail();

        $this->actingAs($user)->post(route('assessments.answers.store', $assessment), [
            'answers' => RiasecQuestion::query()->active()->pluck('id')->mapWithKeys(fn ($id) => [$id => $score])->all(),
        ]);

        return $assessment->refresh();
    }

    // ── Gelombang PMB ───────────────────────────────────────────────────────

    public function test_sesi_tes_baru_ditandai_gelombang_yang_sedang_aktif(): void
    {
        $active = Period::query()->active()->firstOrFail();

        $assessment = $this->completeAssessment();

        $this->assertSame($active->id, $assessment->period_id);
    }

    public function test_hanya_satu_gelombang_boleh_aktif(): void
    {
        $lama = Period::query()->active()->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.periods.store'), [
            'name' => 'Gelombang 2',
            'academic_year' => '2026/2027',
            'starts_at' => '2026-09-01',
            'ends_at' => '2026-12-31',
            'is_active' => '1',
        ])->assertRedirect(route('admin.periods.index'));

        $this->assertFalse($lama->refresh()->is_active);
        $this->assertSame(1, Period::query()->active()->count());
    }

    public function test_gelombang_yang_sudah_dipakai_tidak_dapat_dihapus(): void
    {
        $assessment = $this->completeAssessment();
        $period = Period::query()->findOrFail($assessment->period_id);

        $this->actingAs($this->admin)
            ->delete(route('admin.periods.destroy', $period))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('periods', ['id' => $period->id]);
    }

    public function test_mengganti_gelombang_aktif_tidak_memindahkan_tes_lama(): void
    {
        $assessment = $this->completeAssessment();
        $gelombangAsal = $assessment->period_id;

        $this->actingAs($this->admin)->post(route('admin.periods.store'), [
            'name' => 'Gelombang 2',
            'academic_year' => '2026/2027',
            'is_active' => '1',
        ]);

        $this->assertSame($gelombangAsal, $assessment->refresh()->period_id);
    }

    public function test_rekap_dapat_disaring_per_gelombang(): void
    {
        $assessment = $this->completeAssessment();

        $lain = Period::query()->create([
            'name' => 'Gelombang Lain',
            'academic_year' => '2020/2021',
            'is_active' => false,
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.recap.index', ['period' => $assessment->period_id]))
            ->assertOk()
            ->assertSee($assessment->code);

        $this->actingAs($this->admin)
            ->get(route('admin.recap.index', ['period' => $lain->id]))
            ->assertOk()
            ->assertDontSee($assessment->code);
    }

    // ── Manajemen akun ──────────────────────────────────────────────────────

    public function test_akun_nonaktif_tidak_dapat_masuk(): void
    {
        $this->student->update(['is_active' => false]);

        $this->post(route('login'), [
            'email' => $this->student->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admin_dapat_menonaktifkan_dan_mengaktifkan_akun_calon_mahasiswa(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.users.status', $this->student))
            ->assertSessionHas('success');

        $this->assertFalse($this->student->refresh()->is_active);

        $this->actingAs($this->admin)->put(route('admin.users.status', $this->student));

        $this->assertTrue($this->student->refresh()->is_active);
    }

    public function test_sesi_berjalan_terputus_setelah_akunnya_dinonaktifkan(): void
    {
        $this->actingAs($this->student)->get(route('assessments.index'))->assertOk();

        $this->student->update(['is_active' => false]);

        $this->actingAs($this->student)
            ->get(route('assessments.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_dapat_menyetel_ulang_kata_sandi_calon_mahasiswa(): void
    {
        $lama = $this->student->password;

        $this->actingAs($this->admin)
            ->put(route('admin.users.password', $this->student))
            ->assertSessionHas('success');

        $this->assertNotSame($lama, $this->student->refresh()->password);
    }

    public function test_admin_tidak_dapat_mengubah_akun_admin_lewat_antarmuka(): void
    {
        $lain = User::query()->create([
            'name' => 'Admin Kedua',
            'email' => 'admin2@poliwangi.ac.id',
            'password' => 'password',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.users.status', $lain))
            ->assertSessionHas('error');

        $this->assertTrue($lain->refresh()->is_active);
    }

    public function test_akun_yang_sudah_pernah_tes_tidak_dapat_dihapus(): void
    {
        $this->completeAssessment();

        $this->actingAs($this->admin)
            ->delete(route('admin.users.destroy', $this->student))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->student->id]);
    }

    public function test_calon_mahasiswa_tidak_dapat_membuka_manajemen_akun(): void
    {
        $this->actingAs($this->student)->get(route('admin.users.index'))->assertForbidden();
    }

    // ── Catatan perubahan ───────────────────────────────────────────────────

    public function test_perubahan_bobot_kriteria_tercatat_beserta_nilai_lama_dan_barunya(): void
    {
        $criterion = Criteria::query()->orderBy('code')->firstOrFail();
        $bobotLama = $criterion->weight;

        $this->actingAs($this->admin)->put(route('admin.criteria.update', $criterion), [
            'code' => $criterion->code,
            'name' => $criterion->name,
            'weight' => 0.42,
            'type' => $criterion->type,
            'source' => $criterion->source,
            'sort_order' => $criterion->sort_order,
            'is_active' => '1',
        ]);

        $log = ActivityLog::query()
            ->where('subject_type', Criteria::class)
            ->where('subject_id', $criterion->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('updated', $log->action);
        $this->assertSame($this->admin->id, $log->user_id);
        $this->assertArrayHasKey('weight', $log->changes);
        $this->assertEquals($bobotLama, $log->changes['weight']['from']);
        $this->assertEquals(0.42, $log->changes['weight']['to']);
    }

    public function test_perubahan_pengaturan_algoritma_tercatat(): void
    {
        // Formulir pengaturan mengirim seluruh kunci sekaligus, bukan sebagian.
        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'settings' => [
                'threshold' => 85,
                'threshold_mode' => 'normal',
                'lambda' => 0.5,
                'epsilon' => 0.000001,
                'unselected_priority_score' => 0,
                'likert_min' => 1,
                'likert_max' => 5,
                'min_priorities' => 3,
            ],
        ])->assertSessionHasNoErrors();

        $threshold = Setting::query()->where('key', 'threshold')->firstOrFail();

        $log = ActivityLog::query()
            ->where('subject_type', Setting::class)
            ->where('subject_id', $threshold->id)
            ->where('action', 'updated')
            ->where('user_id', $this->admin->id)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('85', (string) $log->changes['value']['to']);
    }

    public function test_seeder_tidak_ikut_tercatat_karena_berjalan_tanpa_pengguna(): void
    {
        // setUp() menjalankan seed() penuh tanpa pengguna yang masuk.
        $this->assertSame(0, ActivityLog::query()->count());
    }

    public function test_kata_sandi_tidak_pernah_ikut_tersimpan_di_catatan_perubahan(): void
    {
        $this->actingAs($this->admin)->put(route('admin.users.password', $this->student));

        $log = ActivityLog::query()
            ->where('subject_type', User::class)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('(disetel ulang admin)', $log->changes['password']['to']);
        $this->assertStringNotContainsString('$2y$', json_encode($log->changes));
    }

    public function test_halaman_catatan_perubahan_hanya_untuk_admin(): void
    {
        $this->actingAs($this->admin)->get(route('admin.activity-logs.index'))->assertOk();
        $this->actingAs($this->student)->get(route('admin.activity-logs.index'))->assertForbidden();
    }

    // ── Ekspor CSV ──────────────────────────────────────────────────────────

    public function test_admin_dapat_mengunduh_rekap_sebagai_csv(): void
    {
        $assessment = $this->completeAssessment();

        $response = $this->actingAs($this->admin)->get(route('admin.recap.export'));

        $response->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertDownload();

        $isi = $response->streamedContent();

        $this->assertStringContainsString('Kode Tes', $isi);
        $this->assertStringContainsString($assessment->code, $isi);
        $this->assertStringContainsString($assessment->full_name, $isi);
    }

    public function test_ekspor_csv_mengikuti_filter_yang_sedang_aktif(): void
    {
        $assessment = $this->completeAssessment();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.recap.export', ['q' => 'kata-kunci-yang-tidak-ada']));

        $this->assertStringNotContainsString($assessment->code, $response->streamedContent());
    }

    public function test_calon_mahasiswa_tidak_dapat_mengunduh_rekap(): void
    {
        $this->actingAs($this->student)->get(route('admin.recap.export'))->assertForbidden();
    }

    // ── Lembar cetak ────────────────────────────────────────────────────────

    public function test_calon_mahasiswa_dapat_membuka_lembar_cetak_miliknya(): void
    {
        $assessment = $this->completeAssessment();

        $this->actingAs($this->student)
            ->get(route('assessments.print', $assessment))
            ->assertOk()
            ->assertSee($assessment->code)
            ->assertSee('Hasil Rekomendasi Program Studi')
            ->assertSee('saran, bukan keputusan', false);
    }

    public function test_lembar_cetak_tidak_dapat_dibuka_orang_lain(): void
    {
        $assessment = $this->completeAssessment();

        $orangLain = User::query()->create([
            'name' => 'Calon Lain',
            'email' => 'lain@example.com',
            'password' => 'password',
            'role' => User::ROLE_MAHASISWA,
        ]);

        $this->actingAs($orangLain)
            ->get(route('assessments.print', $assessment))
            ->assertForbidden();
    }

    public function test_lembar_cetak_belum_tersedia_sebelum_kuesioner_selesai(): void
    {
        $this->actingAs($this->student)->post(route('assessments.store'), $this->biodataPayload());
        $assessment = Assessment::query()->where('user_id', $this->student->id)->latest()->firstOrFail();

        $this->actingAs($this->student)
            ->get(route('assessments.print', $assessment))
            ->assertRedirect(route('assessments.questionnaire', $assessment));
    }

    // ── Simpan sebagian jawaban ─────────────────────────────────────────────

    public function test_jawaban_sebagian_tersimpan_tanpa_menjalankan_perhitungan(): void
    {
        $this->actingAs($this->student)->post(route('assessments.store'), $this->biodataPayload());
        $assessment = Assessment::query()->where('user_id', $this->student->id)->latest()->firstOrFail();

        $sebagian = RiasecQuestion::query()->active()->take(5)->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => 3])->all();

        $this->actingAs($this->student)
            ->postJson(route('assessments.answers.autosave', $assessment), ['answers' => $sebagian])
            ->assertOk()
            ->assertJson(['saved' => 5]);

        $this->assertSame(5, $assessment->answers()->count());

        // Perhitungan tidak boleh berjalan dari jawaban yang belum lengkap.
        $this->assertSame('questionnaire', $assessment->refresh()->status);
        $this->assertNull($assessment->recommended_program_id);
    }

    public function test_menjawab_ulang_butir_yang_sama_menimpa_nilai_sebelumnya(): void
    {
        $this->actingAs($this->student)->post(route('assessments.store'), $this->biodataPayload());
        $assessment = Assessment::query()->where('user_id', $this->student->id)->latest()->firstOrFail();

        $pertanyaan = RiasecQuestion::query()->active()->firstOrFail();

        $this->actingAs($this->student)
            ->postJson(route('assessments.answers.autosave', $assessment), ['answers' => [$pertanyaan->id => 2]]);

        $this->actingAs($this->student)
            ->postJson(route('assessments.answers.autosave', $assessment), ['answers' => [$pertanyaan->id => 5]]);

        $this->assertSame(1, $assessment->answers()->count());
        $this->assertSame(5, AssessmentAnswer::query()
            ->where('assessment_id', $assessment->id)
            ->where('riasec_question_id', $pertanyaan->id)
            ->value('score'));
    }

    public function test_jawaban_tersimpan_muncul_kembali_saat_kuesioner_dibuka_lagi(): void
    {
        $this->actingAs($this->student)->post(route('assessments.store'), $this->biodataPayload());
        $assessment = Assessment::query()->where('user_id', $this->student->id)->latest()->firstOrFail();

        $pertanyaan = RiasecQuestion::query()->active()->firstOrFail();

        $this->actingAs($this->student)
            ->postJson(route('assessments.answers.autosave', $assessment), ['answers' => [$pertanyaan->id => 4]]);

        $this->actingAs($this->student)
            ->get(route('assessments.questionnaire', $assessment))
            ->assertOk()
            ->assertSee('1 jawaban', false);
    }

    public function test_autosave_milik_orang_lain_ditolak(): void
    {
        $this->actingAs($this->student)->post(route('assessments.store'), $this->biodataPayload());
        $assessment = Assessment::query()->where('user_id', $this->student->id)->latest()->firstOrFail();

        $orangLain = User::query()->create([
            'name' => 'Calon Lain',
            'email' => 'lain2@example.com',
            'password' => 'password',
            'role' => User::ROLE_MAHASISWA,
        ]);

        $this->actingAs($orangLain)
            ->postJson(route('assessments.answers.autosave', $assessment), ['answers' => [1 => 3]])
            ->assertForbidden();
    }

    // ── Perbandingan antar sesi ─────────────────────────────────────────────

    public function test_perbandingan_meminta_minimal_dua_tes_selesai(): void
    {
        $this->completeAssessment();

        $this->actingAs($this->student)
            ->get(route('assessments.compare'))
            ->assertOk()
            ->assertSee('minimal dua kali tes', false);
    }

    public function test_perbandingan_menampilkan_dua_sesi_terakhir(): void
    {
        $pertama = $this->completeAssessment(score: 2);
        $kedua = $this->completeAssessment(score: 5);

        $this->actingAs($this->student)
            ->get(route('assessments.compare'))
            ->assertOk()
            ->assertSee($pertama->code)
            ->assertSee($kedua->code)
            ->assertSee('Pergeseran Profil Minat');
    }

    public function test_perbandingan_hanya_memuat_tes_milik_sendiri(): void
    {
        $milikOrangLain = $this->completeAssessment();

        $orangLain = User::query()->create([
            'name' => 'Calon Lain',
            'email' => 'lain3@example.com',
            'password' => 'password',
            'role' => User::ROLE_MAHASISWA,
        ]);

        $this->actingAs($orangLain)
            ->get(route('assessments.compare'))
            ->assertOk()
            ->assertDontSee($milikOrangLain->code);
    }

    public function test_admin_tidak_ikut_membandingkan_hasil_tes(): void
    {
        $this->actingAs($this->admin)
            ->get(route('assessments.compare'))
            ->assertRedirect(route('admin.dashboard'));
    }
}
