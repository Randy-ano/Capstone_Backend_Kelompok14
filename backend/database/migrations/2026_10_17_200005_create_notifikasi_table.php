<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi', function (Blueprint $table) {
            $table->id('id_notifikasi');
            $table->foreignId('id_user_penerima')->constrained('users', 'id_user')->cascadeOnDelete();
            $table->text('pesan');
            $table->string('tipe_notifikasi');
            $table->string('kanal');
            $table->dateTime('jadwal_kirim')->nullable();
            $table->string('status_pengiriman')->default('menunggu');
            $table->boolean('status_baca')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi');
    }
};
