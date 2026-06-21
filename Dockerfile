# syntax=docker/dockerfile:1

# =============================================================================
# Stage 1 — PHP dependencies (Composer)
# Scripts skipped (no artisan/PHP extensions here); discovery runs in Stage 3.
# Built first because the frontend build imports vendor/tightenco/ziggy.
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
# Builds client bundle only. SSR + vue-tsc are intentionally skipped here for
# deploy reliability; Inertia falls back to client-side rendering at runtime.
# =============================================================================
FROM node:22-alpine AS frontend
WORKDIR /app

COPY package.json ./
# No package-lock.json in the repo by design: dependencies resolve fresh inside
# the image so the build matches the container environment, not a dev machine.
# --legacy-peer-deps sidesteps an eslint/vue plugin peer conflict that is
# irrelevant to the asset build.
RUN npm install --legacy-peer-deps --no-audit --no-fund

COPY . .
# Ziggy ships as a composer package but is imported by the Vue app.
COPY --from=vendor /app/vendor/tightenco/ziggy ./vendor/tightenco/ziggy
RUN npx vite build

# =============================================================================
# Stage 3 — Runtime (PHP-FPM + Nginx + Supervisor)
# A single self-contained image so local and production run identically.
# =============================================================================
FROM php:8.3-fpm-bookworm AS app

# --- System packages & PHP extensions ---------------------------------------
# gd: current image pipeline. imagick: future variant system. exif: orientation.
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        unzip \
        git \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
        libzip-dev \
        libonig-dev \
        libicu-dev \
        libmagickwand-dev \
        libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
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
    && pecl install imagick redis \
    && docker-php-ext-enable imagick redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www

# --- Application source + dependencies + built assets -----------------------
COPY . /var/www
COPY --from=vendor /app/vendor /var/www/vendor
COPY --from=frontend /app/public/build /var/www/public/build

# Laravel package discovery (composer scripts were skipped in Stage 2)
RUN php artisan package:discover --ansi || true

# --- Container configuration -------------------------------------------------
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/nginx.conf /etc/nginx/sites-available/default
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# --- Writable runtime directories + permissions ------------------------------
# .dockerignore strips storage contents, so recreate the framework dirs the
# app needs at runtime, then hand ownership to the php-fpm/queue user.
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
