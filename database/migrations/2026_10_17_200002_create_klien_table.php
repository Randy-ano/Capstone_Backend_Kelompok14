<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('klien', function (Blueprint $table) {
            $table->id('id_klien');
            $table->string('nama_perusahaan');
            $table->text('alamat')->nullable();
            $table->string('no_telepon')->nullable();
            $table->string('penanggung_jawab')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('klien');
    }
};
