<?php

use App\Services\Tpa\BankImporter;
use Illuminate\Database\Migrations\Migration;

/**
 * Batch soal bank. Setiap batch baru = file JSON baru + migrasi seperti ini,
 * sehingga ./deploy.sh (migrate --force) otomatis memasukkannya ke server.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(BankImporter::class)->import(database_path('data/tpa/tiu-batch-02.json'));
    }

    public function down(): void
    {
        // Soal bank sengaja dibiarkan: riwayat tryout bisa merujuk ke sana.
    }
};
