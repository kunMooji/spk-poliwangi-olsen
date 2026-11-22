<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel area latihan SKD CPNS (/logintpa). Sengaja terpisah dari tabel
 * users milik SPK agar akun & datanya tidak tercampur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tpa_users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('tpa_tryouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tpa_user_id')->constrained('tpa_users')->cascadeOnDelete();
            $table->string('category', 3);
            $table->unsignedTinyInteger('difficulty');
            $table->string('status', 20)->default('in_progress');
            $table->unsignedSmallInteger('score')->default(0);
            $table->unsignedSmallInteger('max_score')->default(0);
            $table->unsignedTinyInteger('correct_count')->default(0);
            $table->unsignedInteger('total_time_ms')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['tpa_user_id', 'status', 'finished_at']);
        });

        Schema::create('tpa_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tpa_tryout_id')->constrained('tpa_tryouts')->cascadeOnDelete();
            $table->unsignedTinyInteger('number');
            $table->string('sub_topic')->nullable();
            $table->text('question');
            $table->json('options');
            $table->string('correct_key', 1)->nullable();
            $table->text('hint');
            $table->text('explanation');
            $table->string('user_answer', 1)->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedTinyInteger('points')->default(0);
            $table->unsignedInteger('time_spent_ms')->default(0);
            $table->boolean('hint_used')->default(false);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['tpa_tryout_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tpa_questions');
        Schema::dropIfExists('tpa_tryouts');
        Schema::dropIfExists('tpa_users');
    }
};
