# syntax=docker/dockerfile:1.7

FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json vite.config.js ./
RUN npm ci --no-audit --no-fund

COPY resources ./resources
COPY public ./public
RUN npm run build

FROM composer:2.8.12 AS composer

FROM php:8.2-fpm-alpine

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    COMPOSER_PROCESS_TIMEOUT=900 \
    COMPOSER_MAX_PARALLEL_HTTP=4 \
    RUN_MIGRATIONS=true \
    SEED_ADMIN=false

RUN apk add --no-cache \
    curl \
    icu-libs \
    libxml2 \
    libzip \
    nginx \
    supervisor \
    && apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    curl-dev \
    icu-dev \
    libxml2-dev \
    libzip-dev \
    oniguruma-dev \
    && docker-php-ext-install -j"$(nproc)" \
    curl \
    intl \
    mbstring \
    opcache \
    pcntl \
    pdo_mysql \
    simplexml \
    zip \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

WORKDIR /var/www/html

# Version figée pour que les builds Dokploy restent reproductibles.
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

# Cette première installation permet de réutiliser le cache Docker tant que
# composer.json et composer.lock ne changent pas.
COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    set -eu; \
    apk add --no-cache --virtual .composer-deps git; \
    git config --global http.version HTTP/1.1; \
    export COMPOSER_MAX_PARALLEL_HTTP=2; \
    attempt=1; \
    until composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-source \
        --no-scripts \
        --no-autoloader; do \
        if [ "$attempt" -ge 3 ]; then \
            echo "ERREUR: installation Composer impossible après 3 tentatives." >&2; \
            exit 1; \
        fi; \
        echo "Téléchargement Composer interrompu, nouvelle tentative dans 5 secondes ($attempt/3)..." >&2; \
        attempt=$((attempt + 1)); \
        sleep 5; \
    done

COPY . /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    && apk del .composer-deps

COPY custom-php.ini /usr/local/etc/php/conf.d/99-monprof.ini
COPY nginx.conf /etc/nginx/nginx.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/laravel-cron /etc/crontabs/www-data
COPY docker-entrypoint.sh /usr/local/bin/monprof-entrypoint

RUN chmod +x /usr/local/bin/monprof-entrypoint \
    && chmod 0600 /etc/crontabs/www-data \
    && mkdir -p \
    /run/nginx \
    /var/log/supervisor \
    /var/www/html/bootstrap/cache \
    /var/www/html/storage/app/public \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs \
    && chown -R www-data:www-data \
    /var/www/html/bootstrap/cache \
    /var/www/html/storage

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl --fail --silent --show-error http://127.0.0.1/health || exit 1

ENTRYPOINT ["monprof-entrypoint"]
CMD ["supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
