#!/bin/sh
set -e

cd /var/www

# Ensure the public storage symlink exists (idempotent).
php artisan storage:link 2>/dev/null || true

# Best-effort production caches. Never block boot if a cache step fails
# (e.g. route caching with closures), the app still runs uncached.
php artisan config:cache 2>/dev/null || true
php artisan view:cache 2>/dev/null || true

# Opt-in migrations on boot. Disabled by default; enable per-environment.
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force || true
fi

exec "$@"
