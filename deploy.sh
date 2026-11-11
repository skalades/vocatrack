#!/bin/bash
# ==============================================================================
# Vocatrack Production Deployment Script
# Digunakan untuk automated pull & reload di /www/wwwroot/vocatrack.nadirlabs.net
# ==============================================================================

set -e

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

echo "🗄️  [5/7] Menjalankan Migrasi Database..."
php artisan migrate --force

echo "⚡ [6/7] Mengoptimalkan Cache Laravel (Config, Route, View, Event)..."
php artisan optimize:clear
php artisan optimize
php artisan view:cache
php artisan event:cache
php artisan storage:link --quiet || true

echo "🔒 [7/7] Menyesuaikan Izin File (Ownership & Permissions)..."
chown -R www:www storage bootstrap/cache public/build 2>/dev/null || true
chmod -R 775 storage bootstrap/cache

echo "✨ Menonaktifkan Mode Pemeliharaan..."
php artisan up

echo "=============================================================================="
echo "✅ DEPLOYMENT BERHASIL! Vocatrack sudah live dan teroptimasi."
echo "=============================================================================="
