<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mengganti sumber nilai rapor pada kriteria agar mengikuti SNBP.
     *
     * `subject_score` (satu kriteria per mata pelajaran) digantikan dua sumber:
     * `rapor_average` untuk rerata seluruh mapel dan `support_subject` untuk
     * mapel pendukung prodi.
     *
     * Isi tabel dikosongkan lebih dulu karena makna kodenya berubah total — C1
     * yang semula "Nilai Rapor Matematika" kini "Rerata Rapor Seluruh Mapel".
     * Menyisakan baris lama akan bertabrakan dengan kriteria baru sekaligus
     * menyesatkan admin. `CriteriaSeeder` menyusun ulang seluruh kriteria.
     */
    public function up(): void
    {
        DB::table('criteria')->delete();

        Schema::table('criteria', function (Blueprint $table) {
            $table->enum('source', ['rapor_average', 'support_subject', 'riasec', 'priority', 'tracer'])->change();

            // Mapel pendukung kini melekat pada program studi, bukan pada kriteria.
            $table->dropColumn('subject');
        });
    }

    public function down(): void
    {
        DB::table('criteria')->delete();

        Schema::table('criteria', function (Blueprint $table) {
            $table->enum('source', ['subject_score', 'riasec', 'priority', 'tracer'])->change();
            $table->string('subject', 30)->nullable()->after('source');
        });
    }
};
