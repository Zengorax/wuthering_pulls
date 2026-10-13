# Image de développement : PHP 8.4 + Composer + extensions requises par le projet.
FROM php:8.4-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev libpq-dev libpng-dev libjpeg-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" intl zip pdo_pgsql opcache gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_MEMORY_LIMIT=-1

WORKDIR /app
EXPOSE 8000

ENTRYPOINT ["sh", "/usr/local/bin/app-entrypoint"]
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
