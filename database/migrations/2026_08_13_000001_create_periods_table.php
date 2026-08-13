<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gelombang penerimaan mahasiswa baru.
     *
     * Sesi tes ditandai gelombang yang sedang aktif saat tes dibuat, sehingga
     * rekap dan statistik dapat disaring per gelombang alih-alih menumpuk
     * seluruh angkatan dalam satu kumpulan.
     */
    public function up(): void
    {
        Schema::create('periods', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('academic_year', 20);
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->text('description')->nullable();

            // Hanya satu gelombang boleh aktif; penegakannya di PeriodController.
            $table->boolean('is_active')->default(false)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
