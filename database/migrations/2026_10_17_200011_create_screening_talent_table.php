<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screening_talent', function (Blueprint $table) {
            $table->id('id_screening');
            $table->foreignId('id_talent')->constrained('talent', 'id_talent')->cascadeOnDelete();
            $table->foreignId('id_job_req')->constrained('job_requirement', 'id_job_req')->cascadeOnDelete();
            $table->foreignId('id_ts')->nullable()->constrained('pegawai', 'id_pegawai')->nullOnDelete();
            $table->decimal('skor_kecocokan', 5, 2)->nullable();
            $table->dateTime('tanggal_screening');
            $table->text('catatan_screening')->nullable();
            $table->string('hasil_screening')->default('pending');
            $table->timestamps();

            $table->unique(['id_talent', 'id_job_req']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screening_talent');
    }
};
