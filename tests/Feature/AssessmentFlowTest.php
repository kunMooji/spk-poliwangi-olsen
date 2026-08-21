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

class AssessmentFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Setting::forgetCache();

        $this->student = User::query()->where('role', User::ROLE_MAHASISWA)->firstOrFail();
    }

    /**
     * @return array<string, mixed>
     */
    private function biodataPayload(int $priorityCount = 3): array
    {
        $programIds = StudyProgram::query()->active()->orderBy('code')->take($priorityCount)->pluck('id')->all();

        return [
            'full_name' => 'Rizky Calon Mahasiswa',
            'gender' => 'L',
            'school_name' => 'SMA Negeri 1 Banyuwangi',
            'education_level' => 'SMA',
            'school_major' => 'IPA',
            'graduation_year' => (int) date('Y'),
            'phone' => '081234567890',
            'rapor_semesters' => array_fill_keys(Rapor::SEMESTERS, 85),
            'subject_scores' => Rapor::supportSubjects()
                ->mapWithKeys(fn ($subject) => [$subject->id => 85])
                ->all(),
            'priorities' => $programIds,
        ];
    }

    /**
     * @return array<int, int>
     */
    private function answersPayload(int $score = 4): array
    {
        return RiasecQuestion::query()->active()->pluck('id')
            ->mapWithKeys(fn ($id) => [$id => $score])
            ->all();
    }

    public function test_tamu_diarahkan_ke_halaman_masuk(): void
    {
        $this->get(route('assessments.create'))->assertRedirect(route('login'));
        $this->get(route('assessments.index'))->assertRedirect(route('login'));
    }

    public function test_kuesioner_berisi_empat_puluh_dua_pernyataan(): void
    {
        // Mengikuti lembar RIASEC Career Interest Test: 42 pernyataan dengan
        // jumlah butir per dimensi yang memang tidak sama rata. Persentase tetap
        // sebanding karena RiasecService membagi tiap dimensi dengan jumlah
        // butirnya sendiri, bukan dengan angka tetap.
        $this->assertSame(42, RiasecQuestion::query()->active()->count());

        foreach (['R', 'I', 'A', 'S', 'E', 'C'] as $dimension) {
            $this->assertGreaterThan(0, RiasecQuestion::query()->active()->where('dimension', $dimension)->count());
        }

        $this->assertSame(
            42,
            RiasecQuestion::query()->active()->whereIn('dimension', ['R', 'I', 'A', 'S', 'E', 'C'])->count()
        );
    }

    public function test_alur_lengkap_dari_biodata_sampai_hasil(): void
    {
        $this->actingAs($this->student);

        $this->get(route('assessments.create'))->assertOk()->assertSee('Urutan Prioritas Program Studi');

        $this->post(route('assessments.store'), $this->biodataPayload())->assertRedirect();

        $assessment = Assessment::query()->latest()->firstOrFail();

        $this->assertSame('questionnaire', $assessment->status);
        $this->assertSame(3, $assessment->priorities()->count());

        $this->get(route('assessments.questionnaire', $assessment))
            ->assertOk()
            ->assertSee('Kuesioner Minat Bakat RIASEC');

        $this->post(route('assessments.answers.store', $assessment), ['answers' => $this->answersPayload()])
            ->assertRedirect(route('assessments.result', $assessment));

        $assessment->refresh();

        $this->assertSame('completed', $assessment->status);
        $this->assertNotNull($assessment->recommended_program_id);
        $this->assertSame(RiasecQuestion::query()->active()->count(), $assessment->answers()->count());
        $this->assertSame(StudyProgram::query()->active()->count(), $assessment->results()->count());

        $this->get(route('assessments.result', $assessment))
            ->assertOk()
            ->assertSee('Rekomendasi Utama')
            ->assertSee('Profil Kepribadian RIASEC');

        $this->get(route('assessments.calculation', $assessment))
            ->assertOk()
            ->assertSee('Matriks Ternormalisasi');
    }

    public function test_hasil_menjelaskan_kriteria_yang_mengangkat_rekomendasi(): void
    {
        $this->actingAs($this->student);
        $this->post(route('assessments.store'), $this->biodataPayload());
        $assessment = Assessment::query()->latest()->firstOrFail();
        $this->post(route('assessments.answers.store', $assessment), ['answers' => $this->answersPayload()]);

        $response = $this->get(route('assessments.result', $assessment))
            ->assertOk()
            ->assertSee('Mengapa');

        $contributions = $response->viewData('contributions');

        $this->assertCount(Criteria::query()->active()->count(), $contributions);

        // Terurut dari penyumbang terbesar, dan porsinya berjumlah 100%.
        $shares = array_column($contributions, 'contribution');
        $sorted = $shares;
        rsort($sorted);

        $this->assertSame($sorted, $shares);
        $this->assertEqualsWithDelta(100.0, array_sum(array_column($contributions, 'share')), 0.5);
    }

    public function test_pilihan_pertama_yang_kalah_dijelaskan_penyebabnya(): void
    {
        $this->actingAs($this->student);
        $this->post(route('assessments.store'), $this->biodataPayload());
        $assessment = Assessment::query()->latest()->firstOrFail();
        $this->post(route('assessments.answers.store', $assessment), ['answers' => $this->answersPayload()]);

        $response = $this->get(route('assessments.result', $assessment))->assertOk();

        $comparison = $response->viewData('comparison');

        if ($assessment->refresh()->matches_preference) {
            // Rekomendasi sama dengan pilihan pertama, tidak ada yang perlu dibandingkan.
            $this->assertSame([], $comparison);

            return;
        }

        $this->assertNotEmpty($comparison);
        $response->assertSee('tidak menjadi rekomendasi');

        // Terurut dari selisih paling besar secara absolut.
        $deltas = array_map('abs', array_column($comparison, 'delta'));
        $sorted = $deltas;
        rsort($sorted);

        $this->assertSame($sorted, $deltas);
    }

    public function test_prioritas_kurang_dari_batas_minimum_ditolak(): void
    {
        $this->actingAs($this->student);

        $payload = $this->biodataPayload();
        $payload['priorities'] = [$payload['priorities'][0]];

        $this->post(route('assessments.store'), $payload)->assertSessionHasErrors('priorities');
        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_prioritas_ganda_ditolak(): void
    {
        $this->actingAs($this->student);

        $payload = $this->biodataPayload();
        $duplicate = $payload['priorities'][0];
        $payload['priorities'] = [$duplicate, $duplicate, $payload['priorities'][2]];

        $this->post(route('assessments.store'), $payload)->assertSessionHasErrors('priorities.0');
        $this->assertDatabaseCount('assessments', 0);
    }

    public function test_nilai_rapor_di_luar_rentang_ditolak(): void
    {
        $this->actingAs($this->student);

        $payload = $this->biodataPayload();
        $payload['rapor_semesters'][1] = 150;

        $this->post(route('assessments.store'), $payload)->assertSessionHasErrors('rapor_semesters.1');
    }

    public function test_nilai_mapel_pendukung_di_luar_rentang_ditolak(): void
    {
        $this->actingAs($this->student);

        $subjectId = Rapor::supportSubjects()->firstOrFail()->id;

        $payload = $this->biodataPayload();
        $payload['subject_scores'][$subjectId] = 150;

        $this->post(route('assessments.store'), $payload)
            ->assertSessionHasErrors("subject_scores.{$subjectId}");
    }

    public function test_mapel_pendukung_boleh_dikosongkan(): void
    {
        $this->actingAs($this->student);

        $subjectId = Rapor::supportSubjects()->firstOrFail()->id;

        $payload = $this->biodataPayload();
        $payload['subject_scores'][$subjectId] = '';

        $this->post(route('assessments.store'), $payload)->assertSessionHasNoErrors();

        // Barisnya tetap dibuat bernilai null, sebagai catatan bahwa responden
        // memang tidak menempuh mapel tersebut.
        $this->assertDatabaseHas('assessment_subject_scores', [
            'subject_id' => $subjectId,
            'score' => null,
        ]);
    }

    public function test_mapel_pendukung_ditanyakan_setelah_pemilihan_prodi(): void
    {
        $html = $this->actingAs($this->student)
            ->get(route('assessments.create'))
            ->assertOk()
            ->getContent();

        // Responden baru paham kenapa sebuah mapel ditanyakan setelah ia melihat
        // prodi pilihannya, sehingga urutannya tidak boleh terbalik.
        $this->assertLessThan(
            strpos($html, 'Nilai Mata Pelajaran Pendukung'),
            strpos($html, 'Urutan Prioritas Program Studi'),
        );

        // Seluruh mapel pendukung tetap dikirim ke halaman, bukan hanya milik prodi
        // pilihan — tanpa itu prodi lain kehilangan nilai C2.
        foreach (Rapor::supportSubjects() as $subject) {
            $this->assertStringContainsString("subject_scores[{$subject->id}]", $html);
        }

        // Penanda mapel milik prodi pilihan dan penyaringan menurut jenjang
        // sekolah sama-sama dikerjakan di peramban.
        $this->assertStringContainsString('raporForm(', $html);
        $this->assertStringContainsString('matchesLevel(', $html);
    }

    public function test_responden_dapat_menambahkan_mata_pelajaran_miliknya_sendiri(): void
    {
        $this->actingAs($this->student);

        // Mapel konsentrasi keahlian SMK yang belum dipakai prodi mana pun.
        $tambahan = Subject::query()->whereDoesntHave('studyPrograms')->firstOrFail();

        $payload = $this->biodataPayload();
        $payload['subject_scores'][$tambahan->id] = 91;

        $this->post(route('assessments.store'), $payload)->assertSessionHasNoErrors();

        $this->assertDatabaseHas('assessment_subject_scores', [
            'subject_id' => $tambahan->id,
            'score' => 91,
        ]);
    }

    public function test_mata_pelajaran_yang_tidak_dikenali_ditolak(): void
    {
        $this->actingAs($this->student);

        $payload = $this->biodataPayload();
        $payload['subject_scores'][999999] = 90;

        $this->post(route('assessments.store'), $payload)->assertSessionHasErrors('subject_scores');
    }

    public function test_rerata_rapor_dihitung_dari_seluruh_semester(): void
    {
        $this->actingAs($this->student);

        $payload = $this->biodataPayload();
        $payload['rapor_semesters'] = [1 => 70, 2 => 75, 3 => 80, 4 => 85, 5 => 90];

        $this->post(route('assessments.store'), $payload)->assertSessionHasNoErrors();

        $this->assertSame(80.0, (float) Assessment::query()->firstOrFail()->rapor_average);
        $this->assertDatabaseCount('assessment_rapor_semesters', 5);
    }

    public function test_kuesioner_yang_belum_lengkap_ditolak(): void
    {
        $this->actingAs($this->student);
        $this->post(route('assessments.store'), $this->biodataPayload());

        $assessment = Assessment::query()->latest()->firstOrFail();

        $answers = $this->answersPayload();
        array_pop($answers);

        $this->post(route('assessments.answers.store', $assessment), ['answers' => $answers])
            ->assertSessionHasErrors('answers');

        $this->assertSame('questionnaire', $assessment->refresh()->status);
    }

    public function test_skor_likert_di_luar_skala_ditolak(): void
    {
        $this->actingAs($this->student);
        $this->post(route('assessments.store'), $this->biodataPayload());

        $assessment = Assessment::query()->latest()->firstOrFail();

        $this->post(route('assessments.answers.store', $assessment), ['answers' => $this->answersPayload(9)])
            ->assertSessionHasErrors();

        $this->assertSame(0, $assessment->answers()->count());
    }

    public function test_membuka_tes_baru_mengarahkan_ke_sesi_yang_belum_selesai(): void
    {
        $this->actingAs($this->student);
        $this->post(route('assessments.store'), $this->biodataPayload());

        $assessment = Assessment::query()->latest()->firstOrFail();

        $this->get(route('assessments.create'))
            ->assertRedirect(route('assessments.questionnaire', $assessment));

        $this->assertDatabaseCount('assessments', 1);
    }

    public function test_hasil_belum_selesai_dialihkan_ke_kuesioner(): void
    {
        $this->actingAs($this->student);
        $this->post(route('assessments.store'), $this->biodataPayload());

        $assessment = Assessment::query()->latest()->firstOrFail();

        $this->get(route('assessments.result', $assessment))
            ->assertRedirect(route('assessments.questionnaire', $assessment));
    }

    public function test_pengguna_lain_tidak_dapat_membuka_hasil_milik_orang(): void
    {
        $this->actingAs($this->student);
        $this->post(route('assessments.store'), $this->biodataPayload());
        $assessment = Assessment::query()->latest()->firstOrFail();
        $this->post(route('assessments.answers.store', $assessment), ['answers' => $this->answersPayload()]);

        $other = User::factory()->create(['role' => User::ROLE_MAHASISWA]);

        $this->actingAs($other)
            ->get(route('assessments.result', $assessment))
            ->assertForbidden();

        $this->actingAs($other)
            ->post(route('assessments.answers.store', $assessment), ['answers' => $this->answersPayload()])
            ->assertForbidden();
    }

    public function test_admin_boleh_melihat_hasil_milik_calon_mahasiswa(): void
    {
        $this->actingAs($this->student);
        $this->post(route('assessments.store'), $this->biodataPayload());
        $assessment = Assessment::query()->latest()->firstOrFail();
        $this->post(route('assessments.answers.store', $assessment), ['answers' => $this->answersPayload()]);

        $admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();

        $this->actingAs($admin)->get(route('assessments.result', $assessment))->assertOk();
    }

    public function test_riwayat_hanya_menampilkan_tes_milik_sendiri(): void
    {
        $other = User::factory()->create(['role' => User::ROLE_MAHASISWA]);

        $this->actingAs($other);
        $this->post(route('assessments.store'), $this->biodataPayload());
        $otherAssessment = Assessment::query()->latest()->firstOrFail();

        $this->actingAs($this->student)
            ->get(route('assessments.index'))
            ->assertOk()
            ->assertDontSee($otherAssessment->code);
    }

    public function test_menghapus_tes_menghapus_seluruh_data_turunannya(): void
    {
        $this->actingAs($this->student);
        $this->post(route('assessments.store'), $this->biodataPayload());
        $assessment = Assessment::query()->latest()->firstOrFail();
        $this->post(route('assessments.answers.store', $assessment), ['answers' => $this->answersPayload()]);

        $this->delete(route('assessments.destroy', $assessment))->assertRedirect(route('assessments.index'));

        $this->assertDatabaseCount('assessments', 0);
        $this->assertDatabaseCount('assessment_answers', 0);
        $this->assertDatabaseCount('assessment_results', 0);
        $this->assertDatabaseCount('assessment_priorities', 0);
    }

    public function test_beranda_menampilkan_rekomendasi_terakhir(): void
    {
        $this->actingAs($this->student);
        $this->post(route('assessments.store'), $this->biodataPayload());
        $assessment = Assessment::query()->latest()->firstOrFail();
        $this->post(route('assessments.answers.store', $assessment), ['answers' => $this->answersPayload()]);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Rekomendasi terakhir Anda')
            ->assertSee($assessment->refresh()->recommendedProgram->name);
    }
}
