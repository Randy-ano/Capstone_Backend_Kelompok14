<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kisaran gaji yang ditawarkan klien untuk permintaan kebutuhan (revisi bimbingan 2026-11-05). Rupiah, opsional.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('job_requirement', function (Blueprint $table) {
            $table->unsignedBigInteger('gaji_min')->nullable()->after('pengalaman_min');
            $table->unsignedBigInteger('gaji_maks')->nullable()->after('gaji_min');
        });
    }

    public function down(): void
    {
        Schema::table('job_requirement', function (Blueprint $table) {
            $table->dropColumn(['gaji_min', 'gaji_maks']);
        });
    }
};
