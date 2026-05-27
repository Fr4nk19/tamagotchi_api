#!/bin/bash
set -e

# Default PORT if not set
export PORT=${PORT:-8080}

# Update Apache to listen on the correct PORT
sed -i "s/\${PORT}/$PORT/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Cache config and routes for production
php artisan config:cache
php artisan route:cache

# Run migrations
php artisan migrate --force

exec "$@"
