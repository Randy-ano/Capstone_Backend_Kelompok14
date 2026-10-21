<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sertifikat_talent', function (Blueprint $table) {
            $table->id('id_sertifikat');

            $table->foreignId('id_talent')
                ->constrained('talent', 'id_talent')
                ->cascadeOnDelete();

            $table->string('nama_sertifikat');
            $table->string('penerbit')->nullable();
            $table->date('tanggal_terbit')->nullable();
            $table->date('tanggal_kedaluwarsa')->nullable();
            $table->string('file_sertifikat')->nullable();
            $table->string('url_sertifikat')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sertifikat_talent');
    }
};