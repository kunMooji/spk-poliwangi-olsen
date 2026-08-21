<?php

namespace Tests\Feature;

use App\Exceptions\CalculationException;
use App\Models\Assessment;
use App\Models\Criteria;
use App\Models\RiasecQuestion;
use App\Models\Setting;
use App\Models\StudyProgram;
use App\Models\Subject;
use App\Models\User;
use App\Services\RecommendationService;
use App\Support\Rapor;
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
     * @param  array<string, float>  $subjectScores  nilai mapel pendukung per kode mapel
     */
    private function makeAssessment(
        array $likertBias = [],
        int $priorityCount = 3,
        array $subjectScores = [],
        float $raporAverage = 82.0,
    ): Assessment {
        $user = User::query()->where('role', User::ROLE_MAHASISWA)->firstOrFail();

        $assessment = Assessment::query()->create([
            'user_id' => $user->id,
            'full_name' => 'Calon Mahasiswa Uji',
            'rapor_average' => $raporAverage,
            'status' => 'questionnaire',
        ]);

        foreach (Rapor::SEMESTERS as $semester) {
            $assessment->raporSemesters()->create([
                'semester' => $semester,
                'average_score' => $raporAverage,
            ]);
        }

        // Mapel yang tidak disebut pemanggil diberi nilai sama dengan rerata,
        // sehingga hanya mapel yang sengaja diatur uji yang menggerakkan C2.
        foreach (Rapor::supportSubjects() as $subject) {
            $assessment->subjectScores()->create([
                'subject_id' => $subject->id,
                'score' => $subjectScores[$subject->code] ?? $raporAverage,
            ]);
        }

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

        return $assessment->fresh(['priorities', 'answers', 'subjectScores']);
    }

    /**
     * Menjadikan satu prodi sebagai satu-satunya prioritas — dipakai saat uji
     * perlu memastikan C2 dihitung lewat jalur prioritas (nilai asli), bukan
     * jalur non-prioritas (nilai semester terendah).
     */
    private function setPriority(Assessment $assessment, StudyProgram $program): void
    {
        $assessment->priorities()->delete();
        $assessment->priorities()->create([
            'study_program_id' => $program->id,
            'priority_order' => 1,
        ]);
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
        $bobotC3 = (float) Criteria::query()->where('code', 'C3')->value('weight');

        $this->assertCount(5, $assessment->weights_snapshot);
        $this->assertSame($bobotC3, $assessment->weights_snapshot['C3']['weight']);
        $this->assertEqualsWithDelta(1.0, array_sum(array_column($assessment->weights_snapshot, 'weight')), 1e-9);
    }

    public function test_snapshot_bobot_tidak_ikut_berubah_saat_admin_memperbarui_kriteria(): void
    {
        $assessment = $this->makeAssessment();
        $this->recommendation->calculate($assessment);

        $sebelum = (float) Criteria::query()->where('code', 'C3')->value('weight');

        Criteria::query()->where('code', 'C3')->update(['weight' => $sebelum + 0.1]);
        Setting::forgetCache();

        $assessment->refresh();

        $this->assertSame($sebelum, $assessment->weights_snapshot['C3']['weight']);
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
        // mahasiswa sudah dihitung sebagai kriteria C4, sehingga memakainya lagi
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

        // Seluruh bobot dialihkan ke C4 (prioritas minat), sehingga prodi pilihan
        // pertama pasti menempati peringkat teratas.
        Criteria::query()->update(['weight' => 0.0]);
        Criteria::query()->where('code', 'C4')->update(['weight' => 1.0]);
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

        $this->assertCount(5, $result->matrix);
        $this->assertCount(5, $result->normalized);
        $this->assertArrayHasKey('C3', $result->matrix);
        $this->assertGreaterThan(0, $result->normalized['C3']);
    }

    public function test_nilai_mapel_pendukung_yang_berbeda_menghasilkan_peringkat_yang_berbeda(): void
    {
        // Mapel pendukung adalah satu-satunya kriteria nilai rapor yang berbeda
        // antar prodi, sehingga di sinilah profil akademik responden benar-benar
        // memengaruhi urutan rekomendasi.
        $eksakta = $this->makeAssessment(subjectScores: [
            'matematika' => 95, 'fisika' => 95, 'kimia' => 92, 'informatika' => 93,
            'ekonomi-akuntansi' => 55, 'geografi' => 55, 'bahasa-inggris' => 60, 'biologi' => 60,
        ]);
        $sosial = $this->makeAssessment(subjectScores: [
            'matematika' => 50, 'fisika' => 45, 'kimia' => 45, 'informatika' => 50,
            'ekonomi-akuntansi' => 95, 'geografi' => 95, 'bahasa-inggris' => 93, 'biologi' => 88,
        ]);

        $this->recommendation->calculate($eksakta);
        $this->recommendation->calculate($sosial);

        $peringkatEksakta = $eksakta->results()->orderBy('ranking')->pluck('study_program_id')->all();
        $peringkatSosial = $sosial->results()->orderBy('ranking')->pluck('study_program_id')->all();

        $this->assertNotSame($peringkatEksakta, $peringkatSosial);
    }

    public function test_kolom_mapel_pendukung_bervariasi_antar_prodi(): void
    {
        $assessment = $this->makeAssessment(subjectScores: [
            'matematika' => 95, 'fisika' => 60, 'ekonomi-akuntansi' => 75,
        ]);

        $this->recommendation->calculate($assessment);

        $c2 = $assessment->results()->get()
            ->map(fn ($result) => $result->matrix['C2'])
            ->unique();

        // Prodi dengan mapel pendukung berbeda harus memperoleh nilai C2 berbeda;
        // bila seragam, kriteria ini tidak membedakan apa pun.
        $this->assertGreaterThan(1, $c2->count());
    }

    public function test_rerata_rapor_konstan_tetap_ternormalisasi_pada_skala_aslinya(): void
    {
        // C1 bernilai sama untuk seluruh prodi sehingga `max - min` pada sampel
        // bernilai nol. Tanpa batas normalisasi tetap, CocosoService memberi 1.0
        // kepada semua alternatif dan rapor 90 tak terbedakan dari rapor 55.
        $tinggi = $this->makeAssessment(raporAverage: 90.0);
        $rendah = $this->makeAssessment(raporAverage: 55.0);

        $this->recommendation->calculate($tinggi);
        $this->recommendation->calculate($rendah);

        $c1Tinggi = $tinggi->results()->get()->map(fn ($result) => $result->normalized['C1']);
        $c1Rendah = $rendah->results()->get()->map(fn ($result) => $result->normalized['C1']);

        $this->assertSame(1, $c1Tinggi->unique()->count(), 'C1 memang konstan dalam satu sesi.');
        $this->assertEqualsWithDelta(0.90, $c1Tinggi->first(), 0.0001);
        $this->assertEqualsWithDelta(0.55, $c1Rendah->first(), 0.0001);
    }

    public function test_mapel_yang_tidak_ditempuh_memakai_rerata_rapor(): void
    {
        $mesin = StudyProgram::query()->where('code', 'TRM-D4')->firstOrFail();

        $assessment = $this->makeAssessment(raporAverage: 70.0);
        $this->setPriority($assessment, $mesin);

        // Peserta didik IPS yang tidak menempuh Fisika: nilainya kosong, bukan nol.
        $fisika = Subject::query()->where('code', 'fisika')->firstOrFail();
        $assessment->subjectScores()->where('subject_id', $fisika->id)->update(['score' => null]);

        $this->recommendation->calculate($assessment->fresh(['priorities', 'answers', 'subjectScores']));

        // Teknologi Rekayasa Manufaktur memakai Matematika + Fisika. Matematika
        // bernilai rerata dan Fisika jatuh ke rerata pula, sehingga C2-nya tepat
        // sama dengan rerata.
        $result = $assessment->results()->where('study_program_id', $mesin->id)->firstOrFail();

        $this->assertEqualsWithDelta(70.0, $result->matrix['C2'], 0.0001);
    }

    public function test_mapel_pendukung_disaring_menurut_jenjang_responden(): void
    {
        // Teknologi Rekayasa Perangkat Lunak menautkan tiga mapel sekaligus:
        // Matematika (umum), Informatika (SMA), dan Rekayasa Perangkat Lunak
        // (SMK · Teknologi Informasi). Yang berlaku bagi satu responden harus
        // tetap dua — sesuai batas SNBP — dan dipilih menurut asal sekolahnya.
        $trpl = StudyProgram::query()->where('code', 'TRPL-D4')->firstOrFail();

        $kodeNilai = [
            'matematika' => 90.0,
            'informatika' => 60.0,
            'rekayasa-perangkat-lunak' => 50.0,
        ];

        // Harus jadi prioritas: C2 non-prioritas kini memakai nilai semester
        // terendah, bukan nilai mapel — subjek uji ini justru diuji lewat
        // jalur prioritas.
        $smk = $this->makeAssessment(raporAverage: 70.0, subjectScores: $kodeNilai);
        $smk->update(['education_level' => 'SMK', 'school_major' => 'Teknologi Informasi']);
        $this->setPriority($smk, $trpl);
        $this->recommendation->calculate($smk->fresh(['priorities', 'answers', 'subjectScores']));

        $sma = $this->makeAssessment(raporAverage: 70.0, subjectScores: $kodeNilai);
        $sma->update(['education_level' => 'SMA', 'school_major' => 'Umum']);
        $this->setPriority($sma, $trpl);
        $this->recommendation->calculate($sma->fresh(['priorities', 'answers', 'subjectScores']));

        $c2Smk = $smk->results()->where('study_program_id', $trpl->id)->firstOrFail()->matrix['C2'];
        $c2Sma = $sma->results()->where('study_program_id', $trpl->id)->firstOrFail()->matrix['C2'];

        // SMK: Matematika + Rekayasa Perangkat Lunak — Informatika tidak ditempuh.
        $this->assertEqualsWithDelta((90.0 + 50.0) / 2, $c2Smk, 0.0001);

        // SMA: Matematika + Informatika — mapel konsentrasi keahlian diabaikan.
        $this->assertEqualsWithDelta((90.0 + 60.0) / 2, $c2Sma, 0.0001);
    }

    public function test_prodi_tanpa_mapel_pendukung_yang_cocok_memakai_rerata_rapor(): void
    {
        // Teknologi Rekayasa Otomotif tidak menautkan satu pun mapel Pariwisata,
        // tapi tetap sebagai prioritas: Matematika (berjenjang umum) berlaku
        // untuk siapa pun, sehingga C2-nya tetap dari nilai asli, bukan rerata.
        $otomotif = StudyProgram::query()->where('code', 'TRO-D4')->firstOrFail();

        $assessment = $this->makeAssessment(raporAverage: 73.0, subjectScores: ['matematika' => 95.0]);
        $assessment->update(['education_level' => 'SMK', 'school_major' => 'Pariwisata']);
        $this->setPriority($assessment, $otomotif);

        $this->recommendation->calculate($assessment->fresh(['priorities', 'answers', 'subjectScores']));

        // Matematika tetap berlaku karena berjenjang umum, jadi C2 mengikutinya.
        $result = $assessment->results()->where('study_program_id', $otomotif->id)->firstOrFail();
        $this->assertEqualsWithDelta(95.0, $result->matrix['C2'], 0.0001);
    }

    public function test_prodi_prioritas_tanpa_mapel_pendukung_memakai_rerata_rapor(): void
    {
        $program = StudyProgram::query()->where('code', 'TRPL-D4')->firstOrFail();
        $program->supportSubjects()->detach();

        $assessment = $this->makeAssessment(raporAverage: 77.0);
        $this->setPriority($assessment, $program);
        // Semester terendah dibuat sengaja berbeda dari rerata, supaya tes ini
        // benar-benar menguji jalur prioritas (rerata) dan bukan kebetulan sama
        // dengan jalur non-prioritas (semester terendah).
        $assessment->raporSemesters()->where('semester', 1)->update(['average_score' => 40.0]);

        $this->recommendation->calculate($assessment->fresh(['priorities', 'answers', 'subjectScores', 'raporSemesters']));

        $result = $assessment->results()->where('study_program_id', $program->id)->firstOrFail();

        $this->assertEqualsWithDelta(77.0, $result->matrix['C2'], 0.0001);
    }

    public function test_prodi_non_prioritas_memakai_nilai_semester_terendah(): void
    {
        // Prodi yang tidak dijadikan prioritas tidak pernah ditanyakan mapel
        // pendukungnya, sehingga C2-nya diganti nilai semester terendah —
        // bukan rerata — supaya prodi prioritas tidak kalah bersaing hanya
        // karena lemah di satu mapel spesifik.
        $program = StudyProgram::query()->where('code', 'TRPL-D4')->firstOrFail();

        $assessment = $this->makeAssessment(raporAverage: 85.0);

        // Prasyarat tes: TRPL-D4 memang bukan salah satu prioritas bawaan
        // makeAssessment() (tiga prodi pertama menurut kode).
        $this->assertFalse($assessment->priorities->contains('study_program_id', $program->id));

        $assessment->raporSemesters()->where('semester', 1)->update(['average_score' => 55.0]);

        $this->recommendation->calculate($assessment->fresh(['priorities', 'answers', 'subjectScores', 'raporSemesters']));

        $result = $assessment->results()->where('study_program_id', $program->id)->firstOrFail();

        // Semester terendah (55.0), bukan rerata (85.0).
        $this->assertEqualsWithDelta(55.0, $result->matrix['C2'], 0.0001);
    }
}
