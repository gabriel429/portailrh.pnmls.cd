#!/bin/bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"

export PATH="/opt/alt/alt-nodejs22/root/usr/bin:/opt/alt/alt-nodejs20/root/usr/bin:/opt/alt/alt-nodejs18/root/usr/bin:/usr/local/bin:/usr/bin:/bin:$PATH"
export NODE_OPTIONS="--max-old-space-size=768"
export npm_config_loglevel="error"

cd "$ROOT_DIR"

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