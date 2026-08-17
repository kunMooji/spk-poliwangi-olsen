<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rerata nilai rapor seluruh mata pelajaran per semester (komponen pertama SNBP).
     *
     * Disimpan per semester, bukan sebagai satu angka jadi, supaya rerata dapat
     * ditelusuri dan diaudit — bukan sekadar angka yang diketik responden.
     *
     * Semester terakhir tidak diminta: SNBP memeringkat berdasarkan semua
     * semester kecuali yang terakhir, sehingga hanya semester 1..5 yang relevan.
     */
    public function up(): void
    {
        Schema::create('assessment_rapor_semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('semester');
            $table->decimal('average_score', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['assessment_id', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_rapor_semesters');
    }
};
