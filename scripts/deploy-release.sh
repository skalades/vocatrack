#!/usr/bin/env bash
# ==============================================================================
# VokaTrack — Deploy berbasis rilis (zero-downtime + rollback)
#
# Struktur di server:
#   $DEPLOY_BASE/
#     releases/20261116_020000/   <- satu folder per rilis
#     shared/.env                 <- dipakai bersama semua rilis
#     shared/storage/             <- berkas siswa, backup, log, session
#     current -> releases/...     <- symlink yang dilayani web server
#
# Document root web server: $DEPLOY_BASE/current/public
#
# Pemakaian:
#   scripts/deploy-release.sh deploy      # rilis baru dari $DEPLOY_BRANCH
#   scripts/deploy-release.sh rollback    # kembali ke rilis sebelumnya
#   scripts/deploy-release.sh list        # tampilkan daftar rilis
#
# Konfigurasi (env var atau berkas $DEPLOY_BASE/deploy.env):
#   DEPLOY_BASE        default /www/wwwroot/vocatrack
#   DEPLOY_REPO        URL git (wajib saat deploy pertama, mis. git@github.com:skalades/vocatrack.git)
#   DEPLOY_BRANCH      default main
#   KEEP_RELEASES      default 5
#   PHP_BIN            default php
#   COMPOSER_BIN       default composer
#   PHP_FPM_RELOAD     perintah reload PHP-FPM (mis. "/etc/init.d/php-fpm-82 reload")
#   HEALTHCHECK_URL    mis. https://vocatrack.nadirlabs.net/up — gagal => rollback otomatis
#   WEB_USER           default www
# ==============================================================================

set -Eeuo pipefail

DEPLOY_BASE="${DEPLOY_BASE:-/www/wwwroot/vocatrack}"
[ -f "$DEPLOY_BASE/deploy.env" ] && source "$DEPLOY_BASE/deploy.env"

DEPLOY_BRANCH="${DEPLOY_BRANCH:-main}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
PHP_FPM_RELOAD="${PHP_FPM_RELOAD:-}"
HEALTHCHECK_URL="${HEALTHCHECK_URL:-}"
WEB_USER="${WEB_USER:-www}"

RELEASES_DIR="$DEPLOY_BASE/releases"
SHARED_DIR="$DEPLOY_BASE/shared"
CURRENT_LINK="$DEPLOY_BASE/current"

log()  { echo -e "\033[1;34m[deploy]\033[0m $*"; }
warn() { echo -e "\033[1;33m[deploy]\033[0m $*"; }
fail() { echo -e "\033[1;31m[deploy]\033[0m $*" >&2; exit 1; }

current_release() {
    [ -L "$CURRENT_LINK" ] && basename "$(readlink -f "$CURRENT_LINK")" || true
}

# Ganti symlink secara atomik (rename(2)), tidak pernah ada jeda tanpa "current".
switch_to() {
    local target="$1"
    ln -sfn "$RELEASES_DIR/$target" "$DEPLOY_BASE/current.tmp"
    mv -Tf "$DEPLOY_BASE/current.tmp" "$CURRENT_LINK"
    log "Symlink current -> releases/$target"
}

reload_runtime() {
    if [ -n "$PHP_FPM_RELOAD" ]; then
        log "Reload PHP-FPM (bersihkan OPcache)..."
        eval "$PHP_FPM_RELOAD" || warn "Reload PHP-FPM gagal — jalankan manual."
    else
        warn "PHP_FPM_RELOAD belum diatur; OPcache mungkin masih memuat kode lama."
    fi
    (cd "$CURRENT_LINK" && "$PHP_BIN" artisan queue:restart) || true
}

healthcheck() {
    [ -z "$HEALTHCHECK_URL" ] && { warn "HEALTHCHECK_URL belum diatur; health check dilewati."; return 0; }
    log "Health check $HEALTHCHECK_URL ..."
    for i in 1 2 3 4 5; do
        if curl -fsS --max-time 10 -o /dev/null "$HEALTHCHECK_URL"; then
            log "Health check OK."
            return 0
        fi
        sleep 3
    done
    return 1
}

prepare_shared() {
    mkdir -p "$RELEASES_DIR" "$SHARED_DIR"
    [ -f "$SHARED_DIR/.env" ] || fail "Berkas $SHARED_DIR/.env belum ada. Salin .env produksi ke sana dulu."
    mkdir -p "$SHARED_DIR/storage/app/public" "$SHARED_DIR/storage/app/private" "$SHARED_DIR/storage/app/backups" \
             "$SHARED_DIR/storage/framework/cache/data" "$SHARED_DIR/storage/framework/sessions" \
             "$SHARED_DIR/storage/framework/views" "$SHARED_DIR/storage/logs"
}

cmd_deploy() {
    prepare_shared

    local repo="${DEPLOY_REPO:-}"
    local previous; previous="$(current_release)"
    if [ -z "$repo" ] && [ -n "$previous" ]; then
        repo="$(git -C "$RELEASES_DIR/$previous" remote get-url origin 2>/dev/null || true)"
    fi
    [ -n "$repo" ] || fail "DEPLOY_REPO belum diatur."

    local release; release="$(date +%Y%m%d_%H%M%S)"
    local dir="$RELEASES_DIR/$release"

    # Jika gagal sebelum switch, buang folder rilis — situs live tidak tersentuh.
    trap 'warn "Deploy gagal sebelum switch. Rilis $release dibuang; situs tetap di ${previous:-<kosong>}."; rm -rf "$dir"' ERR

    log "[1/7] Clone $DEPLOY_BRANCH -> releases/$release"
    git clone --depth 1 --branch "$DEPLOY_BRANCH" "$repo" "$dir"

    log "[2/7] Hubungkan .env & storage bersama"
    ln -sfn "$SHARED_DIR/.env" "$dir/.env"
    rm -rf "$dir/storage"
    ln -sfn "$SHARED_DIR/storage" "$dir/storage"

    log "[3/7] Composer install (no-dev)"
    (cd "$dir" && "$COMPOSER_BIN" install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress)

    if command -v npm >/dev/null 2>&1; then
        log "[4/7] Build aset frontend"
        (cd "$dir" && npm ci --no-audit --no-fund && npm run build && rm -rf node_modules)
    else
        warn "[4/7] npm tidak ada — memakai public/build yang ter-commit."
    fi

    log "[5/7] Cadangan pra-deploy + migrasi database"
    (cd "$dir" && "$PHP_BIN" artisan vokatrack:backup --prefix=pre_deploy_vokatrack --no-prune)
    (cd "$dir" && "$PHP_BIN" artisan migrate --force)
    (cd "$dir" && "$PHP_BIN" artisan vokatrack:migrate-private-files)

    log "[6/7] Cache konfigurasi, rute, view, event"
    (cd "$dir" && "$PHP_BIN" artisan optimize && "$PHP_BIN" artisan view:cache && "$PHP_BIN" artisan event:cache)
    (cd "$dir" && "$PHP_BIN" artisan storage:link --quiet) || true
    chown -R "$WEB_USER:$WEB_USER" "$dir" "$SHARED_DIR/storage" 2>/dev/null || true

    trap - ERR

    log "[7/7] Aktifkan rilis"
    switch_to "$release"
    reload_runtime

    if ! healthcheck; then
        if [ -n "$previous" ]; then
            warn "Health check GAGAL — rollback otomatis ke $previous."
            switch_to "$previous"
            reload_runtime
            fail "Rilis $release dibatalkan. Periksa log: $SHARED_DIR/storage/logs/ (migrasi TIDAK di-rollback)."
        fi
        fail "Health check gagal dan tidak ada rilis sebelumnya untuk rollback."
    fi

    cleanup_releases
    log "✅ Deploy selesai: $release (sebelumnya: ${previous:-tidak ada})"
}

cmd_rollback() {
    local current; current="$(current_release)"
    [ -n "$current" ] || fail "Belum ada rilis aktif."

    local target
    target="$(ls -1 "$RELEASES_DIR" | sort | awk -v cur="$current" '$0 < cur' | tail -n 1)"
    [ -n "$target" ] || fail "Tidak ada rilis sebelum $current."

    warn "Rollback kode $current -> $target. Migrasi database TIDAK di-rollback."
    switch_to "$target"
    reload_runtime
    healthcheck || warn "Health check gagal setelah rollback — periksa segera."
    log "✅ Rollback selesai. Rilis aktif: $target"
}

cmd_list() {
    local current; current="$(current_release)"
    ls -1 "$RELEASES_DIR" 2>/dev/null | sort -r | while read -r r; do
        [ "$r" = "$current" ] && echo "* $r (aktif)" || echo "  $r"
    done
}

cleanup_releases() {
    local current; current="$(current_release)"
    ls -1 "$RELEASES_DIR" | sort -r | tail -n +"$((KEEP_RELEASES + 1))" | while read -r old; do
        [ "$old" = "$current" ] && continue
        log "Hapus rilis lama: $old"
        rm -rf "${RELEASES_DIR:?}/$old"
    done
}

case "${1:-}" in
    deploy)   cmd_deploy ;;
    rollback) cmd_rollback ;;
    list)     cmd_list ;;
    *) echo "Pemakaian: $0 {deploy|rollback|list}"; exit 1 ;;
esac
