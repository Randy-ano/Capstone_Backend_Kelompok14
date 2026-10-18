<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monitoring_talent', function (Blueprint $table) {
            $table->id('id_monitoring');
            $table->foreignId('id_placement')->constrained('placement', 'id_placement')->cascadeOnDelete();
            $table->foreignId('id_manager')->nullable()->constrained('pegawai', 'id_pegawai')->nullOnDelete();
            $table->dateTime('tanggal_monitoring');
            $table->text('hasil_monitoring');
            $table->text('catatan_tindak_lanjut')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_talent');
    }
};
