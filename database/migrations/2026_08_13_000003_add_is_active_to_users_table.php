<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penonaktifan akun.
     *
     * Akun dinonaktifkan, bukan dihapus, supaya sesi tes yang pernah dikerjakan
     * tetap utuh sebagai arsip — `assessments.user_id` memakai `cascadeOnDelete`.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('role')->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
