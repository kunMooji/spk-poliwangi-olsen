<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jejak perhitungan CoCoSo per alternatif prodi.
     *
     * Kolom `matrix` dan `normalized` menyimpan nilai per kriteria dalam JSON
     * (dikunci dengan kode kriteria) supaya tahapan perhitungan bisa
     * ditampilkan ulang tanpa menghitung ulang.
     */
    public function up(): void
    {
        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_program_id')->constrained()->cascadeOnDelete();

            $table->json('matrix');      // x_ij mentah per kode kriteria
            $table->json('normalized');  // r_ij ternormalisasi per kode kriteria

            $table->decimal('s_value', 18, 8)->default(0);   // S_i  (weighted sum)
            $table->decimal('p_value', 18, 8)->default(0);   // P_i  (weighted product)
            $table->decimal('k_a', 18, 8)->default(0);       // K_ia
            $table->decimal('k_b', 18, 8)->default(0);       // K_ib
            $table->decimal('k_c', 18, 8)->default(0);       // K_ic
            $table->decimal('k_value', 18, 8)->default(0);   // K_i  (nilai akhir)
            $table->decimal('k_normal', 8, 4)->default(0);   // K_i / max(K_i) * 100

            $table->unsignedSmallInteger('ranking')->default(0);
            $table->timestamps();

            $table->unique(['assessment_id', 'study_program_id'], 'assessment_results_unique');
            $table->index(['assessment_id', 'ranking']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
    }
};
