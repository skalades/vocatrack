#!/bin/bash
# ==============================================================================
# Vocatrack Production Deployment Script
# Digunakan untuk automated pull & reload di /www/wwwroot/vocatrack.nadirlabs.net
# ==============================================================================

# CATATAN: skrip ini adalah cara deploy lama (in-place, ada downtime, tanpa rollback).
# Gunakan scripts/deploy-release.sh setelah migrasi struktur server — lihat RENCANA/RUNBOOK-OPERASIONAL.md.

set -e

# Jika ada langkah yang gagal, jangan biarkan situs tertahan di mode pemeliharaan.
trap 'echo "❌ Deploy gagal — menonaktifkan mode pemeliharaan."; php artisan up || true' ERR

echo "🚀 [1/7] Mengaktifkan Mode Pemeliharaan..."
php artisan down --render="errors::503" --retry=60 || true

echo "📥 [2/7] Menarik Kode Terbaru dari GitHub (branch: main)..."
git fetch origin main
git reset --hard origin/main

echo "📦 [3/7] Menginstal Dependensi PHP (Production No-Dev)..."
composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader

if command -v npm &> /dev/null; then
    echo "🎨 [4/7] Terdeteksi NPM: Mengompilasi Asset Frontend (Vite)..."
    npm install --no-audit --no-fund
    npm run build
else
    echo "⚠️  [4/7] NPM tidak terpasang di server. Pastikan folder public/build sudah diunggah jika ada perubahan asset."
fi

echo "🗄️  [5/7] Cadangan pra-deploy lalu Migrasi Database..."
php artisan vokatrack:backup --prefix=pre_deploy_vokatrack --no-prune
php artisan migrate --force
# Pindahkan berkas pribadi siswa lama dari disk publik ke disk privat (idempoten)
php artisan vokatrack:migrate-private-files

echo "⚡ [6/7] Mengoptimalkan Cache Laravel (Config, Route, View, Event)..."
php artisan optimize:clear
php artisan optimize
php artisan view:cache
php artisan event:cache
php artisan storage:link --quiet || true
php artisan vokatrack:sync-pwa || true

echo "🔒 [7/7] Menyesuaikan Izin File (Ownership & Permissions)..."
chown -R www:www storage bootstrap/cache public/build 2>/dev/null || true
chmod -R 775 storage bootstrap/cache

php artisan queue:restart || true

echo "✨ Menonaktifkan Mode Pemeliharaan..."
php artisan up

echo "=============================================================================="
echo "✅ DEPLOYMENT BERHASIL! Vocatrack sudah live dan teroptimasi."
echo "=============================================================================="
