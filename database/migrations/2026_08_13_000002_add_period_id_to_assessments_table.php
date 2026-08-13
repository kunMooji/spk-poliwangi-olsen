<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penanda gelombang pada sesi tes.
     *
     * Dibuat nullable dan `nullOnDelete` karena sesi tes yang sudah selesai
     * tetap harus terbaca meski gelombangnya dihapus — arsip hasil tidak boleh
     * ikut hilang hanya karena penataan gelombang berubah.
     */
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->foreignId('period_id')
                ->nullable()
                ->after('user_id')
                ->constrained('periods')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropForeign(['period_id']);
            $table->dropColumn('period_id');
        });
    }
};
