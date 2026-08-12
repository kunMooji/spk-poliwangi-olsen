<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riasec_questions', function (Blueprint $table) {
            $table->id();
            $table->text('statement');
            $table->enum('dimension', ['R', 'I', 'A', 'S', 'E', 'C'])->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riasec_questions');
    }
};
