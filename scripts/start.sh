#!/bin/bash
set -e

# Cache configuration, routes, and views for optimal production performance
echo "Caching framework configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run database migrations
echo "Running migrations..."
php artisan migrate --force

# Start Apache
echo "Starting Apache server..."
apache2-foreground
