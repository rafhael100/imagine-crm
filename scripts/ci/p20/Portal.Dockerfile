FROM php:8.4-cli-bookworm AS php
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev libpq-dev libxml2-dev unzip && docker-php-ext-install mbstring pdo_pgsql dom xml xmlwriter && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-plugins --prefer-dist --no-interaction --no-progress
COPY . .
RUN composer dump-autoload --no-scripts --no-plugins && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache && chown -R www-data:www-data /var/www/html

FROM postgres:18-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libonig5 libxml2 libargon2-1 libsodium23 libsqlite3-0 libcurl4 && rm -rf /var/lib/apt/lists/*
COPY --from=php /usr/local /usr/local
COPY --from=php --chown=www-data:www-data /var/www/html /var/www/html
WORKDIR /var/www/html
USER www-data
ENTRYPOINT ["bash", "/p20-code/portal-start.sh"]
