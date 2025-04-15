FROM php:8.3-apache

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

# ✅ Copy full application AFTER Composer install
COPY . /var/www/html

# ✅ Fix permissions AFTER copying files
COPY . /var/www/html

# 🔧 Ensure writable temp folder and large file support
RUN mkdir -p /var/www/html/tmp \
    && chown www-data:www-data /var/www/html/tmp \
    && chmod 777 /var/www/html/tmp \
    && echo "upload_tmp_dir=/var/www/html/tmp" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "upload_max_filesize=20G\npost_max_size=20G\nmemory_limit=4G\nmax_execution_time=7200\nmax_input_time=7200" >> /usr/local/etc/php/conf.d/uploads.ini

# ✅ Add entrypoint to auto-run install.php
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]