FROM php:8.4-fpm-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libpq-dev libonig-dev libzip-dev unzip \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql mbstring bcmath zip \
    && pecl install redis-6.2.0 && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN for attempt in 1 2 3; do \
      if composer install --no-dev --no-scripts --no-interaction --prefer-dist --no-progress; then exit 0; fi; \
      sleep 2; \
    done; exit 1
COPY . .
RUN composer dump-autoload --no-scripts --optimize \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && printf 'display_errors=Off\nlog_errors=On\nzend.exception_ignore_args=On\nexpose_php=Off\nupload_max_filesize=20K\npost_max_size=64K\n' > /usr/local/etc/php/conf.d/private-crm.ini
USER www-data
CMD ["sh","-c","mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache && exec php-fpm"]
