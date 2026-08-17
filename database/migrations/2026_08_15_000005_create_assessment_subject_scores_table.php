<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nilai rapor pada mata pelajaran pendukung (komponen kedua SNBP).
     *
     * Yang diminta adalah gabungan mapel pendukung seluruh prodi aktif, bukan
     * hanya prodi pilihan responden. CoCoSo memeringkat semua alternatif
     * sekaligus, sehingga prodi di luar daftar prioritas pun harus punya nilai —
     * justru prodi itulah yang berpotensi jadi rekomendasi baru.
     *
     * `score` boleh null: mapel tersebut tidak ada di rapor responden, misalnya
     * peserta didik IPS yang tidak menempuh Fisika. Nilai null jatuh ke rerata
     * rapor umum saat penyusunan matriks, bukan ke nol, supaya prodi terkait
     * tidak otomatis gugur.
     */
    public function up(): void
    {
        Schema::create('assessment_subject_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->decimal('score', 5, 2)->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_subject_scores');
    }
};
