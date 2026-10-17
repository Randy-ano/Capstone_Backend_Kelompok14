<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('placement', function (Blueprint $table) {
            $table->id('id_placement');
            $table->foreignId('id_evaluasi')->unique()->constrained('evaluasi_kandidat', 'id_evaluasi')->restrictOnDelete();
            $table->foreignId('id_talent')->constrained('talent', 'id_talent')->restrictOnDelete();
            $table->foreignId('id_job_req')->constrained('job_requirement', 'id_job_req')->restrictOnDelete();
            $table->foreignId('id_klien')->constrained('klien', 'id_klien')->restrictOnDelete();
            $table->string('posisi_penempatan');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai')->nullable();
            $table->string('status_placement')->default('aktif');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('placement');
    }
};
