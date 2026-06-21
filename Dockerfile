# syntax=docker/dockerfile:1

# =============================================================================
# Stage 1 — PHP dependencies (Composer)
# Scripts skipped; discovery runs in Stage 3.
# Built first because the frontend imports vendor/tightenco/ziggy.
# =============================================================================
FROM composer:2 AS vendor
WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

# =============================================================================
# Stage 2 — Frontend assets (Vite)
# =============================================================================
FROM node:22-alpine AS frontend
WORKDIR /app

COPY package.json ./
# No package-lock.json by design: resolves fresh inside the container.
# --legacy-peer-deps: sidesteps an eslint/vue peer conflict irrelevant to build.
RUN npm install --legacy-peer-deps --no-audit --no-fund

COPY . .
# Ziggy ships as a PHP package but is imported by the Vue app.
COPY --from=vendor /app/vendor/tightenco/ziggy ./vendor/tightenco/ziggy
RUN npx vite build

# =============================================================================
# Stage 3 — Runtime (PHP-FPM + Nginx + Supervisor)
#
# We use the mlocati/docker-php-extension-installer helper which downloads
# pre-compiled extension binaries instead of building from C source.
# This cuts the extension install step from ~20 min down to ~2-3 min.
# =============================================================================
FROM php:8.3-fpm-bookworm AS app

# Pull in the extension installer (single ADD is fine; no curl/wget needed)
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    /usr/local/bin/install-php-extensions
RUN chmod +x /usr/local/bin/install-php-extensions

# System packages (only what the extensions actually need at runtime)
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        unzip \
        git \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# All PHP extensions in one fast step — pre-compiled binaries, not source.
# gd: current image pipeline.  imagick: future variant system.
RUN install-php-extensions \
        gd \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        zip \
        bcmath \
        exif \
        intl \
        opcache \
        pcntl \
        imagick \
        redis

WORKDIR /var/www

# --- Application source + dependencies + built assets -----------------------
COPY . /var/www
COPY --from=vendor /app/vendor /var/www/vendor
COPY --from=frontend /app/public/build /var/www/public/build

# Laravel package discovery (composer scripts were skipped in Stage 1)
RUN php artisan package:discover --ansi || true

# --- Container configuration -------------------------------------------------
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# --- Writable runtime directories + permissions ------------------------------
RUN mkdir -p \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/framework/testing \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["entrypoint"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
