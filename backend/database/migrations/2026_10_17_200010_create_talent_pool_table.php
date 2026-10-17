<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_pool', function (Blueprint $table) {
            $table->id('id_talent_pool');
            $table->foreignId('id_talent')->unique()->constrained('talent', 'id_talent')->cascadeOnDelete();
            $table->dateTime('tanggal_masuk');
            $table->string('status_ketersediaan')->default('tersedia');
            $table->string('tag_keahlian')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_pool');
    }
};
