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

# Start storage symlink (idempotent — safe to run every deploy)
echo "Linking storage..."
php artisan storage:link || true

# Start Laravel Queue Worker in the background
# --sleep=3      : wait 3s between polls when queue is empty
# --tries=3      : retry a failing job up to 3 times before marking as failed
# --max-jobs=100 : restart worker after 100 jobs to prevent memory leaks
# --max-time=3600: restart worker after 1 hour as a safety net
echo "Starting queue worker..."
php artisan queue:work --sleep=3 --tries=3 --max-jobs=100 --max-time=3600 &
QUEUE_PID=$!

# Brief pause then confirm the worker process is still running
sleep 2
if ! kill -0 "$QUEUE_PID" 2>/dev/null; then
    echo "⚠️  WARNING: Queue worker failed to start or exited immediately." >&2
    echo "⚠️  AI scoring jobs will not be processed until the worker is restarted." >&2
fi

# Start Apache in the foreground
echo "Starting Apache server..."
apache2-foreground
