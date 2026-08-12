<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jawaban skala Likert 1-5 untuk tiap pernyataan kuesioner RIASEC.
     */
    public function up(): void
    {
        Schema::create('assessment_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('riasec_question_id')->constrained()->cascadeOnDelete();
            $table->char('dimension', 1)->index();
            $table->unsignedTinyInteger('score');
            $table->timestamps();

            $table->unique(['assessment_id', 'riasec_question_id'], 'assessment_answers_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_answers');
    }
};
