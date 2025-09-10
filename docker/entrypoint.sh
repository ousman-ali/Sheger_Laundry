#!/usr/bin/env sh
set -e

cd /var/www

# Ensure storage directories are writable
chown -R www-data:www-data storage bootstrap/cache || true
chmod -R 775 storage bootstrap/cache || true

# Create storage symlink if missing
if [ ! -L public/storage ]; then
  php artisan storage:link || true
fi

# Generate app key if missing
if [ -z "$(php -r 'echo env("APP_KEY");')" ]; then
  php artisan key:generate --force || true
fi

# Cache config/routes/views for performance
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Optionally run pending migrations
if [ "${RUN_MIGRATIONS}" = "true" ]; then
  php artisan migrate --force --no-interaction || true
fi

exec "$@"
