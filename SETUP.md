# Setup Intellisys Talent Hub

Panduan menyiapkan project di laptop masing-masing (Windows) sampai siap mengerjakan FR.

**Posisi saat ini:** Fase 0–2 di [BACKEND_SPEC.md](BACKEND_SPEC.md) selesai.

| Bagian | Sudah ada |
|---|---|
| Backend (repo ini) | Database lengkap (23 tabel), model, factory, seeder, auth & akun (FR-01, FR-02) |
| Frontend (repo terpisah, lihat langkah 4) | Halaman login yang tersambung ke backend, menu sesuai role (halaman lain masih mockup) |

> Repo ini sekarang **khusus backend** (kode Laravel ada langsung di root repo). Frontend dipindah ke repo sendiri.
> Sudah pernah clone versi lama yang masih punya folder `backend/` dan `frontend/`? Ikuti [Pindah dari struktur lama](#pindah-dari-struktur-lama) di bagian bawah.

---

## 1. Siapkan software

| Software | Catatan |
|---|---|
| Git | |
| [Laragon](https://laragon.org/) | Untuk MySQL 8 dan PHP ≥ 8.2. Spec mewajibkan **MySQL 8**. |
| Composer 2 | |
| Node.js ≥ 18 | |

Ekstensi PHP yang harus aktif (Laragon: menu **PHP → Extensions**): `pdo_mysql`, `pdo_sqlite`, `mbstring`, `openssl`, `fileinfo`, `zip`.

> **Punya XAMPP juga?** Matikan MySQL & Apache XAMPP sebelum menyalakan Laragon. Keduanya memakai port 3306 & 80, dan XAMPP berisi MariaDB, bukan MySQL 8.

Agar commit tercatat atas nama sendiri (cukup sekali):

```
git config --global user.name "Nama Kamu"
git config --global user.email "email-github-kamu@gmail.com"
```

## 2. Ambil kode terbaru

Belum pernah clone:

```
git clone https://github.com/Randy-ano/Capstone_Kelompok14.git
cd Capstone_Kelompok14
```

Sudah pernah clone:

```
git checkout main
git pull origin main
```

## 3. Setup backend

Semua perintah di bawah dijalankan di root repo (folder `Capstone_Kelompok14`).

```
composer install
copy .env.example .env
php artisan key:generate
```

1. Nyalakan Laragon (**Start All**).
2. Buat database **`intellisys_talent_hub`** (charset `utf8mb4`) lewat tombol **Database** di Laragon (HeidiSQL).
3. Isi database lalu jalankan test:

```
php artisan migrate:fresh --seed
php artisan test
```

Hasil test harus **semua passed** (saat Fase 2: 134 passed). Kalau ada yang gagal, jangan lanjut dulu, kabari di grup.

Test memakai SQLite in-memory, jadi tidak mengubah data di MySQL.

Jalankan backend:

```
php artisan serve
```

Cek http://localhost:8000/api/v1/health → harus `{"status":"ok"}`.

## 4. Setup frontend

Frontend ada di **repo terpisah** (link menyusul dari Randy). Clone ke folder lain, **bukan** di dalam folder repo backend. Lalu di terminal kedua (backend tetap berjalan):

```
git clone <URL repo frontend>
cd <nama folder repo frontend>
npm install
npm run dev
```

Frontend menghubungi backend di `http://localhost:8000/api/v1` (bisa diubah lewat `VITE_API_URL`, lihat `.env.example` di repo frontend).

Buka http://localhost:5173 lalu login dengan akun demo. Password semuanya **`password`**.

| Email | Role |
|---|---|
| manager@intellisys.test | Manager |
| am@intellisys.test | Account Manager |
| ts@intellisys.test | Talent Support |
| hr@intellisys.test | HR Client |
| talent@intellisys.test | Talent |

Selain itu ada 19 talent tambahan: `talent01@intellisys.test` s.d. `talent19@intellisys.test` (password sama).

## 5. Mulai kerjakan FR

Buat branch sendiri dari `main`. **Jangan commit langsung ke `main`.**

```
git checkout main
git pull origin main
git checkout -b fitur/nama-singkat
```

- Nama branch: `<jenis>/<nama-singkat>`, contoh `fitur/profil-talent`, `perbaikan/login-error`.
- Format pesan commit: `<jenis>: <deskripsi singkat dalam Bahasa Indonesia>`, contoh `fitur: endpoint profil talent`. Jangan pakai kode Jira (`SCRUM-xx`).

| Jenis | Dipakai saat | Contoh |
|---|---|---|
| `fitur` | menambah fitur/halaman/endpoint baru | `fitur: halaman talent pool dengan filter kompetensi` |
| `perbaikan` | memperbaiki bug | `perbaikan: badge status tidak muncul di layar HP` |
| `pembaruan` | mengubah/menyempurnakan yang sudah ada | `pembaruan: rapikan header dan hapus menu mockup` |
| `test` | hanya menambah/mengubah test | `test: skenario filter kompetensi talent pool` |
| `dokumen` | README, spec, DECISIONS, black box | `dokumen: catat keputusan rumus skor rekomendasi` |

- Selesai → `git push origin <nama-branch>`, lalu buat **Pull Request** ke `main`.
- Sebelum membuat PR, ambil perubahan terbaru dari `main` dan pastikan `php artisan test` tetap hijau.

## 6. Aturan tim

1. **Baca [BACKEND_SPEC.md](BACKEND_SPEC.md)**: aturan bisnis (R1–R32) dan daftar endpoint per FR. Keputusan di luar spec dicatat di [DECISIONS.md](DECISIONS.md); tambahkan keputusanmu di sana juga.
2. **Skema database dikunci.** Jangan tambah/ubah migration tanpa persetujuan tim (sudah sesuai ERD yang disetujui dosen).
3. **Logika bisnis di `app/Services`.** Controller hanya: FormRequest → Service → API Resource. Contoh: `AuthService` + `AuthController`.
4. **Setiap endpoint wajib punya test**: sukses, 422 (validasi gagal), 403 (role lain), dan 403/404 (data milik pihak lain).
5. **Tidak perlu menunggu FR orang lain.** Semua tabel punya factory, jadi data pendukung bisa dibuat langsung di test, misalnya `Placement::factory()->create()`.
6. **Helper test** (di `tests/Pest.php`):
   - `loginSebagai(Role::TalentSupport)` — buat user beserta profilnya lalu login.
   - `buatUser(Role::HrClient)` — buat user beserta profil tanpa login.
   - `roleSelain(Role::Manager)` — dataset semua role lain, untuk test 403.
   - Contoh pemakaian: `tests/Feature/Akun/PegawaiTest.php`.
7. **Batasi akses route** dengan middleware `role:`, contoh `Route::middleware('role:talent_support,manager')`. Untuk data per klien/talent, pakai Policy dan helper di model `User`: `isInternal()`, `idKlien()`, `profil()`.
8. **Jangan pakai kata "AI"** di nama class, endpoint, response, maupun komentar. Rekomendasi kandidat adalah pencocokan berbasis aturan.
9. **File bersama** (`routes/api.php`, `bootstrap/app.php`, `tests/Pest.php`, `app/Models/User.php`): tambahkan bagianmu dalam blok sendiri dengan komentar nama FR, agar konflik merge mudah diselesaikan.
10. Pesan error/sukses ke pengguna dalam **Bahasa Indonesia**.

## Kalau ada masalah

| Gejala | Penyebab & solusi |
|---|---|
| `caching_sha2_password could not be loaded` saat memakai perintah `mysql` | Perintah `mysql` yang terpanggil milik XAMPP. Buat database lewat HeidiSQL di Laragon. |
| `could not find driver` | Ekstensi `pdo_mysql` / `pdo_sqlite` belum aktif. |
| `Connection refused` saat migrate | MySQL Laragon belum menyala, atau port 3306 masih dipakai XAMPP. |
| Frontend: "Tidak dapat terhubung ke server" | `php artisan serve` belum dijalankan. |
| Frontend: error CORS di console browser | Buka frontend lewat `http://localhost:5173` (bukan `127.0.0.1` atau port lain). |

## Pindah dari struktur lama

Untuk yang sudah clone repo ini **sebelum** dipisah (masih ada folder `backend/` dan `frontend/`):

1. Simpan pekerjaanmu dulu: commit di branch-mu (`git add -A` lalu `git commit`).
2. Ambil struktur baru:
   ```
   git checkout main
   git pull origin main
   ```
   File Laravel sekarang ada di root repo. Folder `backend/` mungkin masih tersisa, tapi isinya hanya file yang tidak dilacak git (`vendor/`, `.env`, `storage/`, dsb.).
3. Pindahkan `.env` lamamu ke root repo (`backend\.env` → `.env`), lalu **hapus folder `backend/` dan `frontend/` yang tersisa**.
4. Di root repo jalankan `composer install`, lalu `php artisan test` untuk memastikan semuanya hijau.
5. Gabungkan struktur baru ke branch-mu: `git checkout nama-kamu` lalu `git merge main`. Git mengenali file yang pindah folder, jadi perubahanmu di `backend/...` ikut terbawa ke lokasi baru. Kalau ada konflik, tanya di grup.
6. Untuk frontend, clone repo frontend yang baru (lihat langkah 4) di folder terpisah.
