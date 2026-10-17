<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent', function (Blueprint $table) {
            $table->id('id_talent');
            $table->foreignId('id_user')->unique()->constrained('users', 'id_user')->cascadeOnDelete();
            $table->string('nama_lengkap');
            $table->string('pendidikan')->nullable();
            $table->text('pengalaman_kerja')->nullable();
            $table->string('file_cv')->nullable();
            $table->string('status_kelengkapan')->default('belum_lengkap');
            $table->string('status_kandidat')->default('tersedia');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent');
    }
};
