<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifting_talent', function (Blueprint $table) {
            $table->id('id_shift');
            $table->foreignId('id_placement')->constrained('placement', 'id_placement')->cascadeOnDelete();
            $table->foreignId('id_pengelola')->nullable()->constrained('users', 'id_user')->nullOnDelete();
            $table->date('tanggal_shift');
            $table->string('jenis_shift');
            $table->string('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['id_placement', 'tanggal_shift']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifting_talent');
    }
};
