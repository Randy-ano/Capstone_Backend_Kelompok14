# DECISIONS

Catatan keputusan teknis di luar / pelengkap `BACKEND_SPEC.md`.

| Tanggal | Keputusan | Alasan |
|---|---|---|
| 2026-10-17 | Memakai **Laravel 12** (12.70), bukan versi 13. | PHP yang terpasang di mesin pengembangan (XAMPP) adalah 8.2; Laravel 13 butuh PHP ≥ 8.3. Laravel 12 adalah versi stabil terbaru yang kompatibel. Bisa di-upgrade setelah PHP diperbarui. |
| 2026-10-17 | Prefix `/api/v1` diatur lewat `apiPrefix` di `bootstrap/app.php`, bukan `Route::prefix()` di `routes/api.php`. | Satu titik konfigurasi; semua route di `routes/api.php` otomatis ber-prefix `v1`. |
| 2026-10-17 | Origin CORS dibaca dari env `FRONTEND_URL` (boleh beberapa, dipisah koma), default `http://localhost:5173`. | Origin produksi bisa ditambah tanpa mengubah kode. `supports_credentials` tetap `false` karena auth memakai Bearer token, bukan cookie. |
| 2026-10-17 | Test berjalan di **SQLite in-memory** (`phpunit.xml`), aplikasi memakai MySQL (`.env`). | Test cepat dan tidak butuh server MySQL. Konsekuensi: hindari fitur SQL khusus MySQL di query; kalau terpaksa, tambahkan test terhadap MySQL. |
| 2026-10-17 | `knuckleswtf/scribe` dipasang sebagai dependensi **dev**. | Rekomendasi resmi Scribe; dokumentasi di-generate saat development (`php artisan scribe:generate`). Ditinjau ulang di Fase 10 bila `/docs` harus tersedia di server produksi. |
| 2026-10-17 | `APP_FALLBACK_LOCALE` tetap `en`. | Belum ada file terjemahan `lang/id`; pesan validasi Bahasa Indonesia akan ditambahkan di fase yang membutuhkannya. |
| 2026-10-17 | Tabel `users` tidak punya `name`, `email_verified_at`, `remember_token` (sesuai ERD). Model `User` men-set `$rememberTokenName = ''`. | Kolom mengikuti bagian 4 persis; auth memakai token Sanctum sehingga remember token tidak diperlukan. Tabel bawaan `password_reset_tokens` & `sessions` tetap dipertahankan karena dipakai framework. |
| 2026-10-17 | Selain default kolom di database, model juga mengisi `$attributes` untuk kolom status ber-default (mis. `talent.status_kandidat = tersedia`). | Supaya enum cast langsung terisi setelah `create()` tanpa perlu `refresh()`. |
| 2026-10-17 | Jumlah talent seeder = **20 termasuk** akun demo `talent@intellisys.test` (19 talent tambahan, email `talent01..19@intellisys.test`, password `password`). 14 profil lengkap + masuk pool, 6 belum lengkap. | Tafsiran paling sederhana dari "20 talent" di Fase 1. |
| 2026-10-17 | Seeder memakai `fake()->seed(2026)`. | Data acak (kompetensi talent) sama setiap `migrate:fresh --seed`, memudahkan demo dan pengecekan. |
| 2026-10-17 | Seeder berjalan dengan `WithoutModelEvents` dan mengisi `status_kelengkapan`, `talent_pool`, serta log progres `kebutuhan_dibuat` secara langsung. Indikator SLA log seed dihitung dengan versi sederhana R13 di `JobRequirementSeeder`. | Logika otomatis R4/R5 (Fase 3) dan Observer progres + R13 resmi (Fase 4) belum ada; data seed dibuat konsisten dengan aturan tersebut agar tidak dobel saat observer aktif. |
| 2026-10-17 | 5 job requirement seed diberi SLA bervariasi: 2 `aman`, 1 `mendekati`, 1 `terlambat` (status `diajukan`), dan 1 `draft`. | Memberi data untuk demo indikator SLA dan pengingat scheduler. |
