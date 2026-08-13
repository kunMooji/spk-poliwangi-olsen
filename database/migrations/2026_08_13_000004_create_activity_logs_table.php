<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan perubahan data master.
     *
     * Menjawab "siapa mengubah bobot C7, kapan, dari berapa ke berapa" — bobot
     * kriteria dan parameter algoritma memengaruhi rekomendasi seluruh calon
     * mahasiswa sesudahnya, sehingga perubahannya perlu dapat ditelusuri.
     *
     * Pelakunya `nullOnDelete`: menghapus akun admin tidak boleh menghapus jejak
     * perubahan yang pernah dilakukannya.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Disalin agar jejak tetap terbaca setelah akun pelakunya dihapus.
            $table->string('user_name')->nullable();

            $table->string('action', 20);
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();

            // Label ikut disimpan supaya log tetap bermakna meski datanya dihapus.
            $table->string('subject_label')->nullable();

            // { field: { from: ..., to: ... } }
            $table->json('changes')->nullable();

            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
