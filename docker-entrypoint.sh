#!/bin/bash
set -e

echo "==> Starting Tamagotchi API container..."

# Default PORT if not set
export PORT=${PORT:-8080}
echo "==> PORT=$PORT"

# Update Apache to listen on the correct PORT
sed -i "s/\${PORT}/$PORT/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf
echo "==> Apache configured to listen on port $PORT"

# Verify APP_KEY is set
if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY environment variable is not set."
    echo "Generate one locally with: php artisan key:generate --show"
    echo "Then set it in your hosting provider's environment variables."
    exit 1
fi
echo "==> APP_KEY is set"

# Clear any stale caches first
echo "==> Clearing stale caches..."
php artisan config:clear || true
php artisan route:clear || true
php artisan cache:clear || true

# Cache config and routes for production
echo "==> Caching config..."
php artisan config:cache
echo "==> Caching routes..."
php artisan route:cache

# Run migrations - don't kill container if DB isn't ready yet
echo "==> Running migrations..."
php artisan migrate --force || echo "WARNING: Migrations failed, container will continue."

echo "==> Starting Apache on port $PORT..."
exec "$@"
