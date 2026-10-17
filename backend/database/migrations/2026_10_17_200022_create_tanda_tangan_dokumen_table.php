<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tanda_tangan_dokumen', function (Blueprint $table) {
            $table->id('id_ttd');
            $table->foreignId('id_dokumen')->constrained('dokumen', 'id_dokumen')->cascadeOnDelete();
            $table->foreignId('id_user_penandatangan')->constrained('users', 'id_user')->cascadeOnDelete();
            $table->string('file_tanda_tangan')->nullable();
            $table->dateTime('tanggal_ttd')->nullable();
            $table->string('status_ttd')->default('menunggu');
            $table->timestamps();

            $table->unique(['id_dokumen', 'id_user_penandatangan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tanda_tangan_dokumen');
    }
};
