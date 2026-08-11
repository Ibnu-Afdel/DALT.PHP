# ---- Stage 1: builder ----
FROM composer:2 AS builder

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --optimize-autoloader

# ---- Stage 2: runtime ----
FROM php:8.2-fpm-alpine AS runtime

WORKDIR /var/www/html

RUN apk add --no-cache curl \
 && docker-php-ext-install pdo pdo_pgsql

COPY --from=builder /app/vendor ./vendor

COPY . .

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
  CMD php-fpm -t || exit 1

CMD ["php-fpm"]
