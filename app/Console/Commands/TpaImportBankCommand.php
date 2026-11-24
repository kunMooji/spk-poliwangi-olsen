<?php

namespace App\Console\Commands;

use App\Services\Tpa\BankImporter;
use Illuminate\Console\Command;

/**
 * Mengimpor ulang semua file bank soal (database/data/tpa/*.json). Biasanya
 * tidak perlu dijalankan manual: setiap batch sudah punya migrasi sendiri.
 * Berguna setelah mengoreksi isi soal di JSON yang sudah pernah diimpor.
 */
class TpaImportBankCommand extends Command
{
    protected $signature = 'tpa:import-bank';

    protected $description = 'Impor/perbarui bank soal latihan SKD dari database/data/tpa/*.json';

    public function handle(BankImporter $importer): int
    {
        foreach (glob(database_path('data/tpa/*.json')) as $file) {
            $count = $importer->import($file);
            $this->info(basename($file).": {$count} soal");
        }

        return self::SUCCESS;
    }
}
