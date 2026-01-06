#!/usr/bin/env sh
set -eu

# Ensure persistent storage directories exist and are writable (bind-mounted in docker-compose.yml).
# This helps avoid silent upload failures (avatars, etc.) when running with bind mounts.
mkdir -p /app/storage/app/public || true

# Ensure the public/storage symlink points to the mounted storage folder.
# In dev, /app/public may be bind-mounted, so we recreate the symlink on each start.
rm -rf /app/public/storage || true
ln -s /app/storage/app/public /app/public/storage || true

# Best-effort permissions (container usually runs as root in our image).
chmod -R 775 /app/storage /app/bootstrap/cache 2>/dev/null || true

# Clear Laravel caches on container start.
# This avoids stale route/config/view caches when /app/storage is mounted as a persistent volume.
#
# Disable by setting:
#   LARAVEL_CLEAR_CACHE_ON_START=false
if [ "${LARAVEL_CLEAR_CACHE_ON_START:-true}" = "true" ]; then
  if [ -f /app/artisan ]; then
    echo "[entrypoint] Clearing Laravel cache (optimize:clear)..."
    # Don't block startup if cache clear fails for any reason.
    php /app/artisan optimize:clear --no-ansi || true
  fi
fi

# Hand off to the original FrankenPHP/Laravel entrypoint.
exec docker-php-entrypoint "$@"


