<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menghapus bobot relevansi mata pelajaran.
     *
     * Relevansi kontinu 0..1 untuk enam mapel tetap adalah konsep buatan sistem
     * ini sendiri; SNBP tidak mengenalnya. Penggantinya adalah daftar mapel
     * pendukung yang diskret dan paling banyak dua per prodi, tersimpan di
     * `study_program_subjects`.
     */
    private const COLUMNS = [
        'math_relevance',
        'physics_relevance',
        'chemistry_relevance',
        'biology_relevance',
        'indonesian_relevance',
        'english_relevance',
    ];

    public function up(): void
    {
        Schema::table('study_programs', function (Blueprint $table) {
            $table->dropColumn(self::COLUMNS);
        });
    }

    public function down(): void
    {
        Schema::table('study_programs', function (Blueprint $table) {
            foreach (self::COLUMNS as $column) {
                $table->decimal($column, 3, 2)->default(0.50);
            }
        });
    }
};
