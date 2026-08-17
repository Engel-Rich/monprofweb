FROM php:8.2-fpm-alpine

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    COMPOSER_ALLOW_SUPERUSER=1 \
    RUN_MIGRATIONS=false

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
    zip \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

WORKDIR /var/www/html

# Installation vérifiée de Composer sans dépendre d'une deuxième image Docker.
RUN set -eu; \
    expected_checksum="$(curl -fsSL https://composer.github.io/installer.sig)"; \
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php; \
    actual_checksum="$(php -r "echo hash_file('sha384', '/tmp/composer-setup.php');")"; \
    test "$expected_checksum" = "$actual_checksum"; \
    php /tmp/composer-setup.php --2 --quiet --install-dir=/usr/local/bin --filename=composer; \
    rm /tmp/composer-setup.php

# Cette première installation permet de réutiliser le cache Docker tant que
# composer.json et composer.lock ne changent pas.
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --no-scripts \
    --no-autoloader

COPY . /var/www/html
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader

COPY custom-php.ini /usr/local/etc/php/conf.d/99-monprof.ini
COPY nginx.conf /etc/nginx/nginx.conf
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker-entrypoint.sh /usr/local/bin/monprof-entrypoint

RUN chmod +x /usr/local/bin/monprof-entrypoint \
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
