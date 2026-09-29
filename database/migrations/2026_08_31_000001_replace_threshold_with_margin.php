<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Mengganti "ambang batas kelayakan" dengan "ambang margin rekomendasi".
 *
 * Alasannya matematis. `k_normal` diskalakan terhadap K tertinggi, sehingga
 * prodi peringkat 1 selalu bernilai tepat 100 — ambang batas berapa pun tidak
 * pernah mengikat pada prodi yang direkomendasikan, dan `recommended_k_normal`
 * tidak membawa informasi apa pun ketika dibandingkan antar sesi.
 *
 * K sendiri memang tidak punya skala absolut: K_ia, K_ib, dan K_ic ketiganya
 * didefinisikan relatif terhadap alternatif lain pada sesi yang sama, jadi
 * "skor kelayakan 0-100" tidak bisa diturunkan darinya tanpa mengarang angka.
 * Yang benar-benar bermakna dan bisa dibandingkan antar sesi adalah *jarak*
 * peringkat 1 terhadap peringkat 2 pada skala relatif itu: margin lebar berarti
 * rekomendasi meyakinkan, margin tipis berarti kedua prodi praktis seri dan
 * calon mahasiswa sebaiknya menimbang keduanya.
 *
 * `threshold_mode` ikut dihapus karena tidak pernah dipakai membandingkan apa
 * pun sejak rekomendasi mengikuti peringkat CoCoSo apa adanya.
 */
return new class extends Migration
{
    /** Ambang margin bawaan, dipakai juga untuk mengisi sesi yang sudah ada. */
    private const MARGIN_THRESHOLD = 10.0;

    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->renameColumn('threshold_used', 'margin_threshold_used');
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('threshold_mode_used');
            $table->decimal('margin_to_runner_up', 8, 4)->nullable()->after('recommended_k_normal');
        });

        // Sesi lama menyimpan ambang kelayakan (bawaan 70) pada kolom yang kini
        // bermakna ambang margin. Kedua angka itu tidak sebanding, jadi diganti
        // ambang margin yang berlaku sekarang alih-alih dibiarkan menyesatkan.
        DB::table('assessments')->update(['margin_threshold_used' => self::MARGIN_THRESHOLD]);

        $this->backfillMargins();

        DB::table('settings')->where('key', 'threshold_mode')->delete();

        DB::table('settings')->where('key', 'threshold')->update([
            'key' => 'margin_threshold',
            'value' => '10',
            'type' => 'float',
            'label' => 'Ambang Margin Rekomendasi Meyakinkan',
            'description' => 'Selisih minimum (dalam poin skala relatif 0-100) antara prodi peringkat 1 dan peringkat 2 agar rekomendasi ditandai meyakinkan. Di bawah nilai ini, hasil ditandai tipis dan calon mahasiswa diarahkan menimbang kedua prodi.',
            'updated_at' => now(),
        ]);

        Setting::forgetCache();
    }

    /**
     * Isi margin sesi yang sudah selesai dari hasil yang tersimpan.
     *
     * Tidak perlu menghitung ulang CoCoSo: `assessment_results.k_normal` sudah
     * merekam skor relatif tiap prodi, dan peringkat 1 selalu bernilai 100,
     * sehingga marginnya cukup diambil dari selisih terhadap peringkat 2.
     */
    private function backfillMargins(): void
    {
        $runnerUps = DB::table('assessment_results')
            ->where('ranking', 2)
            ->pluck('k_normal', 'assessment_id');

        foreach ($runnerUps as $assessmentId => $runnerUpScore) {
            DB::table('assessments')
                ->where('id', $assessmentId)
                ->update(['margin_to_runner_up' => 100 - (float) $runnerUpScore]);
        }
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('margin_to_runner_up');
            $table->string('threshold_mode_used', 20)->nullable();
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->renameColumn('margin_threshold_used', 'threshold_used');
        });

        DB::table('settings')->where('key', 'margin_threshold')->update([
            'key' => 'threshold',
            'value' => '70',
            'type' => 'float',
            'label' => 'Ambang Batas Kelayakan',
            'description' => 'Nilai minimum agar sebuah prodi ditandai layak dipertimbangkan.',
            'updated_at' => now(),
        ]);

        DB::table('settings')->insert([
            'key' => 'threshold_mode',
            'value' => 'normal',
            'type' => 'string',
            'label' => 'Mode Pembanding Ambang Batas',
            'description' => '"normal" membandingkan terhadap K ternormalisasi (0-100), "raw" membandingkan terhadap nilai K asli.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Setting::forgetCache();
    }
};
