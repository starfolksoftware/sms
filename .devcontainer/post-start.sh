#!/usr/bin/env bash
set -euo pipefail

echo "[post-start] Verifying environment..."

if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate --force || true
fi

mkdir -p database
touch database/database.sqlite

chmod -R u+rwX,g+rwX storage bootstrap/cache || true

php artisan migrate --force || true

cat <<'EOT'

Ready to go! Common commands:

  - Start Laravel + Vite concurrently:
      npm run dev:full

  - Or run them separately:
      php artisan serve --host=0.0.0.0 --port=8000
      npm run dev

If the browser shows a Vite HMR warning in Codespaces, try reloading.

EOT
