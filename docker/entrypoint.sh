#!/bin/sh
set -e

mkdir -p /var/log/supervisor

if [ ! -f /var/www/html/database/database.sqlite ]; then
    echo "Initializing SQLite database..."
    touch /var/www/html/database/database.sqlite
    chown www-data:www-data /var/www/html/database/database.sqlite
    chmod 664 /var/www/html/database/database.sqlite
fi

cd /var/www/html

if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "WARNING: APP_KEY not set. Generating temporary key..."
    php artisan key:generate --force
fi

echo "Running migrations..."
php artisan migrate --force

echo "Caching configuration..."
php artisan config:cache
php artisan view:cache

chown -R www-data:www-data storage bootstrap/cache database
chmod -R 775 storage bootstrap/cache database

echo "Starting application..."
exec "$@"
