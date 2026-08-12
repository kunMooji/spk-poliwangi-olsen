<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Urutan prioritas prodi pilihan calon mahasiswa (dasar perhitungan C8).
     */
    public function up(): void
    {
        Schema::create('assessment_priorities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_program_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('priority_order');
            $table->timestamps();

            $table->unique(['assessment_id', 'study_program_id'], 'assessment_priorities_unique_program');
            $table->unique(['assessment_id', 'priority_order'], 'assessment_priorities_unique_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_priorities');
    }
};
