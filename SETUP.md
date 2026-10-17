# Setup Intellisys Talent Hub

Panduan menyiapkan project di laptop masing-masing (Windows) sampai siap mengerjakan FR.

**Posisi saat ini:** Fase 0–2 di [BACKEND_SPEC.md](BACKEND_SPEC.md) selesai.

| Bagian | Sudah ada |
|---|---|
| Backend | Database lengkap (23 tabel), model, factory, seeder, auth & akun (FR-01, FR-02) |
| Frontend | Halaman login yang tersambung ke backend, menu sesuai role (halaman lain masih mockup) |

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

```
cd backend
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

Di terminal kedua (backend tetap berjalan):

```
cd frontend
npm install
npm run dev
```

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
git checkout -b nama-kamu
```

- Format pesan commit: `SCRUM-<nomor issue Jira> deskripsi singkat`, contoh `SCRUM-12 Endpoint profil talent`. Belum ada issue → `SCRUM-0`.
- Selesai → `git push origin nama-kamu`, lalu buat **Pull Request** ke `main`.
- Sebelum membuat PR, ambil perubahan terbaru dari `main` dan pastikan `php artisan test` tetap hijau.

## 6. Aturan tim

1. **Baca [BACKEND_SPEC.md](BACKEND_SPEC.md)**: aturan bisnis (R1–R32) dan daftar endpoint per FR. Keputusan di luar spec dicatat di [backend/DECISIONS.md](backend/DECISIONS.md); tambahkan keputusanmu di sana juga.
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
