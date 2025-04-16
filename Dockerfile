FROM php:8.3-fpm

# Install required packages
RUN apt-get update && apt-get install -y \
    git unzip zip \
    libsqlite3-dev sqlite3 sendmail \
    && docker-php-ext-install pdo pdo_sqlite

# ✅ Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ✅ Working directory for app
WORKDIR /var/www/html

# ✅ Copy app (after Composer dependencies)
COPY composer.json composer.lock* ./
RUN composer install
COPY . .

# ✅ Set correct permissions and create temp dirs
RUN mkdir -p /var/www/html/tmp /var/www/html/data /var/www/html/storage /var/www/html/shared \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 777 /var/www/html/tmp /var/www/html/data /var/www/html/storage /var/www/html/shared

# ✅ Add PHP config for upload + logging
COPY php/99-custom.ini /usr/local/etc/php/conf.d/99-custom.ini

# ✅ Add entrypoint for install
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]

# ✅ Default CMD for php-fpm
CMD ["php-fpm"]