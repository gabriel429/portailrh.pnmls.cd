#!/bin/bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
LOCK_FILE="$ROOT_DIR/storage/app/frontend-build.pid"
STATUS_FILE="$ROOT_DIR/storage/app/frontend-build.status"

export PATH="/opt/alt/alt-nodejs22/root/usr/bin:/opt/alt/alt-nodejs20/root/usr/bin:/opt/alt/alt-nodejs18/root/usr/bin:/usr/local/bin:/usr/bin:/bin:$PATH"
export NODE_OPTIONS="--max-old-space-size=768"
export npm_config_loglevel="error"

cd "$ROOT_DIR"

mkdir -p "$ROOT_DIR/storage/app"

if [ -f "$LOCK_FILE" ]; then
  EXISTING_PID="$(cat "$LOCK_FILE" 2>/dev/null || true)"
  if [ -n "$EXISTING_PID" ] && kill -0 "$EXISTING_PID" 2>/dev/null; then
    echo "Build frontend deja en cours avec PID $EXISTING_PID"
    exit 20
  fi
fi

echo $$ > "$LOCK_FILE"
trap 'rm -f "$LOCK_FILE"' EXIT
echo "running" > "$STATUS_FILE"

echo "=== Verification de l environnement Node ==="
node -v
npm -v

if [ ! -x "$ROOT_DIR/node_modules/.bin/vite" ]; then
  echo "=== Installation initiale des dependances frontend ==="
  npm install --legacy-peer-deps --no-audit --no-fund
else
  echo "=== Dependances frontend deja presentes ==="
fi

echo "=== Build Vite ==="
"$ROOT_DIR/node_modules/.bin/vite" build

echo "=== Synchronisation du build ==="
node "$ROOT_DIR/scripts/sync-vite-build.mjs"

echo "=== Nettoyage final des caches Laravel ==="
php artisan optimize:clear

echo "=== Build frontend termine ==="
echo "success" > "$STATUS_FILE"