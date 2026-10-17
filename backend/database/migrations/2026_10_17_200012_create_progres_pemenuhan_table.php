<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('progres_pemenuhan', function (Blueprint $table) {
            $table->id('id_progres');
            $table->foreignId('id_job_req')->constrained('job_requirement', 'id_job_req')->cascadeOnDelete();
            $table->foreignId('id_talent')->nullable()->constrained('talent', 'id_talent')->cascadeOnDelete();
            $table->foreignId('id_penanggung_jawab')->nullable()->constrained('pegawai', 'id_pegawai')->nullOnDelete();
            $table->string('status_tahapan');
            $table->string('indikator_sla');
            $table->dateTime('log_waktu_pembaruan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('progres_pemenuhan');
    }
};
