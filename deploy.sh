#!/bin/bash
# Pull latest code, rebuild, and restart PHP-FPM.
# Usage: APP_DIR=/var/www/us-content-engine PHP_BIN=php8.3 ./deploy.sh
set -u

APP_DIR="${APP_DIR:-/var/www/us-content-engine}"
PHP_BIN="${PHP_BIN:-php8.3}"
FPM_SERVICE="${FPM_SERVICE:-${PHP_BIN}-fpm}"
BRANCH="${BRANCH:-main}"
LOG="$APP_DIR/storage/logs/deploy.log"

log() { echo "[Deploy $(date +%Y-%m-%d_%H:%M:%S)] $*" | tee -a "$LOG"; }
fail() { log "FAIL at step: $1 — $2"; exit 1; }

log "───────────── Deploy Starting ─────────────"
cd "$APP_DIR" || fail "cd" "APP_DIR not accessible: $APP_DIR"

COMMIT_BEFORE=$(git rev-parse --short HEAD 2>/dev/null || echo "unknown")
git config --global --add safe.directory "$APP_DIR" 2>/dev/null || true

log "▶ Step 1/6: git fetch + reset to origin/$BRANCH"
git fetch origin "$BRANCH" 2>&1 | tail -5 >> "$LOG" || fail "git-fetch" "unable to fetch"
git reset --hard "origin/$BRANCH" 2>&1 | tail -3 >> "$LOG" || fail "git-reset" "reset failed"
COMMIT_AFTER=$(git rev-parse --short HEAD)
log "Commit: $COMMIT_BEFORE → $COMMIT_AFTER"

log "▶ Step 2/6: composer install"
composer install --no-dev --optimize-autoloader --no-interaction -q 2>&1 | tail -10 >> "$LOG" || fail "composer" "check composer.lock drift"

log "▶ Step 3/6: npm install + vite build"
(npm ci --silent 2>/dev/null || npm install --silent) 2>&1 | tail -5 >> "$LOG" || fail "npm-install" "dependency resolution"
rm -rf public/build
npm run build 2>&1 | tail -10 >> "$LOG" || fail "vite-build" "빌드 실패 — free -m 확인"

log "▶ Step 4/6: migrate + seed admin (idempotent) + optimize:clear"
"$PHP_BIN" artisan migrate --force 2>&1 | tail -5 >> "$LOG" || log "WARN: migrate returned non-zero"
"$PHP_BIN" artisan db:seed --force 2>&1 | tail -5 >> "$LOG"
"$PHP_BIN" artisan optimize:clear 2>&1 | tail -3 >> "$LOG"
"$PHP_BIN" artisan optimize 2>&1 | tail -3 >> "$LOG"

log "▶ Step 5/6: permissions"
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chown -R www-data:www-data "$APP_DIR/public/build" 2>/dev/null || true
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

log "▶ Step 6/6: restart $FPM_SERVICE"
systemctl restart "$FPM_SERVICE"

log "✅ DEPLOY SUCCESS: $COMMIT_BEFORE → $COMMIT_AFTER"
log "─────────────────────────────────────────"
