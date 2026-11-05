<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dokumen kerja sama berurutan (revisi 2026-11-06): Quotation, Purchase Order, dan PKS terhubung ke permintaan
 * kebutuhan; Kontrak Kerja terhubung ke pengajuan kandidat (talent + permintaan).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dokumen', function (Blueprint $table) {
            $table->foreignId('id_job_req')->nullable()->after('id_placement')
                ->constrained('job_requirement', 'id_job_req')->nullOnDelete();
            $table->foreignId('id_pengajuan')->nullable()->after('id_job_req')
                ->constrained('pengajuan_kandidat', 'id_pengajuan')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dokumen', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_pengajuan');
            $table->dropConstrainedForeignId('id_job_req');
        });
    }
};
