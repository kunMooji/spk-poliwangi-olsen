<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mengganti enam kolom nilai mapel tetap dengan rerata rapor SNBP.
     *
     * Kolom lama mengunci enam mata pelajaran IPA untuk semua responden,
     * termasuk peserta didik IPS, Bahasa, dan SMK yang tidak menempuhnya.
     * Rinciannya kini pindah ke `assessment_rapor_semesters` dan
     * `assessment_subject_scores`.
     *
     * `rapor_average` disimpan, bukan dihitung ulang, karena dibaca berkali-kali
     * saat penyusunan matriks dan analisis sensitivitas.
     */
    private const LEGACY_COLUMNS = [
        'math_score',
        'physics_score',
        'chemistry_score',
        'biology_score',
        'indonesian_score',
        'english_score',
    ];

    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->decimal('rapor_average', 5, 2)->default(0)->after('phone');
            $table->dropColumn(self::LEGACY_COLUMNS);
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('rapor_average');

            foreach (self::LEGACY_COLUMNS as $column) {
                $table->decimal($column, 5, 2)->default(0);
            }
        });
    }
};
