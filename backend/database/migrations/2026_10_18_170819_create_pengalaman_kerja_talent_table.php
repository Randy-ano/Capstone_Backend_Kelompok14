<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengalaman_kerja_talent', function (Blueprint $table) {
            $table->id('id_pengalaman_kerja');

            $table->foreignId('id_talent')
                ->constrained('talent', 'id_talent')
                ->cascadeOnDelete();

            $table->string('nama_perusahaan');
            $table->string('posisi');
            $table->string('jenis_pekerjaan', 50)->nullable();
            $table->string('lokasi')->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('masih_bekerja')->default(false);
            $table->text('deskripsi')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengalaman_kerja_talent');
    }
};
