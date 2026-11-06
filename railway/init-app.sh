#!/bin/sh
# Persiapan service app di Railway. Dipakai di Custom Start Command, BUKAN Pre-Deploy Command:
#   sh railway/init-app.sh && /start-container.sh
# Pre-deploy Railway berjalan di container terpisah tanpa volume dan perubahan filenya tidak disimpan,
# padahal seed demo membuat file PDF (CV, dokumen, sertifikat, tanda tangan) yang harus masuk ke volume
# /app/storage/app. Lihat DEPLOY.md.
set -e

# Volume baru masih kosong: siapkan folder yang dipakai disk "local" (private) dan "public".
mkdir -p storage/app/private storage/app/public

if [ "$SEED_DEMO" = "true" ]; then
    # Seed hanya sekali per deployment, supaya restart container (mis. setelah crash) tidak menghapus data lagi.
    PENANDA="storage/app/.seed-demo-${RAILWAY_DEPLOYMENT_ID:-lokal}"
    if [ -f "$PENANDA" ]; then
        echo "[init-app] SEED_DEMO=true, tetapi data demo sudah dibuat untuk deployment ini. Hanya migrate."
        php artisan migrate --force
    else
        echo "[init-app] PERINGATAN: SEED_DEMO=true. SEMUA DATA DIHAPUS lalu diisi ulang data demo."
        # File unggahan lama tidak lagi punya baris di database, jadi ikut dibersihkan.
        find storage/app/private -mindepth 1 -delete
        rm -f storage/app/.seed-demo-*
        php artisan migrate:fresh --seed --force
        touch "$PENANDA"
        echo "[init-app] Data demo selesai dibuat. Kembalikan SEED_DEMO=false agar deploy berikutnya tidak menghapus data."
    fi
else
    php artisan migrate --force
fi

php artisan storage:link || true

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

echo "[init-app] Selesai."
