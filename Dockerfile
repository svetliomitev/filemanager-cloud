FROM php:8.3-apache

RUN apt-get update && apt-get install -y \
    libsqlite3-dev sqlite3 sendmail \
    && docker-php-ext-install pdo pdo_sqlite

RUN a2enmod rewrite

RUN echo "sendmail_path = /usr/sbin/sendmail -t -i" >> /usr/local/etc/php/php.ini

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY . /var/www/html

RUN echo "upload_max_filesize=10G\npost_max_size=10G\nmax_execution_time=600\nmax_input_time=600" > /usr/local/etc/php/conf.d/uploads.ini

RUN mkdir -p /var/www/html/data /var/www/html/storage /var/www/html/shared \
    && chown -R www-data:www-data /var/www/html/data /var/www/html/storage /var/www/html/shared \
    && chmod -R 777 /var/www/html/data /var/www/html/storage /var/www/html/shared

# ✅ Add entrypoint for auto-install
# Copy the entrypoint script and make it executable
COPY entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Use it as the container's entrypoint
ENTRYPOINT ["/entrypoint.sh"]