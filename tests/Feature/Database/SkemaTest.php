<?php

use Illuminate\Support\Facades\Schema;

/**
 * Nama tabel & kolom harus sama persis dengan BACKEND_SPEC bagian 4.
 */
dataset('skema', [
    'users' => ['users', ['id_user', 'email', 'password', 'role', 'status_aktif']],
    'pegawai' => ['pegawai', ['id_pegawai', 'id_user', 'nama_pegawai', 'jabatan', 'no_telepon']],
    'klien' => ['klien', ['id_klien', 'nama_perusahaan', 'alamat', 'no_telepon', 'penanggung_jawab']],
    'hr_client' => ['hr_client', ['id_hr_client', 'id_user', 'id_klien', 'nama_hr', 'jabatan']],
    'talent' => ['talent', ['id_talent', 'id_user', 'nama_lengkap', 'pendidikan', 'pengalaman_kerja', 'file_cv', 'status_kelengkapan', 'status_kandidat']],
    'notifikasi' => ['notifikasi', ['id_notifikasi', 'id_user_penerima', 'pesan', 'tipe_notifikasi', 'kanal', 'jadwal_kirim', 'status_pengiriman', 'status_baca']],
    'kompetensi' => ['kompetensi', ['id_kompetensi', 'nama_kompetensi', 'kategori']],
    'talent_kompetensi' => ['talent_kompetensi', ['id_talent', 'id_kompetensi']],
    'job_requirement' => ['job_requirement', ['id_job_req', 'id_klien', 'id_am', 'posisi', 'jumlah_kebutuhan', 'kualifikasi', 'pengalaman_min', 'status_req', 'sla_deadline']],
    'job_req_kompetensi' => ['job_req_kompetensi', ['id_job_req', 'id_kompetensi']],
    'talent_pool' => ['talent_pool', ['id_talent_pool', 'id_talent', 'tanggal_masuk', 'status_ketersediaan', 'tag_keahlian']],
    'screening_talent' => ['screening_talent', ['id_screening', 'id_talent', 'id_job_req', 'id_ts', 'skor_kecocokan', 'tanggal_screening', 'catatan_screening', 'hasil_screening']],
    'progres_pemenuhan' => ['progres_pemenuhan', ['id_progres', 'id_job_req', 'id_talent', 'id_penanggung_jawab', 'status_tahapan', 'indikator_sla', 'log_waktu_pembaruan']],
    'pengajuan_kandidat' => ['pengajuan_kandidat', ['id_pengajuan', 'id_screening', 'id_talent', 'id_job_req', 'id_am', 'tanggal_pengajuan', 'status_pengajuan']],
    'jadwal_interview' => ['jadwal_interview', ['id_interview', 'id_pengajuan', 'tanggal_interview', 'waktu_interview', 'link_lokasi_meeting', 'instruksi', 'status_interview']],
    'evaluasi_kandidat' => ['evaluasi_kandidat', ['id_evaluasi', 'id_pengajuan', 'id_hr_client', 'nilai_score', 'catatan_evaluasi', 'status_evaluasi']],
    'placement' => ['placement', ['id_placement', 'id_evaluasi', 'id_talent', 'id_job_req', 'id_klien', 'posisi_penempatan', 'tanggal_mulai', 'tanggal_selesai', 'status_placement']],
    'pengajuan_cuti' => ['pengajuan_cuti', ['id_cuti', 'id_talent', 'id_placement', 'id_approver_klien', 'id_approver_manager', 'status_approval_klien', 'status_approval_manager', 'tanggal_mulai', 'tanggal_selesai', 'alasan_cuti', 'status_cuti']],
    'shifting_talent' => ['shifting_talent', ['id_shift', 'id_placement', 'id_pengelola', 'tanggal_shift', 'jenis_shift', 'keterangan']],
    'monitoring_talent' => ['monitoring_talent', ['id_monitoring', 'id_placement', 'id_manager', 'tanggal_monitoring', 'hasil_monitoring', 'catatan_tindak_lanjut']],
    'laporan_keluhan' => ['laporan_keluhan', ['id_laporan', 'id_placement', 'id_hr_client', 'id_penindak_lanjut', 'judul_laporan', 'deskripsi_keluhan', 'tanggal_laporan', 'status_laporan', 'hasil_tindak_lanjut', 'tanggal_tindak_lanjut']],
    'dokumen' => ['dokumen', ['id_dokumen', 'id_user_uploader', 'id_klien', 'id_talent', 'id_placement', 'jenis_dokumen', 'file_path', 'status_dokumen', 'tanggal_unggah']],
    'tanda_tangan_dokumen' => ['tanda_tangan_dokumen', ['id_ttd', 'id_dokumen', 'id_user_penandatangan', 'file_tanda_tangan', 'tanggal_ttd', 'status_ttd']],
]);

it('punya kolom sesuai spec', function (string $tabel, array $kolom) {
    $penghubung = ['talent_kompetensi', 'job_req_kompetensi'];
    $harapan = in_array($tabel, $penghubung) ? $kolom : [...$kolom, 'created_at', 'updated_at'];

    expect(Schema::getColumnListing($tabel))->toEqualCanonicalizing($harapan);
})->with('skema');
