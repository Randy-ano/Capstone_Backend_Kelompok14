# BACKEND_SPEC — Intellisys Talent Hub

> Dokumen ini adalah instruksi kerja untuk AI coding agent (dan developer) yang membangun **backend REST API** sistem outsourcing talent PT Intellisys.
> Kerjakan **per fase secara berurutan** (lihat bagian 9). Jangan lompat fase. Setiap fase harus lolos kriteria selesainya (termasuk test) sebelum lanjut.

---

## 0. Aturan untuk Agent

1. **Skema database mengikuti bagian 4 secara persis** (nama tabel, nama kolom, tipe). Skema ini diturunkan dari ERD yang sudah disetujui dosen pembimbing. Jangan mengganti nama tabel/kolom ke konvensi Laravel default.
2. Kalau menemukan kebutuhan yang tidak tercakup di dokumen ini, **jangan menebak diam-diam**: buat solusi paling sederhana, lalu catat di file `DECISIONS.md` (tanggal, keputusan, alasan).
3. Semua logika bisnis di **Service class** (`app/Services`), bukan di controller. Controller hanya: validasi (FormRequest) → panggil service → kembalikan API Resource.
4. Setiap endpoint wajib punya **Feature test (Pest)** minimal untuk: sukses, validasi gagal (422), akses ditolak role lain (403), dan akses data milik pihak lain (403/404).
5. Commit per fitur kecil. Format pesan commit: `<jenis>: <deskripsi singkat dalam Bahasa Indonesia>`, dengan jenis `fitur`, `perbaikan`, `pembaruan`, `test`, atau `dokumen` (contoh `fitur: endpoint talent pool`). Nama branch `<jenis>/<nama-singkat>` (contoh `fitur/talent-pool`). Jangan pakai kode Jira (`SCRUM-xx`) di branch maupun commit.
6. Bahasa: nama tabel/kolom/enum dalam **Bahasa Indonesia** (sesuai ERD). Nama class, method, dan variabel PHP boleh Bahasa Inggris. Pesan error/sukses ke pengguna dalam **Bahasa Indonesia**.

---

## 1. Ringkasan Sistem

Sistem mengelola proses outsourcing talent dari kebutuhan klien sampai talent ditempatkan dan dimonitor.

**Alur inti:**
`Job Requirement → Rekomendasi Kandidat / Talent Pool → Usulan TS ke AM → Pengajuan ke Klien → Interview → Evaluasi Klien → Placement → Cuti / Shift / Monitoring / Laporan Keluhan`

Didukung oleh: Progres Pemenuhan (tahapan + SLA), Dokumen & Tanda Tangan Digital, Notifikasi & Reminder, Dashboard.

### Aktor (role)

| Role (`users.role`) | Siapa | Profil di tabel |
|---|---|---|
| `talent` | Kandidat / karyawan outsourcing | `talent` |
| `hr_client` | HR perusahaan klien | `hr_client` (terikat ke `klien`) |
| `account_manager` | AM PT Intellisys | `pegawai` |
| `talent_support` | TS PT Intellisys | `pegawai` |
| `manager` | Manager PT Intellisys | `pegawai` |

---

## 2. Tech Stack

| Bagian | Pilihan |
|---|---|
| Framework | Laravel versi stabil terbaru (PHP sesuai requirement versi tersebut) |
| Database | MySQL 8 |
| Auth API | Laravel Sanctum, **token-based** (`Authorization: Bearer <token>`) — dipakai web & mobile nanti |
| Otorisasi | Kolom `users.role` + middleware `role:` + Laravel Policies (lihat bagian 5) |
| PDF | `barryvdh/laravel-dompdf`; `setasign/fpdi` + `setasign/fpdf` untuk menempel gambar tanda tangan & menggabungkan PDF saat dokumen diunduh |
| Email | Laravel Mail (driver `log` saat development, SMTP saat produksi; contoh Mailtrap/Gmail di `.env.example` & SETUP.md) |
| Queue | Driver `database` |
| Scheduler | Laravel Scheduler (`routes/console.php`) |
| File | Laravel Storage, disk `local` (private) untuk CV, dokumen, tanda tangan |
| Test | Pest |
| Dokumentasi API | `knuckleswtf/scribe` → menghasilkan dokumentasi + koleksi Postman/OpenAPI untuk tim frontend |

> Catatan: `spatie/laravel-permission` **tidak dipakai**. Role hanya 5 dan tetap, sehingga kolom `users.role` (sesuai ERD) + Policies sudah cukup dan lebih sederhana.

---

## 3. Konvensi Teknis

### 3.1 Penamaan tabel & primary key
- Nama tabel = nama entitas ERD dalam huruf kecil (`job_requirement`, `pengajuan_kandidat`, dst). **Tunggal, bukan jamak.**
- Primary key = `id_<nama>` sesuai ERD (`id_user`, `id_talent`, …), `unsignedBigInteger` auto increment.
- Setiap Model wajib men-set:
  ```php
  protected $table = 'job_requirement';
  protected $primaryKey = 'id_job_req';
  ```
- Relasi Eloquent wajib menyebut foreign key & owner key secara eksplisit, contoh:
  ```php
  public function klien() { return $this->belongsTo(Klien::class, 'id_klien', 'id_klien'); }
  ```
- Tabel `users`: ganti migration bawaan Laravel agar PK-nya `id_user` dan kolomnya sesuai bagian 4. Pastikan Sanctum (`personal_access_tokens.tokenable_id`) tetap berfungsi.

### 3.2 Timestamp
Semua tabel memakai `$table->timestamps()` (created_at & updated_at), kecuali tabel penghubung. *(Ini tambahan standar Laravel di luar ERD — sudah dicatat di bagian 11.)*

### 3.3 Format response API
- Prefix semua route: `/api/v1`.
- Response sukses memakai **API Resource**:
  ```json
  { "data": { ... }, "message": "Berhasil disimpan" }
  ```
- List memakai pagination Laravel (`?page=1&per_page=15`), response menyertakan `data`, `links`, `meta`.
- Filter & pencarian lewat query string: `?search=`, `?status=`, `?sort=-created_at`.
- Error validasi: format default Laravel (HTTP 422, `message` + `errors`).
- 401 = belum login, 403 = role tidak berhak / bukan pemilik data, 404 = data tidak ditemukan.

### 3.4 Struktur folder
```
app/
  Enums/            # PHP backed enum untuk semua kolom status
  Http/
    Controllers/Api/V1/
    Requests/       # FormRequest per aksi
    Resources/      # API Resource per model
    Middleware/EnsureRole.php
  Models/
  Policies/
  Services/         # logika bisnis (RekomendasiService, PlacementService, dst)
  Notifications/
  Observers/        # pencatatan otomatis ProgresPemenuhan
database/
  migrations/
  seeders/
  factories/
tests/Feature/
```

---

## 4. Skema Database

Notasi: `FK→tabel` = foreign key ke primary key tabel tersebut. Semua FK `constrained` dengan aturan hapus yang disebutkan. Semua kolom enum diimplementasikan sebagai kolom `string` + **PHP backed enum** di `app/Enums` (lebih mudah diubah daripada enum MySQL).

### 4.1 Akun

**users**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_user | PK | |
| email | string, unique | |
| password | string | hash |
| role | string (enum Role) | `talent`, `hr_client`, `account_manager`, `talent_support`, `manager` |
| status_aktif | boolean, default true | akun nonaktif tidak bisa login |

**pegawai** — profil AM / TS / Manager
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_pegawai | PK | |
| id_user | FK→users, unique, cascade | 1 user = maks 1 pegawai |
| nama_pegawai | string | |
| jabatan | string | jabatan tertulis, mis. "Senior Account Manager" |
| no_telepon | string, nullable | |

**klien** — perusahaan klien
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_klien | PK | |
| nama_perusahaan | string | |
| alamat | text, nullable | |
| no_telepon | string, nullable | |
| penanggung_jawab | string, nullable | |

**hr_client** — akun HR milik perusahaan klien
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_hr_client | PK | |
| id_user | FK→users, unique, cascade | |
| id_klien | FK→klien, restrict | 1 klien punya 1..N HR |
| nama_hr | string | |
| jabatan | string, nullable | |

**talent**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_talent | PK | |
| id_user | FK→users, unique, cascade | |
| nama_lengkap | string | |
| pendidikan | string, nullable | |
| pengalaman_kerja | text, nullable | deskripsi bebas |
| file_cv | string, nullable | path file CV hasil generate/upload |
| status_kelengkapan | string (enum) | `belum_lengkap` (default), `lengkap` |
| status_kandidat | string (enum) | `tersedia` (default), `dalam_proses`, `ditempatkan`, `tidak_aktif` |
| *kolom profil tambahan* | | lihat bagian 4.2, "Profil talent" |

**notifikasi**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_notifikasi | PK | |
| id_user_penerima | FK→users, cascade | |
| pesan | text | |
| tipe_notifikasi | string | mis. `interview_dijadwalkan`, `cuti_diajukan`, `sla_mendekati` |
| kanal | string (enum) | `sistem`, `email` |
| jadwal_kirim | datetime, nullable | null = kirim segera |
| status_pengiriman | string (enum) | `menunggu` (default), `terkirim`, `gagal` |
| status_baca | boolean, default false | |

### 4.2 Kebutuhan & Kandidat

**kompetensi** — master data
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_kompetensi | PK | |
| nama_kompetensi | string, unique | mis. "Laravel", "React", "SQL" |
| kategori | string, nullable | mis. "Backend", "Frontend", "Database" |

**talent_kompetensi** — penghubung M:N (tanpa timestamps)
| Kolom | Tipe |
|---|---|
| id_talent | FK→talent, cascade |
| id_kompetensi | FK→kompetensi, cascade |
| *PK gabungan* | (id_talent, id_kompetensi) |

**job_requirement**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_job_req | PK | |
| id_klien | FK→klien, restrict | |
| id_am | FK→pegawai, nullable, set null | AM penanggung jawab |
| posisi | string | |
| jumlah_kebutuhan | unsignedInteger | |
| kualifikasi | text, nullable | |
| pengalaman_min | unsignedInteger, default 0 | tahun |
| gaji_min | unsignedBigInteger, nullable | kisaran gaji ditawarkan (rupiah/bulan), migration `2026_11_05_200000` |
| gaji_maks | unsignedBigInteger, nullable | ≥ `gaji_min`; tidak ditampilkan ke talent |
| status_req | string (enum) | `draft` (default), `diajukan`, `diproses`, `terpenuhi`, `dibatalkan` |
| sla_deadline | datetime | batas waktu pemenuhan |

**job_req_kompetensi** — penghubung M:N (tanpa timestamps)
| Kolom | Tipe |
|---|---|
| id_job_req | FK→job_requirement, cascade |
| id_kompetensi | FK→kompetensi, cascade |
| *PK gabungan* | (id_job_req, id_kompetensi) |

**talent_pool**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_talent_pool | PK | |
| id_talent | FK→talent, unique, cascade | 1 talent = maks 1 entri pool |
| tanggal_masuk | datetime | |
| status_ketersediaan | string (enum) | `tersedia` (default), `tidak_tersedia` |
| tag_keahlian | string, nullable | label bebas untuk pencarian cepat |

#### Profil talent (tambahan SCRUM-8)

Kolom dan tabel berikut ditambahkan untuk profil & CV talent (FR-03, FR-04); lihat DECISIONS 2026-10-21. Relasi:
`talent` 1–N `riwayat_pendidikan`, 1–N `pengalaman_kerja_talent`, 1–N `sertifikat_talent`, dan M–N `bahasa` lewat `talent_bahasa`. Semua FK ke `talent` memakai cascade on delete.

**talent** — kolom tambahan (semua nullable)
| Kolom | Tipe | Keterangan |
|---|---|---|
| tanggal_lahir | date | |
| nomor_telepon | string(20) | |
| alamat | text | |
| domisili | string | kota tempat tinggal saat ini |
| tentang_saya | text | ringkasan diri di CV |
| nama_institusi | string | pendidikan terakhir (ringkas) |
| jurusan | string | pendidikan terakhir (ringkas) |
| tahun_lulus | unsignedSmallInteger | pendidikan terakhir (ringkas) |
| gaji_harapan | decimal(15,2) | rupiah |
| linkedin, instagram, github, portfolio | string | URL |

**riwayat_pendidikan**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_riwayat_pendidikan | PK | |
| id_talent | FK→talent, cascade | |
| jenjang | string(50) | mis. "SMA", "D3", "S1" |
| nama_institusi | string | |
| jurusan | string, nullable | |
| tahun_mulai | unsignedSmallInteger, nullable | |
| tahun_selesai | unsignedSmallInteger, nullable | |
| deskripsi | text, nullable | |

**pengalaman_kerja_talent**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_pengalaman_kerja | PK | |
| id_talent | FK→talent, cascade | |
| nama_perusahaan | string | |
| posisi | string | |
| jenis_pekerjaan | string(50), nullable | mis. "Full-time", "Magang" |
| lokasi | string, nullable | |
| tanggal_mulai | date, nullable | dipakai R9 (lama pengalaman) |
| tanggal_selesai | date, nullable | kosong jika masih bekerja |
| masih_bekerja | boolean, default false | |
| deskripsi | text, nullable | |

**sertifikat_talent**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_sertifikat | PK | |
| id_talent | FK→talent, cascade | |
| nama_sertifikat | string | |
| penerbit | string, nullable | |
| tanggal_terbit | date, nullable | |
| tanggal_kedaluwarsa | date, nullable | |
| file_sertifikat | string, nullable | path file di disk private (`sertifikat/{id_talent}/{id_sertifikat}/{nama}.{ext}`); respons API hanya mengirim nama file |
| url_sertifikat | string, nullable | tautan verifikasi |

**bahasa** — master data
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_bahasa | PK | |
| nama_bahasa | string, unique | mis. "Indonesia", "Inggris" |

**talent_bahasa** — penghubung M:N (dengan timestamps)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_talent_bahasa | PK | |
| id_talent | FK→talent, cascade | |
| id_bahasa | FK→bahasa, cascade | |
| tingkat_kemampuan | string | mis. "Dasar", "Menengah", "Fasih" |
| *unique* | (id_talent, id_bahasa) | |

### 4.3 Proses Rekrutmen

**screening_talent** — satu baris = satu **usulan kandidat** dari Talent Support ke Account Manager (revisi 2026-11-05, tanpa perubahan kolom)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_screening | PK | |
| id_talent | FK→talent, cascade | |
| id_job_req | FK→job_requirement, cascade | |
| id_ts | FK→pegawai, nullable, set null | Talent Support yang menyaring |
| skor_kecocokan | decimal(5,2), nullable | 0–100, dari RekomendasiService |
| tanggal_screening | datetime | |
| catatan_screening | text, nullable | catatan TS untuk AM; bila AM tidak meneruskan, alasannya ditambahkan setelah penanda `[Tidak diteruskan AM] ` (dipisah lagi di respons) |
| hasil_screening | string (enum) | `pending` (default, hanya data lama), `lolos` (= usulan berlaku), `tidak_lolos` (= tidak diteruskan AM) |
| *unique* | (id_talent, id_job_req) | talent tidak boleh diusulkan 2x untuk job yang sama |

**progres_pemenuhan** — **log** perubahan tahapan (bukan sumber status utama)
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_progres | PK | |
| id_job_req | FK→job_requirement, cascade | |
| id_talent | FK→talent, nullable, cascade | null = progres level job (mis. job dibuat) |
| id_penanggung_jawab | FK→pegawai, nullable, set null | pihak yang harus menindaklanjuti |
| status_tahapan | string (enum) | `kebutuhan_dibuat`, `screening`, `diajukan`, `interview`, `evaluasi`, `placement`, `ditolak` |
| indikator_sla | string (enum) | `aman`, `mendekati`, `terlambat` (dihitung saat log dibuat) |
| log_waktu_pembaruan | datetime | |

**pengajuan_kandidat**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_pengajuan | PK | |
| id_screening | FK→screening_talent, unique, cascade | 1 screening → maks 1 pengajuan |
| id_talent | FK→talent, cascade | disalin dari screening (denormalisasi, untuk query cepat) |
| id_job_req | FK→job_requirement, cascade | disalin dari screening |
| id_am | FK→pegawai, nullable, set null | AM yang mengajukan |
| tanggal_pengajuan | datetime | |
| status_pengajuan | string (enum) | `diajukan` (default), `lanjut_interview`, `ditolak` |

**jadwal_interview**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_interview | PK | |
| id_pengajuan | FK→pengajuan_kandidat, cascade | 1 pengajuan → banyak interview (beberapa ronde) |
| tanggal_interview | **date** | *(lihat bagian 11)* |
| waktu_interview | **time** | |
| link_lokasi_meeting | string, nullable | link meeting atau alamat |
| instruksi | text, nullable | |
| status_interview | string (enum) | `dijadwalkan` (default), `selesai`, `dibatalkan`, `dijadwal_ulang` |

**evaluasi_kandidat**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_evaluasi | PK | |
| id_pengajuan | FK→pengajuan_kandidat, unique, cascade | 1 pengajuan → maks 1 evaluasi |
| id_hr_client | FK→hr_client, nullable, set null | |
| nilai_score | unsignedTinyInteger | 0–100 |
| catatan_evaluasi | text, nullable | |
| status_evaluasi | string (enum) | `diterima`, `ditolak` |

### 4.4 Penempatan & Operasional

**placement**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_placement | PK | |
| id_evaluasi | FK→evaluasi_kandidat, unique, restrict | wajib evaluasi `diterima` |
| id_talent | FK→talent, restrict | disalin dari evaluasi→pengajuan |
| id_job_req | FK→job_requirement, restrict | disalin |
| id_klien | FK→klien, restrict | disalin dari job_requirement |
| posisi_penempatan | string | |
| tanggal_mulai | date | |
| tanggal_selesai | date, nullable | |
| status_placement | string (enum) | `aktif` (default), `selesai`, `dihentikan` |

**pengajuan_cuti**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_cuti | PK | |
| id_talent | FK→talent, cascade | |
| id_placement | FK→placement, cascade | placement harus `aktif` |
| id_approver_klien | FK→hr_client, nullable, set null | diisi saat HR memutuskan |
| id_approver_manager | FK→pegawai, nullable, set null | **tidak dipakai lagi** (revisi 2026-11-05: keputusan cuti hanya HR Client); tetap ada tanpa migration |
| status_approval_klien | string (enum) | `menunggu` (default), `disetujui`, `ditolak` |
| status_approval_manager | string (enum) | `menunggu` (default), `disetujui`, `ditolak` — **tidak dipakai lagi**, selalu `menunggu` |
| tanggal_mulai | date | |
| tanggal_selesai | date | ≥ tanggal_mulai |
| alasan_cuti | text | |
| status_cuti | string (enum) | `menunggu` (default), `disetujui`, `ditolak`, `dibatalkan` |

**shifting_talent**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_shift | PK | |
| id_placement | FK→placement, cascade | |
| id_pengelola | FK→users, nullable, set null | HR Client atau Manager yang mengatur |
| tanggal_shift | date | |
| jenis_shift | string (enum) | `pagi`, `siang`, `malam`, `libur` |
| keterangan | string, nullable | |
| status_shift | string (enum) | `aktif` (default), `dibatalkan` — migration `2026_11_05_100000` (shift tidak dihapus, hanya dibatalkan) |
| *unique* | (id_placement, tanggal_shift) | 1 shift per talent per hari |

**monitoring_talent**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_monitoring | PK | |
| id_placement | FK→placement, cascade | |
| id_manager | FK→pegawai, nullable, set null | |
| tanggal_monitoring | datetime | |
| hasil_monitoring | text | |
| catatan_tindak_lanjut | text, nullable | |

**laporan_keluhan**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_laporan | PK | |
| id_placement | FK→placement, cascade | |
| id_hr_client | FK→hr_client, nullable, set null | pelapor |
| id_penindak_lanjut | FK→pegawai, nullable, set null | AM/Manager yang menindaklanjuti |
| judul_laporan | string | |
| deskripsi_keluhan | text | |
| tanggal_laporan | datetime | |
| status_laporan | string (enum) | `baru` (default), `diproses`, `selesai` |
| hasil_tindak_lanjut | text, nullable | |
| tanggal_tindak_lanjut | datetime, nullable | |

### 4.5 Dokumen

**dokumen**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_dokumen | PK | |
| id_user_uploader | FK→users, nullable, set null | |
| id_klien | FK→klien, nullable, set null | untuk quotation, PO, PKS |
| id_talent | FK→talent, nullable, set null | untuk CV, kontrak kerja, SP |
| id_placement | FK→placement, nullable, set null | untuk kontrak kerja, SP |
| id_job_req | FK→job_requirement, nullable, set null | quotation, PO, PKS, dan kontrak kerja (R34; migration `2026_11_06_100000`) |
| id_pengajuan | FK→pengajuan_kandidat, nullable, set null | kontrak kerja per kandidat diterima (R34; migration `2026_11_06_100000`) |
| jenis_dokumen | string (enum) | `quotation`, `purchase_order`, `pks`, `kontrak_kerja`, `surat_peringatan`, `cv`, `lainnya` |
| file_path | string | path di disk private |
| status_dokumen | string (enum) | `draft` (default), `menunggu_ttd`, `ditandatangani`, `ditolak` |
| tanggal_unggah | datetime | |

**tanda_tangan_dokumen**
| Kolom | Tipe | Keterangan |
|---|---|---|
| id_ttd | PK | |
| id_dokumen | FK→dokumen, cascade | |
| id_user_penandatangan | FK→users, cascade | |
| file_tanda_tangan | string, nullable | path gambar tanda tangan |
| tanggal_ttd | datetime, nullable | |
| status_ttd | string (enum) | `menunggu` (default), `ditandatangani`, `ditolak` |
| *unique* | (id_dokumen, id_user_penandatangan) | |

---

## 5. Otorisasi & Pembatasan Data

### 5.1 Middleware role
Buat `EnsureRole` dan daftarkan alias `role`. Contoh: `Route::middleware('role:talent_support,manager')`.

### 5.2 Scoping data (wajib, ditegakkan di Policy + query)
| Role | Boleh melihat |
|---|---|
| `talent` | Hanya data miliknya: profil, screening/pengajuan/interview miliknya, placement miliknya, cuti & shift miliknya, dokumen dengan `id_talent` miliknya, notifikasinya. **Cuti, shift, dan dokumen hanya terbuka setelah talent punya placement berstatus tampil Belum Aktif/Aktif** (middleware `talent.ditempatkan`; selain itu 403 "Fitur ini tersedia setelah Anda ditempatkan di perusahaan klien."). Profil & CV tidak terpengaruh. |
| `hr_client` | Hanya data dengan `id_klien` = klien tempat ia bekerja: job requirement, pengajuan ke job klien tersebut, interview, evaluasi, placement, cuti & shift talent yang ditempatkan di klien tsb, laporan keluhan, dokumen klien |
| `account_manager`, `talent_support`, `manager` | Semua data (internal PT Intellisys), dengan batas aksi sesuai tabel endpoint (cuti: hanya melihat) |

Helper yang disarankan di model `User`: `isInternal()`, `kliens()` / `idKlien()`, `talentProfile()`, `pegawaiProfile()`, `hrClientProfile()`.

---

## 6. Aturan Bisnis (WAJIB diimplementasikan di Service + diuji)

### Akun (FR-01, FR-02)
- **R1.** Registrasi publik hanya untuk `talent` dan `hr_client`.
  - Talent: membuat `users` + `talent` (status_kelengkapan `belum_lengkap`).
  - HR Client: mengisi data perusahaan → membuat `klien` baru + `users` + `hr_client`. *(Bergabung ke klien yang sudah ada belum didukung — catat di DECISIONS.md.)*
- **R2.** Akun `pegawai` (AM/TS/Manager) hanya dibuat lewat seeder atau endpoint khusus manager.
- **R3.** User dengan `status_aktif = false` ditolak saat login (403).

### Profil & Talent Pool (FR-03, FR-04, FR-06)
- **R4.** Profil talent dianggap **lengkap** jika: `nama_lengkap` terisi, ada **minimal 1** `riwayat_pendidikan`, **dan** minimal 1 kompetensi. Status diperbarui otomatis setiap profil/riwayat pendidikan/kompetensi berubah (bisa naik ke `lengkap` maupun turun ke `belum_lengkap`).
- **R5.** Saat status berubah menjadi `lengkap` dan talent belum ada di pool → **otomatis** dibuat entri `talent_pool` (`tersedia`, tanggal_masuk = now). Diimplementasikan di `TalentObserver` (event `updated`, `firstOrCreate`), sehingga tidak pernah membuat entri ganda.
- **R6.** Generate CV: render PDF dari data profil + kompetensi (Blade view → dompdf), simpan ke storage, isi `talent.file_cv`, dan buat record `dokumen` jenis `cv`.

### Job Requirement & Rekomendasi (FR-05, FR-07)
- **R7.** Job requirement dibuat oleh `hr_client` (otomatis `id_klien` miliknya) atau `account_manager` (wajib memilih `id_klien`). Minimal 1 kompetensi.
- **R8.** Saat job requirement berstatus `diajukan`, catat progres `kebutuhan_dibuat`.
- **R33. Kompetensi baru dari job requirement.** Pembuat job requirement (`hr_client`/`account_manager`) boleh mengirim `kompetensi_baru` (nama, maks. 10, tiap nama 2–100 karakter) di samping `kompetensi` (id master); "minimal 1 kompetensi" (R7) berlaku untuk gabungan keduanya. Nama dicocokkan dengan master lewat normalisasi (huruf kecil, tanpa spasi, titik, tanda hubung, garis bawah; "React.js" = "reactjs" = "React JS"): jika cocok dipakai id yang ada, jika tidak dibuat kompetensi baru (nama di-trim & spasi ganda dirapikan, `kategori` null) dalam transaksi yang sama dengan penyimpanan job requirement. Kelola master (`POST/PUT/DELETE /kompetensi`) tetap hanya TS & Manager.
- **R9. Skor kecocokan** (RekomendasiService) — **pencocokan berbasis aturan, BUKAN AI/machine learning.**
  Data yang dibandingkan: kriteria di `job_requirement` (+ `job_req_kompetensi`) dengan data profil talent yang menjadi isi CV (`talent` + `talent_kompetensi`). **Tidak membaca/parsing file PDF CV.** Jangan memakai kata "AI" di nama class, endpoint, response, maupun komentar.
  ```
  skor_kompetensi = (jumlah kompetensi talent yang cocok / jumlah kompetensi job) × 100

  lama_pengalaman = total tahun dari semua baris pengalaman_kerja_talent milik talent:
                    tiap baris = tanggal_mulai s.d. tanggal_selesai
                    (atau hari ini jika masih_bekerja = true);
                    periode yang tumpang tindih digabung dulu, sehingga tidak dihitung dua kali;
                    baris tanpa tanggal_mulai, atau tanpa tanggal_selesai padahal tidak masih_bekerja, diabaikan;
                    tanggal di masa depan dipotong sampai hari ini
                    (implementasi: PengalamanKerjaService::lamaTahun, dipakai juga di detail talent pool)
                    lalu dibulatkan 1 desimal sebelum dipakai di skor_pengalaman

  skor_pengalaman = 100                                         jika lama_pengalaman >= pengalaman_min
                  = (lama_pengalaman / pengalaman_min) × 100    selain itu
                    (pengalaman_min = 0 → selalu 100)

  skor_kecocokan  = 0,8 × skor_kompetensi + 0,2 × skor_pengalaman   (dibulatkan 2 desimal)
  ```
  Contoh: job butuh 4 kompetensi dan `pengalaman_min` 2 tahun; talent cocok 3 kompetensi dan pengalaman 1 tahun (Jan–Jun 2023 dan Apr–Des 2023 → digabung Jan–Des 2023). Skor = 0,8 × 75 + 0,2 × 50 = 70.
  Kandidat yang ditampilkan: `talent_pool.status_ketersediaan = tersedia`, `talent.status_kandidat = tersedia`, belum pernah di-screening untuk job ini, `skor_kompetensi` > 0 (pengalaman saja tidak cukup untuk muncul), dan **`skor_kecocokan` ≥ `RekomendasiService::SKOR_MINIMUM` (70)** (revisi 2026-11-05; rumus skor tidak berubah). Respons menyertakan `meta.skor_minimum`. Urutkan skor tertinggi. Kembalikan juga daftar kompetensi yang cocok & yang kurang, serta `lama_pengalaman` (tahun, 1 desimal).

### Screening & Progres (FR-08, FR-09)
- **R10. Usulan kandidat** (revisi 2026-11-05, menggantikan keputusan Lolos/Tidak Lolos): TS mengusulkan talent ke AM dari Rekomendasi Kandidat atau Talent Pool, dengan catatan opsional (maks. 1000 karakter). Usulan = baris `screening_talent` dengan `hasil_screening = lolos`. Talent **boleh di luar rekomendasi** (skor < 70 atau kompetensi tidak lengkap) karena syarat lain (kampus, pendidikan, latar belakang) dicek manusia; `skor_kecocokan` tetap dihitung dari R9 sebagai informasi. Talent harus ada di pool `tersedia` dan `status_kandidat = tersedia`; satu talent hanya sekali per job (422). Saat usulan dibuat → talent.status_kandidat = `dalam_proses`, job_requirement.status_req = `diproses` (jika masih `diajukan`), dan AM penanggung jawab job dinotifikasi (semua AM aktif bila job belum punya AM).
- **R11. Tinjauan AM.** AM meneruskan usulan ke klien (R14) atau **tidak meneruskan** dengan alasan wajib (maks. 1000 karakter): `hasil_screening` → `tidak_lolos` (final), alasan disimpan, talent.status_kandidat kembali `tersedia` (bisa diusulkan untuk permintaan lain), TS pengusul dinotifikasi. Status usulan turunan (`status_usulan`): `menunggu_am` (belum `tidak_lolos` & belum ada pengajuan), `diteruskan` (sudah ada pengajuan), `tidak_diteruskan` (`tidak_lolos`).
- **R12.** **Progres otomatis** (Observer): setiap perubahan tahapan (screening dibuat, pengajuan dibuat, interview dijadwalkan, evaluasi disimpan, placement dibuat, atau kandidat ditolak di tahap mana pun) membuat 1 baris `progres_pemenuhan`. Pemetaan: usulan dibuat → `screening` (label UI "Diusulkan ke AM"); usulan tidak diteruskan → `ditolak`; pengajuan dibuat → `diajukan`; pengajuan ditolak klien → `ditolak`; tiap interview dijadwalkan → `interview`; evaluasi `diterima` → `evaluasi`, `ditolak` → `ditolak`; placement dibuat → `placement`.
- **R13. Indikator SLA** dari `job_requirement.sla_deadline`:
  - `terlambat` jika sekarang > deadline dan status_req bukan `terpenuhi`/`dibatalkan`
  - `mendekati` jika sisa ≤ 3 hari (dan status_req bukan `terpenuhi`/`dibatalkan`)
  - `aman` selain itu
  (implementasi: `ProgresService::indikatorSla`; padanan SQL untuk filter: `ProgresService::filterIndikatorSla`)

### Pengajuan, Interview, Evaluasi (FR-10, FR-11, FR-12)
- **R14.** Pengajuan ("Teruskan ke Klien") dibuat AM dari usulan yang belum diteruskan dan belum `tidak_lolos` (hasil `lolos`, atau `pending` pada data lama). `id_talent` & `id_job_req` disalin dari usulan.
- **R15.** HR Client meninjau pengajuan: `lanjut_interview` atau `ditolak`. Jika ditolak → talent.status_kandidat = `tersedia`.
- **R16.** Interview hanya bisa dijadwalkan untuk pengajuan berstatus `lanjut_interview`. Bisa lebih dari satu (ronde). Interview dibuat, dijadwal ulang, dan ditandai selesai/dibatalkan **hanya oleh Account Manager**; HR Client & talent hanya melihat. Talent & HR Client menerima notifikasi.
- **R17.** Evaluasi oleh HR Client dari klien yang sama, maksimal 1 per pengajuan, dan hanya jika minimal 1 interview berstatus `selesai`. Jika `ditolak` → talent.status_kandidat = `tersedia`.

### Placement (FR-17)
- **R18.** Placement dibuat oleh TS, **hanya dari evaluasi `diterima`** yang belum punya placement, dan (revisi 2026-11-06) setelah dokumen Quotation–Kontrak Kerja selesai (R36). Salin id_talent, id_job_req, id_klien.
- **R19.** Saat placement dibuat (dalam 1 DB transaction): talent.status_kandidat = `ditempatkan`, talent_pool.status_ketersediaan = `tidak_tersedia`, dan jika jumlah placement `aktif` untuk job tsb ≥ `jumlah_kebutuhan` → status_req = `terpenuhi`.
- **R20.** Saat placement `selesai`/`dihentikan` → talent kembali `tersedia` di pool.

### Operasional (FR-14, FR-15, FR-18, FR-19)
- **R21. Cuti** (direvisi 2026-11-05)**:** diajukan talent untuk placement `aktif` miliknya; tanggal tidak boleh tumpang tindih dengan cuti lain yang `menunggu`/`disetujui`.
  - **Hanya HR Client** dari klien tempat talent ditempatkan yang memutuskan (`status_approval_klien`, `id_approver_klien`); keputusannya langsung menjadi `status_cuti` (`disetujui`/`ditolak`). Manager, AM, dan TS hanya melihat (PATCH approval → 403). Kolom approval manager tidak dipakai.
  - Lama cuti dihitung dalam **hari kerja** (Sabtu & Minggu tidak dihitung) → `jumlah_hari_kerja` di respons.
  - **Kuota** `CutiService::KUOTA_HARI_KERJA_PER_TAHUN` = 12 hari kerja per tahun kalender per talent; yang dihitung cuti `menunggu` + `disetujui`. Cuti yang melewati pergantian tahun dihitung per tahun. Melebihi sisa kuota → 422 dengan pesan berisi sisa kuota.
  - Tanggal: `tanggal_mulai` paling cepat besok, `tanggal_selesai` ≥ `tanggal_mulai`, keduanya dalam periode placement, dan rentang minimal berisi satu hari kerja.
  - Talent boleh membatalkan selama masih `menunggu`.
- **R22. Shift:** dikelola HR Client (klien yang sama) atau Manager. Dukung input massal (satu request untuk banyak tanggal). Talent hanya melihat shift miliknya. **Shift tidak dihapus**, melainkan dibatalkan (`status_shift` = `dibatalkan`, tetap tampil di daftar); shift yang dibatalkan tidak bisa diubah, dan aktif kembali bila tanggalnya diatur ulang lewat input massal.
- **R23. Monitoring:** dicatat oleh Manager untuk placement mana pun; dilihat Manager & AM, dan talent untuk placement miliknya (read-only, FR-15).
- **R24. Laporan keluhan:** dibuat HR Client untuk placement di kliennya (status `baru`). AM/Manager menindaklanjuti: isi `id_penindak_lanjut`, `hasil_tindak_lanjut`, `tanggal_tindak_lanjut`, ubah status.

### Dokumen (FR-13)
- **R25.** Upload: pdf/doc/docx/png/jpg, maks 5 MB, disimpan di disk private. Download hanya lewat endpoint terproteksi (cek Policy).
- **R26.** Buat dokumen dari template (quotation, PO, PKS): Blade view → PDF dengan data dari input + klien.
- **R27.** Pengunggah menentukan daftar penandatangan → dibuat baris `tanda_tangan_dokumen` (`menunggu`), status_dokumen = `menunggu_ttd`, penandatangan dinotifikasi. Calon penandatangan (akun aktif) sesuai jenis dokumen:
  - `quotation`, `purchase_order`, `pks`: Account Manager, Manager, dan HR Client dari klien dokumen;
  - `kontrak_kerja`, `surat_peringatan`: talent terkait dokumen, Account Manager, dan Manager;
  - `lainnya`: Account Manager, Manager, HR Client klien dokumen, dan talent terkait.
  Talent Support tidak menandatangani dokumen. **Dokumen yang diunggah talent:** penandatangan otomatis talent itu sendiri (dibuat saat unggah, status langsung `menunggu_ttd`); talent tidak dapat mengatur penandatangan (403).
- **R28.** Penandatangan mengunggah gambar tanda tangan → status_ttd `ditandatangani`. Jika semua sudah → status_dokumen `ditandatangani`. Jika ada yang menolak → `ditolak`.
- **R28a.** Dokumen PDF/PNG/JPG yang sudah punya penandatangan diunduh dalam bentuk **PDF bertanda tangan**, dibuat saat diunduh (file asli di disk tidak diubah):
  - dokumen buatan sistem (R26) dirender ulang dari templatenya dengan blok tanda tangan per penandatangan (peran/jabatan, gambar tanda tangan, nama, status, tanggal & jam);
  - PDF unggahan diberi halaman **Lembar Pengesahan** di akhir: per penandatangan gambar tanda tangan, nama, peran/jabatan, tanggal & jam, dan status;
  - gambar unggahan (PNG/JPG) dijadikan PDF: halaman pertama berisi gambar itu dimuat utuh & proporsional di A4 (lanskap bila gambar melebar), lalu Lembar Pengesahan; nama file unduhan berakhiran `.pdf`;
  - penandatangan yang belum memutuskan ditulis "Belum ditandatangani"; yang menolak "Ditolak".
  Dokumen tanpa penandatangan (draft) dan DOC/DOCX diunduh apa adanya. File asli (tanpa tanda tangan) tetap dapat diunduh pengunggah dan internal lewat `GET /dokumen/{id}/file-asli`.

### Dokumen kerja sama berurutan (revisi 2026-11-06)
Alur per permintaan kebutuhan: kandidat diterima di evaluasi → **Quotation** → **Purchase Order** → **PKS** → **Kontrak Kerja** (per kandidat) → penempatan oleh TS → onboarding. Satu langkah **selesai** jika ada dokumen jenis itu (untuk permintaan / kandidat tersebut) yang berstatus `ditandatangani`. Implementasi: `KelengkapanDokumenService`.
- **R34. Urutan.** Quotation, PO, PKS terhubung ke `id_job_req` (klien diambil dari permintaan); Kontrak Kerja ke `id_pengajuan` (talent, permintaan, klien, dan placement bila ada diambil dari pengajuan). Unggah/buat dari template ditolak 422 jika: Quotation — permintaan belum punya kandidat berevaluasi `diterima`; PO — Quotation permintaan belum selesai; PKS — PO belum selesai; Kontrak Kerja — kandidat belum diterima atau PKS permintaannya belum selesai. Pesan menyebut dokumen yang kurang, mis. "Purchase Order belum bisa diunggah: Quotation untuk permintaan ini belum selesai ditandatangani." Dokumen jenis yang sama untuk tujuan yang sama yang masih `draft`/`menunggu_ttd` juga ditolak (422); yang `ditolak` boleh diganti dokumen baru. Permintaan `dibatalkan` ditolak. Surat Peringatan & Lainnya tidak terikat urutan.
- **R35. Hak unggah & penandatangan yang disarankan.** Quotation: AM · PO: HR Client dari klien permintaan · PKS: AM atau Manager · Kontrak Kerja: AM (selain itu 403). Penandatangan disarankan (dicentang otomatis, tetap bisa diubah dari daftar calon R27): Quotation → HR Client klien · PO → Manager · PKS → Manager + HR Client · Kontrak Kerja → talent + Manager.
- **R36. Penempatan setelah kontrak.** `POST /placement` ditolak 422 sampai Quotation, PO, PKS permintaan dan Kontrak Kerja kandidat selesai ("Penempatan belum bisa dibuat. Dokumen yang belum selesai ditandatangani: …"). Setelah penempatan dibuat, Kontrak Kerja kandidat diisi `id_placement`. Kelengkapan tampil sebagai `kelengkapan_dokumen` (detail pengajuan diterima: `langkah[4]` dengan `status` `belum_ada`/`menunggu`/`ditolak`/`selesai` + `label_status` + `dokumen`, `selesai`, `kurang`; detail permintaan: `langkah[3]` + `kontrak_kerja[]` per kandidat + `tahap`) dan `tahap_dokumen` di `GET /progres`. Talent yang punya Kontrak Kerja boleh membuka menu Dokumen walau belum ditempatkan (`talent.ditempatkan:dokumen`, `akses_dokumen` di `/auth/me`) agar dapat menandatanganinya.

### Notifikasi & Reminder (FR-16)
- **R29.** Gunakan satu `NotifikasiService::kirim(user, tipe, pesan, kanal)` yang menyimpan ke tabel `notifikasi` dan, jika kanal `email`, mengirim email lewat queue lalu memperbarui `status_pengiriman`.
- **R30. Event yang memicu notifikasi:** permintaan kebutuhan diajukan oleh HR Client/AM (→ semua TS aktif + AM penanggung jawab, atau semua AM aktif bila belum ada; pelaku tidak dinotifikasi), usulan kandidat dibuat (→ AM penanggung jawab), usulan tidak diteruskan (→ TS pengusul), pengajuan kandidat dibuat (→ HR Client), status pengajuan berubah (→ AM, Talent), interview dijadwalkan/diubah (→ Talent, HR Client), evaluasi disimpan (→ AM, TS), placement dibuat (→ Talent, HR Client), cuti diajukan (→ HR Client, Manager), cuti diputuskan (→ Talent), laporan keluhan baru (→ AM, Manager), permintaan tanda tangan (→ penandatangan).
- **R31. Scheduler harian (pukul 07:00 WIB):**
  - Job requirement dengan SLA `mendekati`/`terlambat` → AM & Manager
  - Interview besok → Talent & HR Client
  - Cuti `menunggu` > 2 hari → approver yang belum memutuskan
  - Tanda tangan `menunggu` > 2 hari → penandatangan
  - Notifikasi dengan `jadwal_kirim` ≤ sekarang dan `menunggu` → kirim

### Dashboard (FR-20)
- **R32.** Endpoint agregasi untuk Manager (tanpa tabel baru): jumlah job requirement per status, jumlah kandidat per tahapan, jumlah job SLA `terlambat`/`mendekati`, jumlah placement aktif, jumlah laporan keluhan terbuka, tren placement per bulan (12 bulan terakhir).

---

## 7. Daftar Endpoint

Semua di bawah `/api/v1`. Kolom Role: T = talent, HC = hr_client, AM = account_manager, TS = talent_support, M = manager. "Internal" = AM, TS, M.

### Auth & Akun (FR-01, FR-02)
| Method | Path | Role | Keterangan |
|---|---|---|---|
| POST | /auth/register/talent | publik | R1 |
| POST | /auth/register/hr-client | publik | R1, termasuk data perusahaan |
| POST | /auth/login | publik | mengembalikan token + data user + role |
| POST | /auth/logout | semua | hapus token aktif |
| GET | /auth/me | semua | user + profil sesuai role |
| POST | /auth/lupa-password | publik | `email`; kirim email tautan `FRONTEND_URL/reset-password?token=…&email=…` (berlaku 60 menit). Respons selalu "Jika email terdaftar, tautan reset sudah dikirim." Maks 5/menit |
| POST | /auth/reset-password | publik | `email`, `token`, `password` (min. 8), `password_confirmation`; berhasil → semua token login dicabut; token salah/kedaluwarsa → 422 di field `token`. Maks 5/menit |
| PUT | /auth/password | semua | ubah password sendiri: `password_lama`, `password` (min. 8, beda dari lama), `password_confirmation`; sesi lain dihapus, sesi saat ini tetap |
| GET/POST/PUT | /pegawai, /pegawai/{id} | M | kelola akun internal (R2) |
| PATCH | /users/{id}/status | M | aktif/nonaktifkan akun |

### Profil Talent & Kompetensi (FR-03, FR-04)
| Method | Path | Role | Keterangan |
|---|---|---|---|
| GET | /kompetensi | semua | master kompetensi; filter `search` (beberapa kata kunci dipisah koma, salah satu cocok), `kategori` (satu nilai atau `kategori[]` beberapa kategori); `meta.kategori` = semua kategori terpakai; `dengan_pemakaian=1` menambah `jumlah_talent` & `jumlah_job_requirement` |
| POST/PUT/DELETE | /kompetensi, /kompetensi/{id} | TS, M | kelola master; nama unik tanpa membedakan huruf besar/kecil; DELETE ditolak 422 jika masih dipakai talent/job requirement |
| GET | /talent/profil | T | profil sendiri + kompetensi |
| PUT | /talent/profil | T | update profil (R4, R5); `riwayat_pendidikan[]`, `riwayat_pengalaman_kerja[]`, `bahasa[]` (`nama_bahasa`, `tingkat_kemampuan`) jika dikirim menggantikan daftar lama; `sertifikat[]` disinkronkan per `id_sertifikat` (baris ber-id diperbarui dan filenya tetap, tanpa id dibuat, yang tidak dikirim dihapus beserta filenya) |
| PUT | /talent/profil/kompetensi | T | sync daftar `id_kompetensi` |
| POST | /talent/cv/generate | T | R6; 422 jika profil belum lengkap (R4) |
| GET | /talent/cv/download | T | download CV milik sendiri (PDF); 404 jika belum dibuat |
| POST | /talent/sertifikat/{id}/file | T (pemilik) | unggah/ganti file sertifikat: multipart `file` pdf/jpg/png maks 2 MB, disk private; file lama dihapus |
| GET | /sertifikat/{id}/file | T pemilik, AM, TS, M, HC (jika talent pernah diajukan ke kliennya) | buka file sertifikat inline (pratinjau); 404 jika belum diunggah |

### Talent Pool (FR-06)
| Method | Path | Role | Keterangan |
|---|---|---|---|
| GET | /talent-pool | Internal | filter: `status`, `kompetensi[]` (harus punya semua), `search`; `talent.pendidikan` = ringkasan riwayat pendidikan terbaru; `meta.ringkasan` = `{ total, tersedia, tidak_tersedia }` dari seluruh pool (tidak terpengaruh filter) |
| GET | /talent-pool/{id} | Internal | detail + profil + `riwayat_pengalaman_kerja` + `lama_pengalaman_tahun` + `riwayat_screening` (riwayat usulan: `id_job_req`, `catatan_screening`, `alasan_tidak_diteruskan`, `status_usulan`, `label_status_usulan`) |
| GET | /talent-pool/{id}/cv | Internal | download CV talent (PDF); 404 jika belum dibuat |
| PATCH | /talent-pool/{id} | TS | ubah status_ketersediaan / tag_keahlian |

### Job Requirement & Rekomendasi (FR-05, FR-07)
| Method | Path | Role | Keterangan |
|---|---|---|---|
| GET | /job-requirements | HC, Internal | HC hanya miliknya; filter `status`, `indikator_sla`, `id_klien` (internal), `search` (posisi/klien); `meta.ringkasan` = jumlah per status + `sla_terlambat`, `sla_mendekati` (tidak terpengaruh filter) |
| POST | /job-requirements | HC, AM | R7; `status_req` awal `draft` (default) atau `diajukan`; `kompetensi[]` (id) dan/atau `kompetensi_baru[]` (nama, R33); opsional `gaji_min`, `gaji_maks` (rupiah, `gaji_maks` ≥ `gaji_min`) |
| GET | /job-requirements/{id} | HC, Internal | + kompetensi + `ringkasan_progres` (jumlah screening per hasil, `usulan_menunggu_am`/`usulan_diteruskan`/`usulan_tidak_diteruskan`, terpenuhi, sisa) + `kelengkapan_dokumen` (R36, bukan untuk talent) + `progres_terakhir` |
| PUT | /job-requirements/{id} | HC, AM | hanya saat `draft`/`diajukan`; parsial; `kompetensi` (+ `kompetensi_baru`, R33) jika dikirim menggantikan daftar lama; `kompetensi_baru` diabaikan tanpa `kompetensi`; klien tidak bisa diganti; `gaji_maks` dibandingkan dengan `gaji_min` baru atau yang tersimpan |
| PATCH | /job-requirements/{id}/status | HC, AM | body `status_req`: `diajukan` (dari draft, deadline belum lewat) / `dibatalkan` (dari draft/diajukan/diproses; talent yang sedang diproses untuk job ini kembali `tersedia`) |
| GET | /job-requirements/{id}/rekomendasi | TS | R9; job harus `diajukan`/`diproses`; hanya skor ≥ 70; `meta.skor_minimum` |
| GET | /klien | Internal | daftar klien read-only (`search`) untuk pilihan klien saat AM membuat job requirement |

### Usulan Kandidat (Screening) & Progres (FR-08, FR-09)
| Method | Path | Role | Keterangan |
|---|---|---|---|
| GET | /screening | TS, AM, M | filter `id_job_req`, `id_talent`, `hasil`, `status_usulan` (`menunggu_am`/`diteruskan`/`tidak_diteruskan`), `search`; tiap item + `status_usulan`, `label_status_usulan`, `catatan_screening` (catatan TS saja), `alasan_tidak_diteruskan` |
| POST | /screening | TS | usulkan ke AM (R10); body `id_job_req`, `id_talent`, `catatan_screening` (maks 1000); job harus `diajukan`/`diproses`, talent harus tersedia; tanpa batas skor |
| PUT | /screening/{id} | TS | ubah catatan/hasil (endpoint lama, tidak dipakai UI baru); `tidak_lolos` final; alasan AM tetap tersimpan saat catatan diubah; ditolak jika sudah diajukan ke klien atau job selesai |
| PATCH | /screening/{id}/tidak-diteruskan | AM | R11; body `alasan` (wajib, maks 1000); 422 jika usulan sudah diteruskan/tidak diteruskan atau job selesai |
| GET | /job-requirements/{id}/progres | Internal, HC | data job + indikator SLA terkini + `log_progres` (terbaru dulu) |
| GET | /progres | Internal | semua job + SLA terkini + `progres_terakhir` + `jumlah_screening` + `tahap_dokumen` (R36, null jika belum ada kandidat diterima), urut deadline terdekat (untuk tabel monitoring); filter sama dengan GET /job-requirements |

### Pengajuan, Interview, Evaluasi (FR-10, FR-11, FR-12)
| Method | Path | Role | Keterangan |
|---|---|---|---|
| GET | /pengajuan | HC, Internal, T | sesuai scope; filter `status` (nilai `status_tampil`, lihat bagian 8), `id_job_req`, `search`; talent tidak melihat catatan screening & nilai evaluasi |
| GET | /pengajuan/{id} | HC, Internal, T | detail + `interviews` + `evaluasi` + `id_placement` (sesuai scope) + `kelengkapan_dokumen` (R36; kandidat diterima, bukan untuk talent) |
| POST | /pengajuan | AM | R14 ("Teruskan ke Klien"); body `id_screening` (usulan menunggu AM); job harus `diproses` |
| PATCH | /pengajuan/{id}/status | HC | R15; hanya dari `diajukan` ke `lanjut_interview`/`ditolak` |
| GET | /interview | HC, Internal, T | sesuai scope, filter `dari`/`sampai` (tanggal), `status`, `id_pengajuan`; urut tanggal & waktu |
| POST | /interview | AM | R16; pengajuan `lanjut_interview` yang belum dievaluasi; tanggal ≥ hari ini, waktu `H:i` |
| PUT | /interview/{id} | AM | jadwal ulang (tanggal/waktu berubah → `dijadwal_ulang`) **atau** ubah status `selesai`/`dibatalkan` (final; `selesai` tidak boleh sebelum tanggalnya) |
| POST | /evaluasi | HC | R17; body `id_pengajuan`, `nilai_score` 0–100, `catatan_evaluasi`, `status_evaluasi` |
| GET | /evaluasi/{id} | HC, Internal | |

### Placement (FR-17)
| Method | Path | Role | Keterangan |
|---|---|---|---|
| GET | /placement | HC, Internal, T | sesuai scope; filter `status`, `id_klien` (internal), `search` |
| POST | /placement | TS | R18, R19, R36 (422 dengan daftar dokumen yang belum selesai); body `id_evaluasi`, `tanggal_mulai`, `tanggal_selesai`, `posisi_penempatan` (default posisi job) |
| GET | /placement/{id} | HC, Internal, T | + `ringkasan` (jumlah cuti, shift, monitoring, keluhan) |
| PATCH | /placement/{id}/status | TS, M | R20; `selesai`/`dihentikan` dari `aktif`; `tanggal_selesai` default tanggal kontrak atau hari ini |

### Cuti, Shift, Monitoring, Keluhan (FR-14, FR-15, FR-18, FR-19)
| Method | Path | Role | Keterangan |
|---|---|---|---|
| GET | /cuti | T, HC, M, AM, TS | sesuai scope (internal hanya melihat); filter `status`, `id_placement`; item berisi `jumlah_hari`, `jumlah_hari_kerja`, `approval_klien` |
| GET | /cuti/kuota | T | `{ tahun, kuota, terpakai, sisa }` dalam hari kerja; query `tahun` (default tahun ini) |
| POST | /cuti | T | R21; body `id_placement`, `tanggal_mulai` (≥ besok), `tanggal_selesai`, `alasan_cuti`; dalam periode placement, minimal 1 hari kerja, tidak melebihi sisa kuota |
| PATCH | /cuti/{id}/approval | HC | body: `keputusan` (disetujui/ditolak); langsung menjadi status akhir, hanya saat `menunggu` |
| PATCH | /cuti/{id}/batal | T | pemilik, hanya saat `menunggu` |
| GET | /shift | T, HC, M | filter `id_placement`, `dari`, `sampai`; urut tanggal, default 31 per halaman |
| POST | /shift | HC, M | input massal (R22): `id_placement`, `tanggal[]` (maks 62), `jenis_shift`, `keterangan`; tanggal yang sudah ada diperbarui; respons `{ dibuat, diperbarui }` |
| PUT | /shift/{id} | HC, M | placement harus `aktif`; tanggal dalam periode placement; shift dibatalkan tidak bisa diubah |
| PATCH | /shift/{id}/batal | HC, M | `status_shift` → `dibatalkan` (tidak ada lagi DELETE) |
| GET | /monitoring | M, AM, T | filter `id_placement`, `search`; talent hanya catatan placement miliknya (FR-15, read-only) |
| POST | /monitoring | M | R23; `tanggal_monitoring` default sekarang, tidak boleh di masa depan |
| GET | /laporan-keluhan | HC, AM, M | sesuai scope; filter `status`, `id_placement`, `search`; `meta.ringkasan` per status |
| POST | /laporan-keluhan | HC | R24 |
| PATCH | /laporan-keluhan/{id}/tindak-lanjut | AM, M | R24; `status_laporan` diproses/selesai, `hasil_tindak_lanjut` wajib saat selesai; laporan selesai final |

### Dokumen (FR-13)
| Method | Path | Role | Keterangan |
|---|---|---|---|
| GET | /dokumen | semua | dokumen kerja sama saja: **jenis `cv` tidak pernah tampil** dan bukan nilai filter `jenis` yang sah (CV lewat `/talent/cv` & `/talent-pool/{id}/cv`); sesuai scope (internal semua; HC dokumen kliennya; T miliknya; + yang diunggah/harus ditandatangani sendiri); filter `jenis`, `status`, `perlu_ttd`, `search`; tiap item berisi `penandatangan[]` (+ `ada_gambar_ttd`), `unduhan` (`nama_file`, `diolah`, `dengan_tanda_tangan`), dan `aksi` (+ `boleh_unduh_asli`) |
| POST | /dokumen/upload | AM, HC, M, T | R25 (multipart `file`, `jenis_dokumen` selain cv). Quotation/PO/PKS wajib `id_job_req`, Kontrak Kerja wajib `id_pengajuan` (atau `id_placement` untuk perpanjangan); klien/talent/placement diambil dari tujuan; urutan & hak unggah R34–R35 (422 / 403). Jenis lain: `id_placement`/`id_klien`/`id_talent` seperti sebelumnya; unggahan talent langsung `menunggu_ttd` dengan penandatangan talent itu sendiri (R27) |
| POST | /dokumen/generate | AM | R26 (quotation/PO: `items[]`; PKS: `ketentuan`; + `id_job_req` (klien dari permintaan), `nomor_dokumen`, `tanggal_dokumen`, `berlaku_sampai`, `perihal`, `catatan`); urutan & hak unggah R34–R35 berlaku, sehingga PO dari template ditolak 403 (PO diunggah HR Client) |
| GET | /dokumen/{id}/download | sesuai Policy | PDF/PNG/JPG yang sudah punya penandatangan dikirim sebagai PDF bertanda tangan (R28a, nama = `unduhan.nama_file`); selain itu file asli |
| GET | /dokumen/{id}/file-asli | pengunggah, AM, TS, M | file asli apa adanya, tanpa tanda tangan; selain itu 403 |
| GET | /dokumen/{id}/tanda-tangan/{id_ttd}/gambar | sesuai Policy (lihat dokumen) | gambar tanda tangan penandatangan yang sudah menandatangani (inline, untuk thumbnail); 404 jika belum ada |
| GET | /dokumen/{id} | sesuai Policy (lihat dokumen) | detail satu dokumen (format sama dengan item daftar); dipakai tautan daftar periksa R36 |
| GET | /dokumen/tujuan | AM, HC, M | `jenis` = quotation/purchase_order/pks/kontrak_kerja: pilihan permintaan (`id_job_req`, `posisi`, `id_klien`, `nama_perusahaan`) atau kandidat diterima (`id_pengajuan`, `id_job_req`, `nama_lengkap`, `posisi`, `nama_perusahaan`) yang memenuhi urutan R34 dan belum punya dokumen jenis itu yang berjalan/selesai; 403 jika role tidak berhak (R35) |
| GET | /dokumen/{id}/calon-penandatangan | pengunggah (bukan talent) | tiap calon + `disarankan` (R35: Quotation → HR Client, PO → Manager, PKS → Manager + HR Client, Kontrak Kerja → talent + Manager);  sesuai jenis dokumen (R27): komersial → AM, M, HC klien; kepegawaian → talent terkait, AM, M; lainnya → AM, M, HC klien, talent terkait |
| POST | /dokumen/{id}/penandatangan | pengunggah (bukan talent) | R27; body `id_user[]` (1–10) dari calon penandatangan; hanya saat `draft` |
| POST | /dokumen/{id}/tanda-tangan | penandatangan | R28 (multipart `keputusan` ditandatangani/ditolak + `file_tanda_tangan` png/jpg maks 2 MB) |

### Notifikasi (FR-16)
| Method | Path | Role | Keterangan |
|---|---|---|---|
| GET | /notifikasi | semua | milik sendiri, terbaru dulu; filter `status_baca`; notifikasi berjadwal tampil setelah `jadwal_kirim` |
| GET | /notifikasi/belum-dibaca/jumlah | semua | untuk badge lonceng: `{ jumlah }` |
| PATCH | /notifikasi/{id}/baca | semua | hanya penerima (403 untuk lainnya) |
| PATCH | /notifikasi/baca-semua | semua | `{ jumlah }` yang ditandai |

Scheduler: `php artisan notifikasi:pengingat` (terjadwal 07:00 WIB, R31). Jalankan `php artisan schedule:work` dan `php artisan queue:work` di server agar pengingat & email terkirim.

### Dashboard (FR-20)
| Method | Path | Role | Keterangan |
|---|---|---|---|
| GET | /dashboard/manager | M | R32: `job_requirement` (total & per status), `sla` (terlambat/mendekati, job diajukan/diproses), `kandidat_per_tahapan` (tersedia, diusulkan [usulan menunggu AM], diajukan, interview, diterima, ditempatkan), `placement_aktif`, `laporan_keluhan_terbuka`, `tren_placement` (12 bulan, per bulan mulai) |
| GET | /dashboard/ringkasan | semua | `kartu[]` (`kunci`, `label`, `nilai`, `keterangan`, `tujuan`) sesuai role + `notifikasi_belum_dibaca` |

---

## 8. Contoh Kontrak JSON

**POST /api/v1/auth/login**
```json
// request
{ "email": "ts@intellisys.test", "password": "password" }
// 200
{
  "data": {
    "token": "1|xxxxxxxx",
    "user": { "id_user": 3, "email": "ts@intellisys.test", "role": "talent_support",
              "profil": { "id_pegawai": 2, "nama_pegawai": "Rina", "jabatan": "Talent Support" } }
  },
  "message": "Login berhasil"
}
```

**Status tampil pengajuan** — `GET /api/v1/pengajuan`, `GET /api/v1/pengajuan/{id}` dan respons aksi pengajuan menyertakan dua field turunan (bukan kolom database) selain `status_pengajuan`:
```json
{ "status_pengajuan": "lanjut_interview", "status_tampil": "ditempatkan", "label_status_tampil": "Ditempatkan" }
```
| Kondisi (dicek berurutan) | `status_tampil` | `label_status_tampil` |
|---|---|---|
| evaluasi sudah punya placement | `ditempatkan` | Ditempatkan |
| evaluasi diterima | `diterima` | Diterima |
| evaluasi ditolak | `ditolak` | Ditolak |
| selain itu, `status_pengajuan` = `diajukan` | `diajukan` | Menunggu Tinjauan |
| `lanjut_interview` | `lanjut_interview` | Lanjut Interview |
| `ditolak` | `ditolak` | Ditolak |

Filter `status` di `GET /pengajuan` memakai nilai `status_tampil`, sehingga hasil filter selalu sama dengan badge.

**Status tampil penempatan** — `GET /placement`, `GET /placement/{id}`, ringkasan `placement` di respons cuti/shift/monitoring/keluhan, dan `GET /monitoring` (top-level) menyertakan dua field turunan (bukan kolom database):
```json
{ "status_placement": "aktif", "status_tampil": "belum_aktif", "label_status_tampil": "Belum Aktif" }
```
| Kondisi (dicek berurutan) | `status_tampil` | `label_status_tampil` |
|---|---|---|
| `status_placement` `selesai`/`dihentikan` | `selesai` | Selesai |
| `tanggal_selesai` < hari ini | `selesai` | Selesai |
| `tanggal_mulai` > hari ini | `belum_aktif` | Belum Aktif |
| selain itu | `aktif` | Aktif |

**GET /api/v1/auth/me (talent)** — `data.sudah_ditempatkan` (boolean, hanya untuk role talent; juga di `data.user` respons login) = talent punya placement `belum_aktif`/`aktif`. Frontend memakai field ini untuk menu Pengajuan Cuti & Shift dan Dokumen.

**GET /api/v1/cuti/kuota**
```json
{ "data": { "tahun": 2026, "kuota": 12, "terpakai": 2, "sisa": 10 }, "message": "Berhasil" }
```

**GET /api/v1/job-requirements/12/rekomendasi**
```json
{
  "data": [
    {
      "id_talent": 7,
      "nama_lengkap": "Budi Santoso",
      "skor_kecocokan": 75.0,
      "kompetensi_cocok": ["Laravel", "MySQL", "REST API"],
      "kompetensi_kurang": ["Docker"],
      "status_ketersediaan": "tersedia"
    }
  ],
  "message": "4 kandidat direkomendasikan"
}
```

**GET /api/v1/job-requirements?status=diproses&page=1**
```json
{
  "data": [
    {
      "id_job_req": 12,
      "posisi": "Backend Developer",
      "klien": { "id_klien": 1, "nama_perusahaan": "PT Maju Jaya" },
      "jumlah_kebutuhan": 2,
      "terpenuhi": 1,
      "status_req": "diproses",
      "sla_deadline": "2026-11-30T17:00:00+07:00",
      "indikator_sla": "aman",
      "kompetensi": [ { "id_kompetensi": 1, "nama_kompetensi": "Laravel" } ]
    }
  ],
  "links": { "...": "..." },
  "meta": { "current_page": 1, "per_page": 15, "total": 8 }
}
```

Konvensi tanggal: ISO 8601 dengan zona waktu `Asia/Jakarta` (set `APP_TIMEZONE=Asia/Jakarta`).

---

## 9. Tahapan Kerja

Setiap fase: kerjakan → jalankan `php artisan test` → semua hijau → commit → lanjut.

### Fase 0 — Setup project
- Buat project Laravel di folder `backend/`, set `APP_TIMEZONE=Asia/Jakarta`, `APP_LOCALE=id`.
- `php artisan install:api` (Sanctum + routes/api.php).
- Install: dompdf, Scribe, Pest.
- Atur CORS untuk origin frontend (`http://localhost:5173`).
- **Selesai jika:** `GET /api/v1/health` mengembalikan `{ "status": "ok" }` dan test-nya lulus.

### Fase 1 — Database, Model, Seeder
- Semua migration sesuai bagian 4 (urutan sesuai dependensi FK).
- Semua Model + relasi Eloquent (lengkap dua arah) + Enum + Factory.
- Seeder:
  - 1 akun per role (password `password`): `talent@intellisys.test`, `hr@intellisys.test`, `am@intellisys.test`, `ts@intellisys.test`, `manager@intellisys.test`
  - ±30 kompetensi IT (Laravel, React, Vue, Node.js, Java, Spring, Python, Django, Flutter, Kotlin, SQL, MySQL, PostgreSQL, MongoDB, Docker, Kubernetes, AWS, Git, REST API, UI/UX, Figma, QA Manual, Selenium, Scrum, Linux, Network, Cyber Security, Data Analysis, Power BI, Excel)
  - Data demo presentasi (diperbarui 2026-11, lihat DECISIONS): 4 klien, 22 talent (20 lengkap & masuk pool), 8 job requirement, kandidat di setiap tahap, 5 placement aktif + 1 selesai
- **Selesai jika:** `php artisan migrate:fresh --seed` sukses, dan test relasi model lulus.

### Fase 2 — Auth & Otorisasi
- Endpoint Auth & Akun (bagian 7), middleware `role`, Policies dasar, R1–R3.
- **Selesai jika:** test login/register/logout/me, akun nonaktif ditolak, dan akses lintas role ditolak.

### Fase 3 — Profil Talent, Kompetensi, CV, Talent Pool
- R4–R6, endpoint FR-03, FR-04, FR-06.
- **Selesai jika:** profil lengkap otomatis masuk pool; CV PDF bisa digenerate & diunduh.

### Fase 4 — Job Requirement, Rekomendasi, Screening, Progres
- R7–R13, endpoint FR-05, FR-07, FR-08, FR-09, Observer progres.
- **Selesai jika:** test skor rekomendasi (kasus 0%, sebagian, 100%), screening ganda ditolak, log progres & indikator SLA benar.

### Fase 5 — Pengajuan, Interview, Evaluasi, Placement
- R14–R20.
- **Selesai jika:** test alur penuh *job → screening → pengajuan → interview → evaluasi → placement* lulus, termasuk penolakan di tiap tahap dan status_req menjadi `terpenuhi`.

### Fase 6 — Operasional
- R21–R24 (cuti dua approver, shift massal, monitoring, keluhan).
- **Selesai jika:** test semua kombinasi approval cuti & tumpang tindih tanggal lulus.

### Fase 7 — Dokumen & Tanda Tangan
- R25–R28.
- **Selesai jika:** upload/download terproteksi, generate PDF quotation, alur multi-penandatangan lulus.

### Fase 8 — Notifikasi & Scheduler
- R29–R31, queue `database`, mail driver `log`.
- **Selesai jika:** event membuat notifikasi yang benar; command scheduler bisa dijalankan manual (`php artisan schedule:test`) dan diuji.

### Fase 9 — Dashboard
- R32.
- **Selesai jika:** angka agregasi sesuai data seeder (test dengan data terkontrol).

### Fase 10 — Dokumentasi API & Handover
- Generate dokumentasi Scribe (`php artisan scribe:generate`), pastikan semua endpoint punya contoh request/response.
- Export koleksi Postman/OpenAPI untuk tim frontend.
- `README.md` backend: cara install, akun demo, cara menjalankan queue & scheduler.
- **Selesai jika:** dokumentasi bisa dibuka di `/docs` dan file OpenAPI tersedia.
- Catatan: hasil generate tidak di-commit; cara membuat ulang, queue, dan scheduler ada di `README.md`, keputusan contoh respons di DECISIONS.md.

---

## 10. Definition of Done (seluruh backend)
- [ ] `php artisan migrate:fresh --seed` berjalan tanpa error
- [ ] Semua test lulus
- [ ] Semua 20 FR punya endpoint yang berfungsi (FR-20 lewat dashboard)
- [ ] Setiap role hanya bisa mengakses datanya sendiri (scoping diuji)
- [ ] Dokumentasi API (Scribe) lengkap dan bisa dipakai tim frontend
- [ ] `DECISIONS.md` mencatat semua keputusan di luar dokumen ini

---

## 11. Selisih dengan ERD (harus diperbarui juga di dokumen laporan)

| Perubahan | Alasan |
|---|---|
| Semua tabel diberi `created_at` & `updated_at` | Standar Laravel, berguna untuk audit |
| `jadwal_interview.tanggal_interview` jadi **date** dan `waktu_interview` jadi **time** | Menghapus redundansi datetime + string waktu yang sebelumnya tumpang tindih |
| `pengajuan_kandidat.status_pengajuan` punya nilai `lanjut_interview` | Kebutuhan FR-11 (interview hanya untuk kandidat yang dinyatakan lanjut oleh klien) |
| Tabel `riwayat_pendidikan`, `pengalaman_kerja_talent`, `sertifikat_talent`, `bahasa`, `talent_bahasa` dan 13 kolom profil di `talent` (bagian 4.2, "Profil talent") | Data profil & CV talent yang lebih rinci (SCRUM-8). Lama pengalaman untuk R9 dihitung dari `pengalaman_kerja_talent`, jadi kolom `lama_pengalaman_tahun` tidak diperlukan |
| Tidak memakai spatie/laravel-permission | Role cukup dari `users.role` (sesuai ERD) |
| `shifting_talent.status_shift` (`aktif`/`dibatalkan`, default `aktif`) | Revisi bimbingan 2026-11-05: jadwal shift tidak dihapus, hanya dibatalkan |
| `dokumen.id_job_req` (FK job_requirement) & `dokumen.id_pengajuan` (FK pengajuan_kandidat), nullable | Revisi 2026-11-06: dokumen kerja sama berurutan per permintaan & Kontrak Kerja per kandidat (R34–R36); ERD & class diagram perlu diperbarui |
| `job_requirement.gaji_min` & `gaji_maks` (nullable) | Revisi bimbingan 2026-11-05: kisaran gaji yang ditawarkan klien; ERD & class diagram perlu diperbarui |
| `pengajuan_cuti.id_approver_manager` & `status_approval_manager` tidak dipakai lagi (kolom tetap ada) | Revisi bimbingan 2026-11-05: keputusan cuti hanya oleh HR Client; ERD & activity diagram cuti perlu diperbarui |
