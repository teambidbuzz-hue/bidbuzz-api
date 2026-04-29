#!/bin/sh
set -e

if [ -d /var/www/storage-init ] && [ ! "$(ls -A /var/www/storage 2>/dev/null)" ]; then
  echo "Initializing storage directory..."
  mkdir -p /var/www/storage
  cp -R /var/www/storage-init/. /var/www/storage
fi

mkdir -p /var/www/storage /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

rm -rf /var/www/storage-init

if [ "${LARAVEL_CACHE_CONFIG:-false}" = "true" ]; then
  gosu www-data php artisan config:cache
  gosu www-data php artisan route:cache
fi

exec gosu www-data "$@"
