<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menambah ambang "praktis seri" untuk tabel peringkat.
 *
 * Pada data nyata sistem ini, setiap sesi memuat 5 sampai 12 pasang prodi
 * bersebelahan yang selisih skornya di bawah 0,5 poin dari 100 — beberapa
 * bahkan 0,003 dan 0,010. Selisih sekecil itu berada di bawah presisi data yang
 * membentuknya, sehingga urutan di antara mereka ditentukan pembulatan, dan
 * ketika benar-benar sama, ditentukan urutan kode prodi.
 *
 * Menampilkannya sebagai peringkat 11 dan 12 tanpa keterangan membuat sistem
 * mengklaim ketelitian yang tidak dimilikinya. Ambang ini dipakai menandai
 * kelompok prodi yang secara praktis setara, bukan mengubah peringkatnya.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => 'tie_threshold'],
            [
                'value' => '0.5',
                'type' => 'float',
                'label' => 'Ambang Peringkat Praktis Seri',
                'description' => 'Selisih maksimum (poin, skala relatif 0-100) agar dua prodi bersebelahan ditandai praktis seri pada tabel peringkat. Bersifat penanda — tidak mengubah urutan peringkat maupun prodi yang direkomendasikan.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        Setting::forgetCache();
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'tie_threshold')->delete();

        Setting::forgetCache();
    }
};
