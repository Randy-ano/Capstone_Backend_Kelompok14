<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_requirement', function (Blueprint $table) {
            $table->id('id_job_req');
            $table->foreignId('id_klien')->constrained('klien', 'id_klien')->restrictOnDelete();
            $table->foreignId('id_am')->nullable()->constrained('pegawai', 'id_pegawai')->nullOnDelete();
            $table->string('posisi');
            $table->unsignedInteger('jumlah_kebutuhan');
            $table->text('kualifikasi')->nullable();
            $table->unsignedInteger('pengalaman_min')->default(0);
            $table->string('status_req')->default('draft');
            $table->dateTime('sla_deadline');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_requirement');
    }
};
