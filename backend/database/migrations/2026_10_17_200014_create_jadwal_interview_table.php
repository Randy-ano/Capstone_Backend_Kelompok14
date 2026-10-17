<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jadwal_interview', function (Blueprint $table) {
            $table->id('id_interview');
            $table->foreignId('id_pengajuan')->constrained('pengajuan_kandidat', 'id_pengajuan')->cascadeOnDelete();
            $table->date('tanggal_interview');
            $table->time('waktu_interview');
            $table->string('link_lokasi_meeting')->nullable();
            $table->text('instruksi')->nullable();
            $table->string('status_interview')->default('dijadwalkan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jadwal_interview');
    }
};
