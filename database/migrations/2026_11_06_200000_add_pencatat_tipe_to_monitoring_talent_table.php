<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Monitoring kinerja per talent (revisi 2026-11-06): catatan dicatat HR Client atau Manager, Surat Peringatan (SP)
 * oleh Manager dengan lampiran dokumen. `id_manager` tetap ada (nullable) tetapi tidak dipakai lagi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monitoring_talent', function (Blueprint $table) {
            $table->foreignId('id_user_pencatat')->nullable()->after('id_manager')
                ->constrained('users', 'id_user')->nullOnDelete();
            $table->string('tipe')->default('catatan')->after('id_user_pencatat');
            $table->foreignId('id_dokumen')->nullable()->after('tipe')
                ->constrained('dokumen', 'id_dokumen')->nullOnDelete();
        });

        // Data lama: pencatat = akun user dari pegawai (Manager) yang mencatat.
        DB::table('monitoring_talent')->whereNotNull('id_manager')->orderBy('id_monitoring')->each(function (object $m) {
            DB::table('monitoring_talent')->where('id_monitoring', $m->id_monitoring)->update([
                'id_user_pencatat' => DB::table('pegawai')->where('id_pegawai', $m->id_manager)->value('id_user'),
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('monitoring_talent', function (Blueprint $table) {
            $table->dropConstrainedForeignId('id_dokumen');
            $table->dropColumn('tipe');
            $table->dropConstrainedForeignId('id_user_pencatat');
        });
    }
};
