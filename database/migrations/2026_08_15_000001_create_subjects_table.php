<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master mata pelajaran rapor.
     *
     * Menggantikan konstanta `Riasec::SUBJECTS` yang mengunci enam mapel IPA.
     * Daftar mapel harus bisa diubah admin karena kurikulum SMA dan SMK berbeda:
     * kelompok IPA/IPS pada SMA sudah baku, sedangkan mapel produktif SMK
     * berbeda-beda per konsentrasi keahlian dan tidak seragam antar sekolah.
     */
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();
            $table->string('name');

            // Jenjang asal mapel — dipakai untuk mengelompokkan pilihan di form admin,
            // bukan untuk menyaring perhitungan.
            $table->enum('education_level', ['SMA', 'SMK', 'umum'])->default('umum');

            // Kelompok peminatan (IPA/IPS/Umum) atau rumpun keahlian SMK.
            $table->string('group', 60)->nullable();

            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
