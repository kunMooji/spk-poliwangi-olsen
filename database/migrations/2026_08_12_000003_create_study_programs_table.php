<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alternatif keputusan pada perhitungan CoCoSo.
     */
    public function up(): void
    {
        Schema::create('study_programs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->enum('level', ['D2', 'D3', 'D4', 'S1'])->default('D4');
            $table->string('department')->nullable();
            $table->text('description')->nullable();

            // Bobot relevansi mata pelajaran (0.00 - 1.00) -> pengali nilai rapor (C1..C6)
            $table->decimal('math_relevance', 3, 2)->default(0.50);
            $table->decimal('physics_relevance', 3, 2)->default(0.50);
            $table->decimal('chemistry_relevance', 3, 2)->default(0.50);
            $table->decimal('biology_relevance', 3, 2)->default(0.50);
            $table->decimal('indonesian_relevance', 3, 2)->default(0.50);
            $table->decimal('english_relevance', 3, 2)->default(0.50);

            // Profil kepribadian RIASEC prodi (0 - 100) -> pembanding cosine similarity (C7)
            $table->unsignedTinyInteger('riasec_r')->default(0);
            $table->unsignedTinyInteger('riasec_i')->default(0);
            $table->unsignedTinyInteger('riasec_a')->default(0);
            $table->unsignedTinyInteger('riasec_s')->default(0);
            $table->unsignedTinyInteger('riasec_e')->default(0);
            $table->unsignedTinyInteger('riasec_c')->default(0);

            // Tracer study / prospek kerja (C9)
            $table->decimal('employment_rate', 4, 3)->default(0.000);
            $table->unsignedInteger('alumni_count')->default(0);
            $table->unsignedInteger('employed_count')->default(0);
            $table->unsignedSmallInteger('tracer_year')->nullable();
            $table->timestamp('tracer_updated_at')->nullable();

            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_programs');
    }
};
