#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/pgspot}"
BRANCH="${BRANCH:-main}"
WEB_USER="${WEB_USER:-www-data}"

echo "==> Deploy PG Spot in ${APP_DIR}"

cd "${APP_DIR}"

echo "==> Permessi (prima di artisan/composer — obbligatorio)"
mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache
sudo chown -R "${USER}:${WEB_USER}" storage bootstrap/cache
sudo chmod -R ug+rwx storage bootstrap/cache

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
php artisan optimize:clear
php artisan config:cache
php artisan view:cache
php artisan pgspot:publish-robots

echo "==> Route cache disabilitato (Inertia/Ziggy — evita pagine bianche al refresh)"
php artisan route:clear || true

php artisan storage:link 2>/dev/null || true

echo "==> Permessi finali per PHP-FPM"
sudo chown -R "${WEB_USER}:${WEB_USER}" storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
if [ -L public/storage ] || [ -d public/storage ]; then
    sudo chown -h "${WEB_USER}:${WEB_USER}" public/storage 2>/dev/null || sudo chown -R "${WEB_USER}:${WEB_USER}" public/storage
fi

echo "==> Deploy completato"
