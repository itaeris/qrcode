# syntax=docker/dockerfile:1

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/root/.composer/cache \
    composer install \
        --no-dev \
        --no-interaction \
        --no-scripts \
        --prefer-dist \
        --no-progress

COPY . .

RUN touch .env \
    && composer dump-autoload --optimize --no-dev --no-interaction \
    && rm -f .env

FROM node:22-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./

RUN --mount=type=cache,target=/root/.npm \
    npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views \
    ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views

RUN mkdir -p storage/framework/views \
    && npm run build

FROM php:8.4-fpm-alpine AS app

RUN apk add --no-cache \
        nginx \
        curl \
        icu-libs \
        libzip \
        oniguruma \
        mariadb-connector-c \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        linux-headers \
        mariadb-connector-c-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql bcmath intl opcache zip \
    && apk del .build-deps \
    && rm -rf /var/cache/apk/*

COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/www.conf /usr/local/etc/php-fpm.d/zz-docker.conf
COPY docker/entrypoint.sh /entrypoint.sh

COPY --from=vendor /app /var/www/html
COPY --from=assets /app/public/build /var/www/html/public/build

RUN chmod +x /entrypoint.sh \
    && mkdir -p /var/www/html/storage/framework/cache/data \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
        /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

WORKDIR /var/www/html

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["/entrypoint.sh"]
