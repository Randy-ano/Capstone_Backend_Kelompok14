# Deploy Produksi — Backend di Railway

Panduan memasang backend Laravel ini di [Railway](https://railway.com) bersama database MySQL, lalu
menyambungkannya dengan frontend di Vercel (lihat bagian "Deploy ke Vercel" di README repo frontend).

```
Railway project
├── MySQL        database (template MySQL Railway)
├── app          backend Laravel: API publik + Volume /app/storage/app (semua file unggahan & PDF)
└── cron         menjalankan scheduler Laravel sekali tiap jam lalu berhenti
Vercel
└── frontend     React (Vite), memanggil https://<domain-app>/api/v1
```

Tidak ada service worker antrean: produksi memakai `QUEUE_CONNECTION=sync`. Email notifikasi dikirim saat itu
juga, dan file yang dibuat selama request langsung tersimpan di volume service app. Pilihan ini juga menghemat kredit.

## Isi repo yang dipakai

| File | Fungsi |
|---|---|
| `railway/init-app.sh` | Dijalankan setiap container app start: migrate (atau reset data demo jika `SEED_DEMO=true`), `storage:link`, lalu cache config/route/view/event. |
| `railway/run-cron.sh` | Menjalankan `php artisan schedule:run` satu kali, untuk service cron. |
| `.env.production.example` | Daftar semua variabel Railway beserta penjelasannya. |
| `routes/console.php` | Jadwal pengingat harian (07.xx WIB, sekali per hari). |

> **Kenapa `init-app.sh` dipasang di Start Command, bukan Pre-Deploy Command?**
> Menurut [dokumentasi Railway](https://docs.railway.com/guides/pre-deploy-command), pre-deploy berjalan di
> container terpisah: *"Changes to the filesystem are not persisted and volumes are not mounted."* Seed demo
> membuat file PDF (CV, dokumen, sertifikat, tanda tangan) yang harus masuk ke volume, dan hasil `config:cache`
> juga harus ada di container yang melayani request. Karena itu skrip dijalankan di awal start command, sebelum
> web server dinyalakan.

## 1. Siapkan dari laptop

1. Pastikan branch yang akan dideploy sudah di-push ke GitHub (biasanya `main`).
2. Buat APP_KEY. Jangan commit nilainya, cukup tempel di Railway:

   ```bash
   php artisan key:generate --show
   # base64:xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx=
   ```

## 2. Project & database

1. Railway → **New Project** → **Deploy from GitHub repo** → pilih `Capstone_Backend_Kelompok14`.
   Railway (Railpack) mengenali Laravel dari file `artisan` dan memasang PHP beserta ekstensi yang tertulis di
   `composer.json` (`pdo_mysql`, `dom`, `mbstring`, `zlib`, `fileinfo`). Ganti nama service menjadi **app**.
2. Di canvas project: **+ Create → Database → MySQL**. Biarkan namanya **MySQL**, karena nama ini dipakai
   reference variable `${{MySQL.MYSQL_URL}}`.

## 3. Service app

### Variables

Buka service **app → Variables → Raw Editor**, tempel isi `.env.production.example`, lalu ubah:

| Variabel | Isi |
|---|---|
| `APP_KEY` | hasil `php artisan key:generate --show` |
| `FRONTEND_URL` | domain Vercel, mis. `https://intellisys-talent-hub.vercel.app` (tanpa `/` di akhir). Boleh beberapa, dipisah koma. Bila belum ada, isi sementara lalu ubah setelah frontend dideploy. |
| `SEED_DEMO` | `true` khusus untuk deploy pertama (lihat langkah 5) |

`DB_URL=${{MySQL.MYSQL_URL}}` dan `APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}` dibiarkan apa adanya; Railway
mengisi nilainya sendiri.

### Volume

Klik kanan service **app → Attach Volume**, **Mount path: `/app/storage/app`**.

Semua file aplikasi ada di bawah folder ini: `storage/app/private/{cv,dokumen,sertifikat,...}`, termasuk unggahan
pengguna, CV hasil generate, tanda tangan, dan `template.json` dokumen buatan sistem. Tanpa volume, semua file
hilang setiap kali deploy. Catatan Railway: satu service hanya boleh punya satu volume, dan service bervolume tidak
bisa memakai replica (tetap 1 instance).

### Settings

| Pengaturan | Nilai |
|---|---|
| Deploy → **Custom Start Command** | `sh railway/init-app.sh && /start-container.sh` |
| Deploy → **Pre-Deploy Command** | kosongkan |
| Deploy → **Healthcheck Path** | `/up` |
| Networking → **Public Networking** | **Generate Domain** |

`/start-container.sh` adalah start script bawaan Railpack (menyalakan FrankenPHP/Caddy). Migrasi otomatis
bawaannya dimatikan lewat `RAILPACK_SKIP_MIGRATIONS=true`, karena migrasi sudah dijalankan `init-app.sh`.

Jika log menampilkan error izin tulis di `storage/app` (*Permission denied*), tambahkan variabel
`RAILWAY_RUN_UID=0` (anjuran Railway untuk image yang tidak berjalan sebagai root saat memakai volume).

## 4. Service cron (pengingat harian)

1. **+ Create → GitHub Repo** → repo backend yang sama. Ganti nama service menjadi **cron**.
2. **Variables**: isi sama dengan service app, minimal `APP_KEY`, `APP_ENV`, `APP_DEBUG`, `APP_TIMEZONE`,
   `APP_LOCALE`, `FRONTEND_URL`, `DB_CONNECTION`, `DB_URL`, `CACHE_STORE`, `QUEUE_CONNECTION`, `LOG_CHANNEL`,
   `LOG_LEVEL`, dan `MAIL_*`. Agar tidak ditulis dua kali, variabel bisa dipindah ke **Project Settings → Shared
   Variables** lalu dibagikan ke kedua service. **Jangan** beri `SEED_DEMO` ke service cron.
3. **Settings**:
   - Deploy → **Custom Start Command**: `sh railway/run-cron.sh`
   - Deploy → **Cron Schedule**: `0 * * * *` (tiap jam, menit 00, waktu UTC)
   - Tanpa volume dan tanpa domain publik.

Cara kerja jadwal: `notifikasi:pengingat` jatuh tempo pada jam **07.xx WIB** (= 00.xx UTC) dan dijalankan **sekali
per hari** (penanda di cache database). Railway menyebut waktu cron *"can vary by a few minutes"*, jadi pengingat
tetap terkirim walaupun `schedule:run` baru berjalan pukul 07.03. Pengingat tidak membuat file, sehingga service
cron tidak membutuhkan volume.

## 5. Deploy pertama & data demo

1. Dengan `SEED_DEMO=true`, klik **Deploy**. Di tab **Logs** service app akan muncul:

   ```
   [init-app] PERINGATAN: SEED_DEMO=true. SEMUA DATA DIHAPUS lalu diisi ulang data demo.
   ...
   [init-app] Data demo selesai dibuat. Kembalikan SEED_DEMO=false agar deploy berikutnya tidak menghapus data.
   ```

2. Ubah `SEED_DEMO` menjadi `false`, lalu deploy ulang. Data tetap ada; skrip hanya menjalankan migrasi baru.
3. Cek `https://<domain-app>/up` → status 200, lalu login dari frontend memakai akun demo (password `password`):
   `manager@`, `am@`, `ts@`, `hr@`, `talent@intellisys.test`.

## ⚠️ Reset data demo (mis. sebelum presentasi)

> **`SEED_DEMO=true` MENGHAPUS SEMUA DATA**: seluruh tabel database dan semua file di `storage/app/private`
> (unggahan, CV, tanda tangan), lalu mengisinya ulang dengan data demo. Tindakan ini tidak bisa dibatalkan.
> Jangan dipakai bila sudah ada data asli yang perlu disimpan.

1. Service app → Variables → `SEED_DEMO=true` → Deploy.
2. Tunggu log `[init-app] Data demo selesai dibuat.`
3. Kembalikan `SEED_DEMO=false` → Deploy.

Reset hanya berjalan **sekali per deployment** (penanda `storage/app/.seed-demo-<RAILWAY_DEPLOYMENT_ID>` di volume).
Restart container setelah crash tidak menghapus data lagi, tetapi setiap **deploy baru** dengan `SEED_DEMO` yang masih
`true` akan mereset ulang. Karena itu langkah 3 wajib.

## 6. Email (Brevo)

Railway memblokir koneksi SMTP keluar pada paket Trial/Hobby, sehingga Gmail/Mailtrap lewat SMTP tidak bisa
dipakai. Email lupa password dan notifikasi email dikirim lewat **Brevo HTTP API** (HTTPS, paket gratis
300 email/hari) memakai `symfony/brevo-mailer`. Mailer `brevo` terdaftar di `config/mail.php`; lokal & test
tetap `MAIL_MAILER=log`.

1. **Buat API key.** Masuk ke [app.brevo.com](https://app.brevo.com) → menu profil (kanan atas) → **SMTP & API**
   → tab **API Keys** → **Generate a new API key**. Salin key (diawali `xkeysib-`); key hanya tampil sekali.
   Jangan simpan key di repo, `.env.example`, atau `.env.production.example`.
2. **Verifikasi alamat pengirim.** **Senders, Domains & Dedicated IPs → Senders → Add a sender**, isi nama
   & alamat, lalu klik tautan verifikasi yang dikirim Brevo ke alamat itu. Brevo menolak email dari alamat yang
   belum diverifikasi.
3. **Isi Variables** di service app **dan** service cron (pengingat harian juga mengirim email):

   | Variabel | Isi |
   |---|---|
   | `MAIL_MAILER` | `brevo` |
   | `BREVO_API_KEY` | key dari langkah 1 |
   | `MAIL_FROM_ADDRESS` | alamat pengirim yang **sudah diverifikasi** di langkah 2 |
   | `MAIL_FROM_NAME` | `Intellisys Talent Hub` |

4. Deploy ulang, lalu coba **Lupa password** dari frontend dengan email akun yang bisa Anda buka.

Catatan:

- Email dari alamat pribadi (mis. Gmail) yang dikirim lewat Brevo tanpa domain sendiri sering masuk folder
  **Spam/Promosi**. Minta penerima memeriksa folder tersebut dan menandai "Bukan spam". Untuk mengurangi hal ini,
  autentikasi domain sendiri di Brevo (**Domains → Add a domain**, tambahkan record DKIM/DMARC).
- Jika pengiriman gagal (API key salah, alamat pengirim belum diverifikasi, kuota harian habis), lupa password
  tetap menampilkan pesan umum yang sama ("Jika email terdaftar, tautan reset sudah dikirim.") dan detail error
  dicatat di tab **Logs** (`Gagal mengirim email reset password.`). Notifikasi email yang gagal ditandai `gagal`
  dan proses yang memicunya (mis. pengingat ke penerima lain) tetap berjalan.
- Cadangan tanpa Brevo: `MAIL_MAILER=log` (email tidak dikirim, isinya termasuk tautan reset tampil di tab
  **Logs**; butuh `LOG_LEVEL=debug`).
- Tautan reset password memakai origin pertama di `FRONTEND_URL`.

## 7. Sambungkan frontend

1. Deploy frontend di Vercel dengan `VITE_API_URL=https://<domain-app>/api/v1`.
2. Masukkan domain Vercel ke `FRONTEND_URL` service app (dan cron), lalu deploy ulang. CORS hanya mengizinkan
   origin yang tertulis di sana, ditambah `http://localhost:5173` untuk pengembangan.

## Pemecahan masalah

| Gejala | Penyebab & solusi |
|---|---|
| Browser: *blocked by CORS policy* | `FRONTEND_URL` harus sama persis dengan origin di address bar (skema `https://`, tanpa `/` di akhir). Setelah diubah, deploy ulang agar `config:cache` diperbarui. |
| 500 *No application encryption key* | `APP_KEY` kosong/salah. Isi dengan hasil `php artisan key:generate --show` (termasuk awalan `base64:`). |
| *SQLSTATE … Connection refused* / *getaddrinfo* | Nama service database bukan `MySQL`. Sesuaikan `DB_URL=${{<nama service>.MYSQL_URL}}`. |
| File unggahan / PDF hilang setelah deploy | Volume belum dipasang atau mount path bukan `/app/storage/app`. |
| Tautan pagination/unduhan memakai `http://` | Proxy dipercaya lewat `trustProxies(at: '*')` di `bootstrap/app.php`. Pastikan akses lewat domain Railway (https). |
| Pengingat tidak terkirim | Cek Logs service cron sekitar 00.00 UTC. Variabel database & `CACHE_STORE=database` harus sama dengan service app. |
| Email lupa password tidak datang | Cek folder Spam. Cek Logs: `Gagal mengirim email reset password.` berarti `BREVO_API_KEY` salah, `MAIL_FROM_ADDRESS` belum diverifikasi di Brevo, atau kuota harian habis. Pastikan `MAIL_MAILER=brevo` dan deploy ulang setelah mengubah variabel. |
| Dokumentasi `/docs` tidak ada | Scribe adalah dependensi dev; dokumentasi API dibuat di laptop (`php artisan scribe:generate`). |
