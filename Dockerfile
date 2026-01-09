#
# Production image (no Vite dev server). Builds PHP deps and frontend assets at build time.
#

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# We run without scripts here because Laravel's Composer scripts need the full app (artisan) present.
# We'll run `php artisan package:discover` later in the final image after copying the app.
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --optimize-autoloader --no-scripts

FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm ci --no-fund --no-audit
RUN npm run build
# Move manifest to root so it matches the SW's precache expectation (since we set outDir: 'public' for PWA plugin)
RUN mv /app/public/build/manifest.webmanifest /app/public/manifest.webmanifest || true

FROM dunglas/frankenphp:php8.4 AS app

# Copy composer from the vendor stage
COPY --from=vendor /usr/bin/composer /usr/bin/composer

RUN install-php-extensions \
    pdo_pgsql \
    redis \
    bcmath \
    gmp \
    intl \
    zip \
    pcntl

# Required for `php artisan schema:dump` on PostgreSQL (uses pg_dump).
# Our db image is postgres:18.1, so we install the matching client version to avoid version mismatch errors.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends ca-certificates curl gnupg; \
    curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc | gpg --dearmor -o /usr/share/keyrings/postgresql.gpg; \
    . /etc/os-release; \
    echo "deb [signed-by=/usr/share/keyrings/postgresql.gpg] http://apt.postgresql.org/pub/repos/apt ${VERSION_CODENAME}-pgdg main" > /etc/apt/sources.list.d/pgdg.list; \
    apt-get update; \
    apt-get install -y --no-install-recommends postgresql-client-18; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /app

# Entrypoint wrapper: clears Laravel caches on start, then calls original docker-php-entrypoint.
COPY docker/entrypoint.sh /usr/local/bin/plv-entrypoint
RUN chmod +x /usr/local/bin/plv-entrypoint

# App source
COPY . /app

# Replace vendor + built assets with production outputs
COPY --from=vendor /app/vendor /app/vendor
COPY --from=assets /app/public /app/public

# Ensure public storage symlink exists in the container image
RUN rm -rf /app/public/storage && ln -s /app/storage/app/public /app/public/storage

# Caddy/FrankenPHP config (without FrankenPHP worker mode to avoid crashes)
COPY Caddyfile /etc/frankenphp/Caddyfile

# Basic permissions (storage should be mounted as a volume in prod)
RUN mkdir -p /app/storage /app/bootstrap/cache && chmod -R 775 /app/storage /app/bootstrap/cache

# Generate Laravel package manifest (needed when composer scripts are skipped).
RUN php artisan package:discover --ansi

ENTRYPOINT ["plv-entrypoint"]
CMD ["--config","/etc/frankenphp/Caddyfile","--adapter","caddyfile"]
