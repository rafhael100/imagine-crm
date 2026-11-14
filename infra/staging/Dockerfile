# F3-267 review candidate: build allowed only in isolated PR #517 CI; NEVER deploy to VPS.
# This is a smoke-only Laravel runtime: no customer registration or real DB.
FROM php:8.4-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev libonig-dev libpq-dev libsqlite3-dev libzip-dev unzip \
    && docker-php-ext-install -j"$(nproc)" bcmath intl mbstring opcache pdo_pgsql pdo_sqlite zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --classmap-authoritative \
    --no-interaction --prefer-dist --no-progress

# Explicit allowlist: exclude .env, experimental IAM migrations, tests,
# Git history, uploads and arbitrary root artifacts from the runtime image.
COPY app/ app/
COPY bootstrap/ bootstrap/
COPY config/ config/
COPY database/migrations/ database/migrations/
COPY public/ public/
COPY resources/ resources/
COPY routes/ routes/
COPY artisan artisan
RUN composer dump-autoload --no-dev --classmap-authoritative --no-scripts \
    && mkdir -p storage/framework/cache storage/framework/sessions \
        storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

USER www-data
EXPOSE 9000
CMD ["php-fpm", "-F"]
