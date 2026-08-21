<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jenjang sekolah asal responden.
     *
     * Sebelumnya hanya ada `school_major`, satu kolom yang mencampur jurusan SMA
     * (IPA/IPS/Bahasa) dengan jenjang SMK, dan tidak dibaca oleh apa pun. Dengan
     * jenjang berdiri sendiri, `school_major` kembali bermakna tunggal: kelompok
     * peminatan bagi peserta didik SMA, rumpun keahlian bagi peserta didik SMK.
     *
     * Keduanya menentukan mata pelajaran mana yang ditanyakan pada form nilai
     * rapor, sehingga responden tidak diminta mengisi mapel yang tidak pernah
     * ditempuhnya.
     */
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->enum('education_level', ['SMA', 'SMK'])->nullable()->after('school_name');
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('education_level');
        });
    }
};
