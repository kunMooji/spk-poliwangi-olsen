<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mata pelajaran pendukung tiap program studi (komponen kedua SNBP).
     *
     * SNBP membatasi paling banyak dua mapel pendukung per prodi. Batas itu
     * ditegakkan di `StudyProgramRequest`, bukan di skema, supaya PTN yang
     * kebijakannya berbeda tidak perlu mengubah struktur tabel.
     *
     * Inilah satu-satunya sumber perbedaan nilai rapor antar alternatif:
     * komponen pertama (rerata seluruh mapel) bernilai sama untuk semua prodi.
     */
    public function up(): void
    {
        Schema::create('study_program_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('study_program_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position')->default(1);
            $table->timestamps();

            $table->unique(['study_program_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_program_subjects');
    }
};
