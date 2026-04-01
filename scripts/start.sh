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

# Start Laravel Queue Worker in the background
# We use --max-jobs and --max-time to prevent memory leaks on Render's 512MB RAM free tier
echo "Starting queue worker..."
php artisan queue:work --sleep=3 --tries=3 --max-jobs=10 --max-time=3600 &

# Start Apache in the foreground
echo "Starting Apache server..."
apache2-foreground
