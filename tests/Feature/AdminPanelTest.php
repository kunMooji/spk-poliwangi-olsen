<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Criteria;
use App\Models\RiasecQuestion;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Models\Subject;
use App\Models\User;
use App\Support\Rapor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
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
     * Kerjakan satu sesi tes lengkap sebagai calon mahasiswa.
     */
    private function completeAssessment(?User $user = null): Assessment
    {
        $user ??= $this->student;

        $this->actingAs($user)->post(route('assessments.store'), [
            'full_name' => $user->name,
            'gender' => 'L',
            'school_name' => 'SMA Negeri 1 Banyuwangi',
            'school_major' => 'IPA',
            'graduation_year' => (int) date('Y'),
            'phone' => '081234567890',
            'rapor_semesters' => array_fill_keys(Rapor::SEMESTERS, 85),
            'subject_scores' => Rapor::supportSubjects()->mapWithKeys(fn ($subject) => [$subject->id => 85])->all(),
            'priorities' => StudyProgram::query()->active()->orderBy('code')->take(3)->pluck('id')->all(),
        ]);

        $assessment = Assessment::query()->where('user_id', $user->id)->latest()->firstOrFail();

        $this->actingAs($user)->post(route('assessments.answers.store', $assessment), [
            'answers' => RiasecQuestion::query()->active()->pluck('id')->mapWithKeys(fn ($id) => [$id => 4])->all(),
        ]);

        return $assessment->refresh();
    }

    public function test_tamu_tidak_dapat_membuka_panel_admin(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.recap.index'))->assertRedirect(route('login'));
    }

    public function test_calon_mahasiswa_ditolak_di_seluruh_halaman_admin(): void
    {
        $this->actingAs($this->student);

        foreach ([
            route('admin.dashboard'),
            route('admin.study-programs.index'),
            route('admin.criteria.index'),
            route('admin.questions.index'),
            route('admin.tracer.index'),
            route('admin.settings.edit'),
            route('admin.recap.index'),
        ] as $url) {
            $this->get($url)->assertForbidden();
        }
    }

    public function test_admin_tidak_ikut_mengerjakan_tes(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('assessments.create'))->assertRedirect(route('admin.dashboard'));
        $this->get(route('assessments.index'))->assertRedirect(route('admin.dashboard'));
        $this->post(route('assessments.store'), [])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_beranda_admin_dialihkan_ke_panel(): void
    {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Panel Administrator');
    }

    public function test_seluruh_halaman_panel_admin_dapat_dibuka(): void
    {
        $this->actingAs($this->admin);

        $program = StudyProgram::query()->firstOrFail();
        $criterion = Criteria::query()->firstOrFail();
        $question = RiasecQuestion::query()->firstOrFail();
        $subject = Subject::query()->firstOrFail();

        $pages = [
            route('admin.study-programs.index') => 'Program Studi',
            route('admin.study-programs.create') => 'Mata Pelajaran Pendukung',
            route('admin.study-programs.edit', $program) => 'Profil Kepribadian RIASEC Prodi',
            route('admin.subjects.index') => 'Mata Pelajaran',
            route('admin.subjects.create') => 'Tambah Mata Pelajaran',
            route('admin.subjects.edit', $subject) => 'Ubah Mata Pelajaran',
            route('admin.criteria.index') => 'Total bobot kriteria aktif',
            route('admin.criteria.create') => 'Sumber Nilai',
            route('admin.criteria.edit', $criterion) => 'Jenis Kriteria',
            route('admin.questions.index') => 'Pernyataan Kuesioner RIASEC',
            route('admin.questions.create') => 'Dimensi RIASEC',
            route('admin.questions.edit', $question) => 'Urutan Tampil',
            route('admin.tracer.index') => 'Tracer Study',
            route('admin.settings.edit') => 'Pengaturan Algoritma',
            route('admin.recap.index') => 'Rekap Hasil Tes',
            route('admin.statistics') => 'Statistik Institusional',
        ];

        foreach ($pages as $url => $expected) {
            $this->get($url)->assertOk()->assertSee($expected);
        }
    }

    public function test_admin_melihat_riwayat_tes_seluruh_calon_mahasiswa(): void
    {
        $other = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'name' => 'Mahasiswa Kedua']);

        $first = $this->completeAssessment();
        $second = $this->completeAssessment($other);

        $this->actingAs($this->admin)
            ->get(route('admin.recap.index'))
            ->assertOk()
            ->assertSee($first->code)
            ->assertSee($second->code);

        $this->actingAs($this->admin)
            ->get(route('admin.recap.show', $second))
            ->assertOk()
            ->assertSee('Mahasiswa Kedua')
            ->assertSee('Hasil Rekomendasi');
    }

    public function test_rekap_dapat_disaring_berdasarkan_calon_mahasiswa(): void
    {
        $other = User::factory()->create(['role' => User::ROLE_MAHASISWA, 'name' => 'Mahasiswa Kedua']);

        $first = $this->completeAssessment();
        $second = $this->completeAssessment($other);

        $this->actingAs($this->admin)
            ->get(route('admin.recap.index', ['q' => 'Mahasiswa Kedua']))
            ->assertOk()
            ->assertSee($second->code)
            ->assertDontSee($first->code);
    }

    public function test_analisis_sensitivitas_menampilkan_skenario_lambda_dan_bobot(): void
    {
        $assessment = $this->completeAssessment();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.recap.sensitivity', $assessment))
            ->assertOk()
            ->assertSee('Analisis Sensitivitas')
            ->assertSee('Pengaruh Nilai λ', false)
            ->assertSee('Pengaruh Pergeseran Bobot');

        $analysis = $response->viewData('analysis');

        // 11 skenario λ + 4 pergeseran untuk tiap kriteria aktif.
        $this->assertCount(11, $analysis['lambda']);
        $this->assertSame(
            Criteria::query()->active()->count() * count(\App\Services\SensitivityService::DEFAULT_SHIFTS),
            count($analysis['weights']),
        );
        $this->assertSame($assessment->recommended_program_id, $analysis['baseline']['winner']);
    }

    public function test_analisis_sensitivitas_tidak_mengubah_hasil_asli(): void
    {
        $assessment = $this->completeAssessment();
        $before = $assessment->only(['recommended_program_id', 'recommended_k_value', 'weights_snapshot']);

        $this->actingAs($this->admin)->get(route('admin.recap.sensitivity', $assessment))->assertOk();

        $this->assertSame($before, $assessment->refresh()->only(['recommended_program_id', 'recommended_k_value', 'weights_snapshot']));
    }

    public function test_analisis_sensitivitas_menolak_tes_yang_belum_selesai(): void
    {
        $this->actingAs($this->student)->post(route('assessments.store'), [
            'full_name' => 'Belum Selesai',
            'rapor_semesters' => array_fill_keys(Rapor::SEMESTERS, 80),
            'priorities' => StudyProgram::query()->active()->take(3)->pluck('id')->all(),
        ]);

        $assessment = Assessment::query()->latest()->firstOrFail();

        $this->actingAs($this->admin)
            ->get(route('admin.recap.sensitivity', $assessment))
            ->assertRedirect(route('admin.recap.show', $assessment));
    }

    public function test_statistik_institusional_menampilkan_kesenjangan_minat(): void
    {
        $this->completeAssessment();
        $this->completeAssessment(User::factory()->create(['role' => User::ROLE_MAHASISWA]));

        $response = $this->actingAs($this->admin)
            ->get(route('admin.statistics'))
            ->assertOk()
            ->assertSee('Minat dibanding Rekomendasi')
            ->assertSee('Asal Sekolah Terbanyak')
            ->assertSee('SMA Negeri 1 Banyuwangi');

        $gap = $response->viewData('interestGap');

        $this->assertNotEmpty($gap);

        // Tiap baris membandingkan jumlah pilihan pertama dengan jumlah rekomendasi.
        foreach ($gap as $row) {
            $this->assertSame($row['chosen'] - $row['recommended'], $row['gap']);
        }

        $this->assertSame(2, $response->viewData('totalCompleted'));
    }

    public function test_statistik_aman_ketika_belum_ada_tes(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.statistics'))
            ->assertOk()
            ->assertSee('Belum ada tes yang selesai');
    }

    public function test_admin_menghapus_data_tes(): void
    {
        $assessment = $this->completeAssessment();

        $this->actingAs($this->admin)
            ->delete(route('admin.recap.destroy', $assessment))
            ->assertRedirect(route('admin.recap.index'));

        $this->assertDatabaseCount('assessments', 0);
        $this->assertDatabaseCount('assessment_results', 0);
    }

    public function test_admin_menambah_dan_mengubah_program_studi(): void
    {
        $payload = [
            'code' => 'TRPL',
            'name' => 'Teknologi Rekayasa Perangkat Lunak',
            'level' => 'D4',
            'department' => 'Teknologi Informasi',
            'support_subjects' => Subject::query()->whereIn('code', ['matematika', 'fisika'])->pluck('id')->all(),
            'riasec_r' => 60, 'riasec_i' => 85, 'riasec_a' => 40,
            'riasec_s' => 30, 'riasec_e' => 45, 'riasec_c' => 70,
            'alumni_count' => 200,
            'employed_count' => 150,
            'tracer_year' => (int) date('Y') - 1,
            'is_active' => 1,
        ];

        $this->actingAs($this->admin)
            ->post(route('admin.study-programs.store'), $payload)
            ->assertRedirect(route('admin.study-programs.index'));

        $program = StudyProgram::query()->where('code', 'TRPL')->firstOrFail();

        // employment_rate selalu diturunkan dari data tracer, bukan diisi manual.
        $this->assertSame(0.75, $program->employment_rate);

        $this->actingAs($this->admin)
            ->put(route('admin.study-programs.update', $program), ['name' => 'TRPL Poliwangi'] + $payload)
            ->assertRedirect(route('admin.study-programs.index'));

        $this->assertSame('TRPL Poliwangi', $program->refresh()->name);
    }

    public function test_alumni_terserap_tidak_boleh_melebihi_total_alumni(): void
    {
        $program = StudyProgram::query()->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.study-programs.update', $program), [
                'code' => $program->code,
                'name' => $program->name,
                'level' => $program->level,
                'riasec_r' => 10, 'riasec_i' => 10, 'riasec_a' => 10,
                'riasec_s' => 10, 'riasec_e' => 10, 'riasec_c' => 10,
                'alumni_count' => 100,
                'employed_count' => 120,
            ])
            ->assertSessionHasErrors('employed_count');
    }

    public function test_prodi_yang_dipakai_pada_tes_tidak_dapat_dihapus(): void
    {
        $assessment = $this->completeAssessment();
        $program = $assessment->priorities()->firstOrFail()->studyProgram;

        $this->actingAs($this->admin)
            ->from(route('admin.study-programs.index'))
            ->delete(route('admin.study-programs.destroy', $program))
            ->assertRedirect(route('admin.study-programs.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($program);
    }

    public function test_prodi_tanpa_riwayat_tes_dapat_dihapus(): void
    {
        $program = StudyProgram::query()->create([
            'code' => 'SEMENTARA',
            'name' => 'Prodi Uji Coba',
            'level' => 'D3',
        ]);

        $this->actingAs($this->admin)
            ->delete(route('admin.study-programs.destroy', $program))
            ->assertRedirect(route('admin.study-programs.index'));

        $this->assertModelMissing($program);
    }

    public function test_admin_mengubah_bobot_kriteria(): void
    {
        $criterion = Criteria::query()->where('code', 'C3')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.criteria.update', $criterion), [
                'code' => $criterion->code,
                'name' => $criterion->name,
                'weight' => 0.30,
                'type' => 'benefit',
                'source' => 'riasec',
                'sort_order' => $criterion->sort_order,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.criteria.index'));

        $this->assertSame(0.3, $criterion->refresh()->weight);
    }

    public function test_mapel_pendukung_dibatasi_dua_sesuai_aturan_snbp(): void
    {
        $program = StudyProgram::query()->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.study-programs.update', $program), [
                'code' => $program->code,
                'name' => $program->name,
                'level' => $program->level,
                'support_subjects' => Subject::query()->take(3)->pluck('id')->all(),
                'riasec_r' => 50, 'riasec_i' => 50, 'riasec_a' => 50,
                'riasec_s' => 50, 'riasec_e' => 50, 'riasec_c' => 50,
                'alumni_count' => 100,
                'employed_count' => 80,
            ])
            ->assertSessionHasErrors('support_subjects');
    }

    public function test_admin_mengelola_master_mata_pelajaran(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), [
                'name' => 'Teknik Pengelasan Kapal',
                'education_level' => 'SMK',
                'group' => 'Teknologi Manufaktur dan Rekayasa',
                'sort_order' => 99,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.subjects.index'));

        // Kode diturunkan dari nama bila admin mengosongkannya.
        $this->assertDatabaseHas('subjects', [
            'code' => 'teknik-pengelasan-kapal',
            'education_level' => 'SMK',
        ]);
    }

    public function test_mata_pelajaran_dengan_nama_yang_sudah_ada_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.subjects.store'), [
                'name' => Subject::query()->firstOrFail()->name,
                'education_level' => 'SMK',
                'sort_order' => 99,
            ])
            ->assertSessionHasErrors('code');
    }

    public function test_mata_pelajaran_yang_dipakai_prodi_tidak_dapat_dihapus(): void
    {
        $subject = Subject::query()->whereHas('studyPrograms')->firstOrFail();

        $this->actingAs($this->admin)
            ->from(route('admin.subjects.index'))
            ->delete(route('admin.subjects.destroy', $subject))
            ->assertRedirect(route('admin.subjects.index'));

        $this->assertDatabaseHas('subjects', ['id' => $subject->id]);
    }

    public function test_pernyataan_yang_sudah_dijawab_tidak_dapat_dihapus(): void
    {
        $this->completeAssessment();
        $question = RiasecQuestion::query()->active()->firstOrFail();

        $this->actingAs($this->admin)
            ->from(route('admin.questions.index'))
            ->delete(route('admin.questions.destroy', $question))
            ->assertRedirect(route('admin.questions.index'))
            ->assertSessionHas('error');

        $this->assertModelExists($question);
    }

    public function test_admin_menambah_pernyataan_kuesioner(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.questions.store'), [
                'statement' => 'Saya senang merancang alur kerja yang rapi dan terdokumentasi.',
                'dimension' => 'C',
                'sort_order' => 99,
                'is_active' => 1,
            ])
            ->assertRedirect(route('admin.questions.index'));

        $this->assertDatabaseHas('riasec_questions', ['sort_order' => 99, 'dimension' => 'C']);
    }

    public function test_admin_memperbarui_data_tracer_study(): void
    {
        $program = StudyProgram::query()->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.tracer.update'), [
                'programs' => [
                    $program->id => [
                        'alumni_count' => 400,
                        'employed_count' => 300,
                        'tracer_year' => (int) date('Y') - 1,
                    ],
                ],
            ])
            ->assertRedirect(route('admin.tracer.index'));

        $program->refresh();

        $this->assertSame(400, $program->alumni_count);
        $this->assertSame(0.75, $program->employment_rate);
        $this->assertNotNull($program->tracer_updated_at);
    }

    public function test_pengaturan_tersimpan_dan_divalidasi(): void
    {
        $current = Setting::values();

        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'settings' => array_merge($current, ['threshold' => 80, 'lambda' => 0.4]),
            ])
            ->assertRedirect(route('admin.settings.edit'));

        Setting::forgetCache();

        $this->assertSame(80.0, (float) Setting::get('threshold'));
        $this->assertSame(0.4, (float) Setting::get('lambda'));

        $this->actingAs($this->admin)
            ->put(route('admin.settings.update'), [
                'settings' => array_merge($current, ['likert_min' => 5, 'likert_max' => 3]),
            ])
            ->assertSessionHasErrors('settings.likert_max');
    }

    public function test_perubahan_bobot_tidak_mengubah_hasil_tes_lama(): void
    {
        $old = $this->completeAssessment();
        $oldSnapshot = $old->weights_snapshot;
        $oldK = $old->recommended_k_value;
        $oldThreshold = $old->threshold_used;

        $criterion = Criteria::query()->where('code', 'C3')->firstOrFail();

        $this->actingAs($this->admin)->put(route('admin.criteria.update', $criterion), [
            'code' => $criterion->code,
            'name' => $criterion->name,
            'weight' => 0.35,
            'type' => 'benefit',
            'source' => 'riasec',
            'sort_order' => $criterion->sort_order,
            'is_active' => 1,
        ]);

        $this->actingAs($this->admin)->put(route('admin.settings.update'), [
            'settings' => array_merge(Setting::values(), ['threshold' => 85]),
        ]);

        Setting::forgetCache();

        // Hasil lama membaca snapshot parameternya sendiri, jadi tidak boleh berubah.
        $old->refresh();
        $this->assertSame($oldSnapshot, $old->weights_snapshot);
        $this->assertSame($oldK, $old->recommended_k_value);
        $this->assertSame($oldThreshold, $old->threshold_used);

        // Tes baru wajib memakai parameter yang sudah diperbarui.
        $new = $this->completeAssessment(User::factory()->create(['role' => User::ROLE_MAHASISWA]));

        $this->assertSame(0.35, (float) $new->weights_snapshot['C3']['weight']);
        $this->assertSame(85.0, (float) $new->threshold_used);
    }
}
