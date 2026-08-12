<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kriteria C1..C9 beserta bobot keputusan CoCoSo.
     *
     * `source` + `subject` dipakai DecisionMatrixBuilder untuk menentukan
     * dari mana nilai x_ij diambil, sehingga kriteria bersifat data-driven.
     */
    public function up(): void
    {
        Schema::create('criteria', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('name');
            $table->decimal('weight', 8, 6)->default(0);
            $table->enum('type', ['benefit', 'cost'])->default('benefit');
            $table->enum('source', ['subject_score', 'riasec', 'priority', 'tracer']);
            $table->string('subject', 30)->nullable();
            $table->string('unit', 30)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criteria');
    }
};
