<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_cuti', function (Blueprint $table) {
            $table->id('id_cuti');
            $table->foreignId('id_talent')->constrained('talent', 'id_talent')->cascadeOnDelete();
            $table->foreignId('id_placement')->constrained('placement', 'id_placement')->cascadeOnDelete();
            $table->foreignId('id_approver_klien')->nullable()->constrained('hr_client', 'id_hr_client')->nullOnDelete();
            $table->foreignId('id_approver_manager')->nullable()->constrained('pegawai', 'id_pegawai')->nullOnDelete();
            $table->string('status_approval_klien')->default('menunggu');
            $table->string('status_approval_manager')->default('menunggu');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->text('alasan_cuti');
            $table->string('status_cuti')->default('menunggu');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_cuti');
    }
};
