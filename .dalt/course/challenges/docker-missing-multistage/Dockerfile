FROM php:8.2-fpm-alpine

WORKDIR /var/www/html
RUN apk add --no-cache curl postgresql-dev
RUN docker-php-ext-install pdo pdo_pgsql

# Composer installed directly in final image (bad practice)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

COPY . .
EXPOSE 9000
CMD ["php-fpm"]
