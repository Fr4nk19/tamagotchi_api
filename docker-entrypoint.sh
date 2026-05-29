#!/bin/bash
set -e

# Default PORT if not set
export PORT=${PORT:-8080}

# Update Apache to listen on the correct PORT
sed -i "s/\${PORT}/$PORT/g" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf

# APP_KEY must be provided as an environment variable in production.
# Generate one locally with: php artisan key:generate --show
if [ -z "$APP_KEY" ]; then
    echo "ERROR: APP_KEY environment variable is not set."
    echo "Generate one locally with: php artisan key:generate --show"
    echo "Then set it in your hosting provider's environment variables."
    exit 1
fi

# Cache config and routes for production
php artisan config:cache
php artisan route:cache

# Run migrations
php artisan migrate --force

exec "$@"
