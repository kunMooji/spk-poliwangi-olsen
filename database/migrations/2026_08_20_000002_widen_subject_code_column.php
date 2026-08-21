<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kode mata pelajaran diturunkan dari namanya, sehingga panjangnya mengikuti
     * panjang nama. Batas 40 karakter tidak cukup untuk konsentrasi keahlian SMK
     * seperti "Layanan Penunjang Kefarmasian Klinis dan Komunitas", yang slug-nya
     * saja sudah 49 karakter — baik saat di-seed maupun saat admin menambahkannya
     * sendiri lewat panel.
     */
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('code', 80)->change();
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('code', 40)->change();
        });
    }
};
