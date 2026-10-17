<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_keluhan', function (Blueprint $table) {
            $table->id('id_laporan');
            $table->foreignId('id_placement')->constrained('placement', 'id_placement')->cascadeOnDelete();
            $table->foreignId('id_hr_client')->nullable()->constrained('hr_client', 'id_hr_client')->nullOnDelete();
            $table->foreignId('id_penindak_lanjut')->nullable()->constrained('pegawai', 'id_pegawai')->nullOnDelete();
            $table->string('judul_laporan');
            $table->text('deskripsi_keluhan');
            $table->dateTime('tanggal_laporan');
            $table->string('status_laporan')->default('baru');
            $table->text('hasil_tindak_lanjut')->nullable();
            $table->dateTime('tanggal_tindak_lanjut')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_keluhan');
    }
};
