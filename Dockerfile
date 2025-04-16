FROM php:8.3-fpm
# Build custom PHP-FPM container, use with a clean Apache reverse proxy outside

# Install system packages and PHP extensions
RUN apt-get update && apt-get install -y \
    git unzip zip \
    libsqlite3-dev sqlite3 sendmail \
    && docker-php-ext-install pdo pdo_sqlite

# Enable Apache rewrite module
RUN a2enmod rewrite

# Configure sendmail path
RUN echo "sendmail_path = /usr/sbin/sendmail -t -i" >> /usr/local/etc/php/php.ini

# Set Apache document root
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# ✅ Install Composer and dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock* /var/www/html/
WORKDIR /var/www/html
RUN composer install

# ✅ PHP custom config — copied BEFORE app to prevent overwrite
COPY php/99-custom.ini /usr/local/etc/php/conf.d/99-custom.ini

# ✅ Copy full application AFTER config
COPY . /var/www/html

# ✅ Create temp upload dir
RUN mkdir -p /var/www/html/tmp \
    && chown www-data:www-data /var/www/html/tmp \
    && chmod 777 /var/www/html/tmp

# ✅ Add entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]