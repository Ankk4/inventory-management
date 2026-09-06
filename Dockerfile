FROM php:8.4-fpm-bookworm

RUN apt-get update && apt-get install -y \
    git unzip libsqlite3-dev libzip-dev \
    libpng-dev libjpeg62-turbo-dev \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install pdo pdo_sqlite zip gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache database

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
