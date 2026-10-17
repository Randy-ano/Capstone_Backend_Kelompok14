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
