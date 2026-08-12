<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Satu sesi tes milik seorang calon mahasiswa.
     *
     * Parameter algoritma (bobot, threshold, lambda) di-snapshot ke baris ini
     * saat perhitungan dijalankan, agar hasil lama tidak ikut berubah ketika
     * admin memperbarui bobot atau threshold di kemudian hari.
     */
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20)->unique();

            // Biodata
            $table->string('full_name');
            $table->enum('gender', ['L', 'P'])->nullable();
            $table->string('school_name')->nullable();
            $table->string('school_major', 50)->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->string('phone', 25)->nullable();

            // Nilai rapor 0-100 (C1..C6)
            $table->decimal('math_score', 5, 2)->default(0);
            $table->decimal('physics_score', 5, 2)->default(0);
            $table->decimal('chemistry_score', 5, 2)->default(0);
            $table->decimal('biology_score', 5, 2)->default(0);
            $table->decimal('indonesian_score', 5, 2)->default(0);
            $table->decimal('english_score', 5, 2)->default(0);

            // Akumulasi skala Likert per dimensi RIASEC
            $table->unsignedSmallInteger('score_r')->default(0);
            $table->unsignedSmallInteger('score_i')->default(0);
            $table->unsignedSmallInteger('score_a')->default(0);
            $table->unsignedSmallInteger('score_s')->default(0);
            $table->unsignedSmallInteger('score_e')->default(0);
            $table->unsignedSmallInteger('score_c')->default(0);

            // Skor RIASEC dikonversi ke persentase 0-100
            $table->decimal('percent_r', 5, 2)->default(0);
            $table->decimal('percent_i', 5, 2)->default(0);
            $table->decimal('percent_a', 5, 2)->default(0);
            $table->decimal('percent_s', 5, 2)->default(0);
            $table->decimal('percent_e', 5, 2)->default(0);
            $table->decimal('percent_c', 5, 2)->default(0);

            $table->char('holland_code', 3)->nullable();
            $table->char('dominant_type', 1)->nullable();

            // Hasil rekomendasi
            $table->foreignId('primary_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->foreignId('recommended_program_id')->nullable()->constrained('study_programs')->nullOnDelete();
            $table->decimal('recommended_k_value', 18, 8)->nullable();
            $table->decimal('recommended_k_normal', 8, 4)->nullable();
            $table->boolean('matches_preference')->nullable();

            // Snapshot parameter algoritma saat perhitungan dijalankan
            $table->decimal('threshold_used', 8, 4)->nullable();
            $table->string('threshold_mode_used', 20)->nullable();
            $table->decimal('lambda_used', 4, 3)->nullable();
            $table->json('weights_snapshot')->nullable();

            $table->enum('status', ['draft', 'questionnaire', 'completed'])->default('draft')->index();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
