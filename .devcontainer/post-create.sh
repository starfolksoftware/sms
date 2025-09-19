#!/usr/bin/env bash
set -euo pipefail

echo "[post-create] Starting setup..."

if ! command -v php >/dev/null 2>&1; then
  echo "PHP is required in the devcontainer."
  exit 1
fi

if ! command -v composer >/dev/null 2>&1; then
  echo "Composer is required in the devcontainer."
  exit 1
fi

if ! command -v node >/dev/null 2>&1; then
  echo "Node is required in the devcontainer."
  exit 1
fi

echo "[post-create] Installing PHP dependencies..."
composer install --no-interaction --prefer-dist

echo "[post-create] Installing Node dependencies..."
npm ci || npm install

echo "[post-create] Preparing environment file..."
if [ ! -f .env ]; then
  cp .env.example .env
fi

echo "[post-create] Generating app key..."
php artisan key:generate --force || true

echo "[post-create] Ensuring SQLite database exists..."
mkdir -p database
touch database/database.sqlite

echo "[post-create] Fixing permissions for storage and cache..."
chmod -R u+rwX,g+rwX storage bootstrap/cache || true

echo "[post-create] Running migrations..."
php artisan migrate --force

echo "[post-create] Caching basic config to speed up artisan..."
php artisan config:clear || true
php artisan route:clear || true
php artisan view:clear || true

echo "[post-create] Ensuring storage symlink..."
php artisan storage:link || true

echo "[post-create] Done."
