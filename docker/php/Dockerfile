FROM php:8.4-fpm

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/* \
    && mkdir -p /var/www/html/var/templates_c \
    && chown www-data:www-data /var/www/html/var/templates_c

WORKDIR /var/www/html
