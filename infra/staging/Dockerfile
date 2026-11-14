# Private staging candidate only. No production deploy or migrations.
FROM php:8.4-fpm-bookworm
RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libonig-dev libzip-dev unzip \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql mbstring bcmath zip \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --no-progress --no-audit
COPY . .
RUN composer dump-autoload --no-scripts --optimize \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache
USER www-data
EXPOSE 9000
CMD ["php-fpm"]
