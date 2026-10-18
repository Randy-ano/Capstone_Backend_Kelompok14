<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_pendidikan', function (Blueprint $table) {
            $table->id('id_riwayat_pendidikan');

            $table->foreignId('id_talent')
                ->constrained('talent', 'id_talent')
                ->cascadeOnDelete();

            $table->string('jenjang', 50);
            $table->string('nama_institusi');
            $table->string('jurusan')->nullable();
            $table->unsignedSmallInteger('tahun_mulai')->nullable();
            $table->unsignedSmallInteger('tahun_selesai')->nullable();
            $table->text('deskripsi')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_pendidikan');
    }
};
