<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_kompetensi', function (Blueprint $table) {
            $table->foreignId('id_talent')->constrained('talent', 'id_talent')->cascadeOnDelete();
            $table->foreignId('id_kompetensi')->constrained('kompetensi', 'id_kompetensi')->cascadeOnDelete();
            $table->primary(['id_talent', 'id_kompetensi']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_kompetensi');
    }
};
