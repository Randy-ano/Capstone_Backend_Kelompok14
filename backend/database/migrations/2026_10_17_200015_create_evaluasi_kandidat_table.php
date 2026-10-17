<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluasi_kandidat', function (Blueprint $table) {
            $table->id('id_evaluasi');
            $table->foreignId('id_pengajuan')->unique()->constrained('pengajuan_kandidat', 'id_pengajuan')->cascadeOnDelete();
            $table->foreignId('id_hr_client')->nullable()->constrained('hr_client', 'id_hr_client')->nullOnDelete();
            $table->unsignedTinyInteger('nilai_score');
            $table->text('catatan_evaluasi')->nullable();
            $table->string('status_evaluasi');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluasi_kandidat');
    }
};
