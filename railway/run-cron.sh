#!/bin/sh
# Service cron Railway (Cron Schedule "0 * * * *", tiap jam, waktu UTC). Menjalankan scheduler Laravel sekali
# lalu selesai, sesuai syarat cron job Railway. Pengingat harian (notifikasi:pengingat) jatuh tempo pada jam
# 07.xx WIB = 00.xx UTC dan hanya dijalankan sekali per hari, jadi tetap terpicu walaupun cron terlambat
# beberapa menit. Lihat routes/console.php dan DEPLOY.md.
set -e

php artisan schedule:run --verbose --no-interaction
