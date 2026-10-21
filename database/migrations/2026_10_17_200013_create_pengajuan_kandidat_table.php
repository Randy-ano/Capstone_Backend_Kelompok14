<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_kandidat', function (Blueprint $table) {
            $table->id('id_pengajuan');
            $table->foreignId('id_screening')->unique()->constrained('screening_talent', 'id_screening')->cascadeOnDelete();
            $table->foreignId('id_talent')->constrained('talent', 'id_talent')->cascadeOnDelete();
            $table->foreignId('id_job_req')->constrained('job_requirement', 'id_job_req')->cascadeOnDelete();
            $table->foreignId('id_am')->nullable()->constrained('pegawai', 'id_pegawai')->nullOnDelete();
            $table->dateTime('tanggal_pengajuan');
            $table->string('status_pengajuan')->default('diajukan');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_kandidat');
    }
};
