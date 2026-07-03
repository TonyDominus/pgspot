#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/pgspot}"
BRANCH="${BRANCH:-main}"

echo "==> Deploy PG Spot in ${APP_DIR}"

cd "${APP_DIR}"

echo "==> Git pull"
git fetch origin
git checkout "${BRANCH}"
git pull origin "${BRANCH}"

echo "==> Composer"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> NPM build"
npm ci
npm run build

echo "==> Laravel"
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link

echo "==> Permessi"
chown -R www-data:www-data storage bootstrap/cache

echo "==> Deploy completato"
