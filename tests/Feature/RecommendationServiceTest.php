<?php

namespace Tests\Feature;

use App\Exceptions\CalculationException;
use App\Models\Assessment;
use App\Models\Criteria;
use App\Models\RiasecQuestion;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\RecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    private RecommendationService $recommendation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        Setting::forgetCache();

        $this->recommendation = app(RecommendationService::class);
    }

    /**
     * @param  array<string, int>  $likertBias  skor Likert per dimensi
     * @param  array<string, float>  $scores  nilai rapor, menimpa nilai bawaan
     */
    private function makeAssessment(array $likertBias = [], int $priorityCount = 3, array $scores = []): Assessment
    {
        $user = User::query()->where('role', User::ROLE_MAHASISWA)->firstOrFail();

        $assessment = Assessment::query()->create($scores + [
            'user_id' => $user->id,
            'full_name' => 'Calon Mahasiswa Uji',
            'math_score' => 88,
            'physics_score' => 82,
            'chemistry_score' => 75,
            'biology_score' => 70,
            'indonesian_score' => 85,
            'english_score' => 90,
            'status' => 'questionnaire',
        ]);

        StudyProgram::query()->active()->orderBy('code')->take($priorityCount)->get()
            ->each(fn (StudyProgram $program, int $index) => $assessment->priorities()->create([
                'study_program_id' => $program->id,
                'priority_order' => $index + 1,
            ]));

        $bias = $likertBias + ['R' => 3, 'I' => 5, 'A' => 2, 'S' => 3, 'E' => 3, 'C' => 4];

        RiasecQuestion::query()->active()->ordered()->get()
            ->each(fn (RiasecQuestion $question) => $assessment->answers()->create([
                'riasec_question_id' => $question->id,
                'dimension' => $question->dimension,
                'score' => $bias[$question->dimension],
            ]));

        return $assessment->fresh(['priorities', 'answers']);
    }

    public function test_menyimpan_hasil_untuk_setiap_program_studi_aktif(): void
    {
        $assessment = $this->makeAssessment();

        $this->recommendation->calculate($assessment);

        $expected = StudyProgram::query()->active()->count();

        $this->assertSame($expected, $assessment->results()->count());
        $this->assertDatabaseCount('assessment_results', $expected);
    }

    public function test_peringkat_bersifat_unik_dan_berurutan(): void
    {
        $assessment = $this->makeAssessment();

        $this->recommendation->calculate($assessment);

        $rankings = $assessment->results()->pluck('ranking')->sort()->values()->all();

        $this->assertSame(range(1, count($rankings)), $rankings);
    }

    public function test_profil_riasec_tersimpan_dari_jawaban_kuesioner(): void
    {
        // Jumlah butir per dimensi tidak sama rata (I 7 butir, A 6 butir), namun
        // persentase tetap sebanding karena tiap dimensi dibagi jumlah butirnya
        // sendiri: persen = (jawaban - 1) / 4 x 100 bila seluruh butir dijawab sama.
        // Dimensi I dijawab 5 -> skor 7x5 = 35, persentase 100.
        // Dimensi A dijawab 2 -> skor 6x2 = 12, persentase 25.
        $assessment = $this->makeAssessment();

        $this->recommendation->calculate($assessment);
        $assessment->refresh();

        $itemsI = RiasecQuestion::query()->active()->where('dimension', 'I')->count();
        $itemsA = RiasecQuestion::query()->active()->where('dimension', 'A')->count();

        $this->assertSame($itemsI * 5, $assessment->score_i);
        $this->assertSame(100.0, $assessment->percent_i);
        $this->assertSame($itemsA * 2, $assessment->score_a);
        $this->assertSame(25.0, $assessment->percent_a);

        // Persentase: I 100, C 75, lalu R/S/E sama-sama 50. Nilai seri dipecah
        // mengikuti urutan baku dimensi R,I,A,S,E,C sehingga R yang terpilih.
        $this->assertSame('ICR', $assessment->holland_code);
        $this->assertSame('I', $assessment->dominant_type);
    }

    public function test_status_dan_snapshot_parameter_tersimpan(): void
    {
        $assessment = $this->makeAssessment();

        $this->recommendation->calculate($assessment);
        $assessment->refresh();

        $this->assertSame('completed', $assessment->status);
        $this->assertNotNull($assessment->completed_at);
        $this->assertSame(70.0, $assessment->threshold_used);
        $this->assertSame('normal', $assessment->threshold_mode_used);
        $this->assertSame(0.5, $assessment->lambda_used);

        // Bobot dibaca dari data, bukan dipatok angka, supaya penyetelan ulang
        // bobot oleh admin tidak menjatuhkan tes ini.
        $bobotC7 = (float) Criteria::query()->where('code', 'C7')->value('weight');

        $this->assertCount(9, $assessment->weights_snapshot);
        $this->assertSame($bobotC7, $assessment->weights_snapshot['C7']['weight']);
        $this->assertEqualsWithDelta(1.0, array_sum(array_column($assessment->weights_snapshot, 'weight')), 1e-9);
    }

    public function test_snapshot_bobot_tidak_ikut_berubah_saat_admin_memperbarui_kriteria(): void
    {
        $assessment = $this->makeAssessment();
        $this->recommendation->calculate($assessment);

        $sebelum = (float) Criteria::query()->where('code', 'C7')->value('weight');

        Criteria::query()->where('code', 'C7')->update(['weight' => $sebelum + 0.1]);
        Setting::forgetCache();

        $assessment->refresh();

        $this->assertSame($sebelum, $assessment->weights_snapshot['C7']['weight']);
    }

    public function test_rekomendasi_selalu_prodi_dengan_peringkat_teratas(): void
    {
        $assessment = $this->makeAssessment();
        $calculation = $this->recommendation->calculate($assessment);
        $assessment->refresh();

        $topProgramId = array_search(1, $calculation['ranking'], true);

        $this->assertSame($topProgramId, $assessment->recommended_program_id);
        $this->assertSame(100.0, $assessment->recommended_k_normal);
    }

    public function test_ambang_batas_tidak_lagi_menentukan_prodi_yang_direkomendasikan(): void
    {
        // Ambang batas berapa pun tidak boleh memindahkan rekomendasi: minat calon
        // mahasiswa sudah dihitung sebagai kriteria C8, sehingga memakainya lagi
        // sebagai aturan penimpa berarti menghitung minat dua kali.
        $assessment = $this->makeAssessment();

        Setting::set('threshold', 0);
        Setting::forgetCache();
        $this->recommendation->calculate($assessment->fresh(['priorities', 'answers']));
        $longgar = $assessment->fresh()->recommended_program_id;

        Setting::set('threshold', 100);
        Setting::forgetCache();
        $calculation = $this->recommendation->calculate($assessment->fresh(['priorities', 'answers']));
        $ketat = $assessment->fresh()->recommended_program_id;

        $this->assertSame($longgar, $ketat);
        $this->assertSame(array_search(1, $calculation['ranking'], true), $ketat);
    }

    public function test_matches_preference_menandai_pilihan_pertama_yang_menempati_peringkat_satu(): void
    {
        $assessment = $this->makeAssessment();
        $this->recommendation->calculate($assessment);
        $assessment->refresh();

        $primaryRank = $assessment->results()
            ->where('study_program_id', $assessment->primary_program_id)
            ->value('ranking');

        $this->assertSame($primaryRank === 1, (bool) $assessment->matches_preference);
    }

    public function test_perhitungan_ulang_menimpa_hasil_sebelumnya(): void
    {
        $assessment = $this->makeAssessment();

        $this->recommendation->calculate($assessment);
        $first = $assessment->results()->count();

        $this->recommendation->calculate($assessment);
        $second = $assessment->results()->count();

        $this->assertSame($first, $second);
        $this->assertSame(StudyProgram::query()->active()->count(), $second);
    }

    public function test_mengubah_bobot_kriteria_mengubah_peringkat(): void
    {
        $assessment = $this->makeAssessment();
        $this->recommendation->calculate($assessment);
        $before = $assessment->results()->orderBy('ranking')->pluck('study_program_id')->all();

        // Seluruh bobot dialihkan ke C8 (prioritas minat), sehingga prodi pilihan
        // pertama pasti menempati peringkat teratas.
        Criteria::query()->update(['weight' => 0.0]);
        Criteria::query()->where('code', 'C8')->update(['weight' => 1.0]);
        Setting::forgetCache();

        $this->recommendation->calculate($assessment->fresh(['priorities', 'answers']));
        $assessment->refresh();

        $after = $assessment->results()->orderBy('ranking')->pluck('study_program_id')->all();

        $this->assertNotSame($before, $after);
        $this->assertSame($assessment->primary_program_id, $after[0]);
    }

    public function test_menolak_perhitungan_tanpa_jawaban_kuesioner(): void
    {
        $assessment = $this->makeAssessment();
        $assessment->answers()->delete();

        $this->expectException(CalculationException::class);

        $this->recommendation->calculate($assessment->fresh(['priorities']));
    }

    public function test_menolak_perhitungan_saat_program_studi_aktif_kurang_dari_dua(): void
    {
        $assessment = $this->makeAssessment();

        StudyProgram::query()->update(['is_active' => false]);
        StudyProgram::query()->limit(1)->update(['is_active' => true]);

        $this->expectException(CalculationException::class);

        $this->recommendation->calculate($assessment);
    }

    public function test_menolak_perhitungan_saat_total_bobot_nol(): void
    {
        $assessment = $this->makeAssessment();

        Criteria::query()->update(['weight' => 0.0]);

        $this->expectException(CalculationException::class);

        $this->recommendation->calculate($assessment);
    }

    public function test_matriks_dan_normalisasi_tersimpan_untuk_halaman_detail_perhitungan(): void
    {
        $assessment = $this->makeAssessment();

        $this->recommendation->calculate($assessment);

        $result = $assessment->results()->orderBy('ranking')->first();

        $this->assertCount(9, $result->matrix);
        $this->assertCount(9, $result->normalized);
        $this->assertArrayHasKey('C7', $result->matrix);
        $this->assertGreaterThan(0, $result->normalized['C7']);
    }

    public function test_nilai_rapor_yang_berbeda_menghasilkan_peringkat_yang_berbeda(): void
    {
        // Regresi atas cacat normalisasi: nilai rapor sama untuk seluruh prodi
        // dalam satu sesi, sehingga pada kolom `rapor x relevansi` nilai rapor
        // berlaku sebagai konstanta pengali. Normalisasi min-max berbasis sampel
        // mencoret konstanta itu, membuat calon mahasiswa bernilai 95 di seluruh
        // mapel eksakta memperoleh peringkat yang persis sama dengan yang
        // bernilai 40. Batas normalisasi tetap 0-100 mengembalikan pengaruhnya.
        $eksakta = $this->makeAssessment(scores: [
            'math_score' => 95, 'physics_score' => 95, 'chemistry_score' => 92, 'biology_score' => 90,
            'indonesian_score' => 60, 'english_score' => 60,
        ]);
        $bahasa = $this->makeAssessment(scores: [
            'math_score' => 45, 'physics_score' => 40, 'chemistry_score' => 42, 'biology_score' => 48,
            'indonesian_score' => 95, 'english_score' => 95,
        ]);

        $this->recommendation->calculate($eksakta);
        $this->recommendation->calculate($bahasa);

        $peringkatEksakta = $eksakta->results()->orderBy('ranking')->pluck('study_program_id')->all();
        $peringkatBahasa = $bahasa->results()->orderBy('ranking')->pluck('study_program_id')->all();

        $this->assertNotSame($peringkatEksakta, $peringkatBahasa);
    }

    public function test_nilai_rapor_dikalikan_relevansi_mapel_sehingga_kolom_bervariasi(): void
    {
        $assessment = $this->makeAssessment();

        $this->recommendation->calculate($assessment);

        $c1 = $assessment->results()->get()
            ->map(fn ($result) => $result->matrix['C1'])
            ->unique();

        // Bila nilai rapor dipakai mentah, seluruh baris akan bernilai sama
        // dan normalisasi min-max menjadi pembagian nol.
        $this->assertGreaterThan(1, $c1->count());
    }
}
