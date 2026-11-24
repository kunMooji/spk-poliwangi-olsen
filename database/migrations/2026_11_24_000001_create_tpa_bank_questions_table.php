<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bank soal latihan SKD. Soal disusun di luar website (lewat Claude Code)
 * lalu diimpor dari database/data/tpa/*.json, sehingga tryout bisa dibuat
 * tanpa memanggil Claude API.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpa_bank_questions', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('category', 3);
            $table->string('sub_topic');
            $table->unsignedTinyInteger('level');
            $table->text('question');
            $table->json('options');
            $table->string('correct_key', 1)->nullable();
            $table->text('hint');
            $table->text('explanation');
            $table->timestamps();

            $table->index(['category', 'level']);
        });

        Schema::table('tpa_tryouts', function (Blueprint $table) {
            $table->string('source', 10)->default('ai')->after('difficulty');
        });

        Schema::table('tpa_questions', function (Blueprint $table) {
            $table->foreignId('tpa_bank_question_id')->nullable()->after('tpa_tryout_id')
                ->constrained('tpa_bank_questions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('tpa_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tpa_bank_question_id');
        });

        Schema::table('tpa_tryouts', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::dropIfExists('tpa_bank_questions');
    }
};
