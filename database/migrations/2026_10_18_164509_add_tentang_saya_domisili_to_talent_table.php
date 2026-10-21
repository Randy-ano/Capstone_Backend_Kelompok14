<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('talent', function (Blueprint $table) {
            // Informasi pribadi
            $table->date('tanggal_lahir')->nullable();
            $table->string('nomor_telepon', 20)->nullable();
            $table->text('alamat')->nullable();
            $table->string('domisili')->nullable();

            // Informasi profil
            $table->text('tentang_saya')->nullable();

            // Informasi karier
            $table->decimal('gaji_harapan', 15, 2)->nullable();

            // Tautan portofolio dan media sosial
            $table->string('linkedin')->nullable();
            $table->string('instagram')->nullable();
            $table->string('github')->nullable();
            $table->string('portfolio')->nullable();

            // Informasi pendidikan
            $table->string('nama_institusi')->nullable();
            $table->string('jurusan')->nullable();
            $table->unsignedSmallInteger('tahun_lulus')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('talent', function (Blueprint $table) {
            $table->dropColumn([
                'tanggal_lahir',
                'nomor_telepon',
                'alamat',
                'domisili',
                'tentang_saya',
                'gaji_harapan',
                'linkedin',
                'instagram',
                'github',
                'portfolio',
                'nama_institusi',
                'jurusan',
                'tahun_lulus',
            ]);
        });
    }
};
