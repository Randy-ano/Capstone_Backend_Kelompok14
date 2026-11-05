<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Shift tidak dihapus lagi, melainkan dibatalkan (revisi bimbingan 2026-11-05).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shifting_talent', function (Blueprint $table) {
            $table->string('status_shift')->default('aktif')->after('keterangan');
        });
    }

    public function down(): void
    {
        Schema::table('shifting_talent', function (Blueprint $table) {
            $table->dropColumn('status_shift');
        });
    }
};
