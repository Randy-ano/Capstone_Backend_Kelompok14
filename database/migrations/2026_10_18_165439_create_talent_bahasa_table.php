<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('talent_bahasa', function (Blueprint $table) {
            $table->id('id_talent_bahasa');

            $table->foreignId('id_talent')
                ->constrained('talent', 'id_talent')
                ->cascadeOnDelete();

            $table->foreignId('id_bahasa')
                ->constrained('bahasa', 'id_bahasa')
                ->cascadeOnDelete();

            $table->string('tingkat_kemampuan');

            $table->timestamps();

            $table->unique(['id_talent', 'id_bahasa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('talent_bahasa');
    }
};
