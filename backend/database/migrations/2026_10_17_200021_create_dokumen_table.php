<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen', function (Blueprint $table) {
            $table->id('id_dokumen');
            $table->foreignId('id_user_uploader')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->foreignId('id_klien')->nullable()->constrained('klien', 'id_klien')->nullOnDelete();
            $table->foreignId('id_talent')->nullable()->constrained('talent', 'id_talent')->nullOnDelete();
            $table->foreignId('id_placement')->nullable()->constrained('placement', 'id_placement')->nullOnDelete();
            $table->string('jenis_dokumen');
            $table->string('file_path');
            $table->string('status_dokumen')->default('draft');
            $table->dateTime('tanggal_unggah');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen');
    }
};
