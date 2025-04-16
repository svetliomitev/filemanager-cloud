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

# Install Composer and dependencies
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock* /var/www/html/
WORKDIR /var/www/html
RUN composer install

# Copy full application AFTER dependencies
COPY . /var/www/html

# PHP config for uploads and logging
RUN mkdir -p /var/www/html/tmp \
    && chown www-data:www-data /var/www/html/tmp \
    && chmod 777 /var/www/html/tmp \
    && echo -e "upload_max_filesize=20G\npost_max_size=20G\nmemory_limit=4G\nmax_execution_time=7200\nmax_input_time=7200\nupload_tmp_dir=/var/www/html/tmp\nlog_errors=On\nerror_log=/var/www/html/data/php_errors.log" \
    > /usr/local/etc/php/conf.d/99-custom.ini

# ✅ Create required folders for the app (Uppy chunked upload support)
RUN mkdir -p /var/www/html/storage \
             /var/www/html/data \
             /var/www/html/shared \
             /var/www/html/tmp/upload_chunks \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/data /var/www/html/shared /var/www/html/tmp \
    && chmod -R 777 /var/www/html/storage /var/www/html/data /var/www/html/shared /var/www/html/tmp

# Add entrypoint
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]