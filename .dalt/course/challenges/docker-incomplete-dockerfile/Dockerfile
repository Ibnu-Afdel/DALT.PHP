FROM php:8.2-fpm-alpine

# TODO: Set the working directory to /var/www/html
# Every subsequent command (COPY, RUN, CMD) will run from this path.

# TODO: Install the PHP extensions needed for PostgreSQL database access.
# You need two extensions: the base PDO abstraction layer and its Postgres driver.
# The helper command that compiles them is documented in the lesson.
#
# Careful: that helper compiles the extension but does not install the
# operating-system library it compiles against. Alpine needs that package
# added first, in the same RUN, or the build stops at a missing C header.

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction

COPY . .

EXPOSE 9000

# TODO: Add the CMD instruction to start php-fpm
# Use the array ("exec") form, not the string form.
