#!/bin/bash

echo "🔧 Fixing folder permissions..."

# Ensure required folders exist
mkdir -p /var/www/html/storage \
         /var/www/html/data \
         /var/www/html/shared \
         /var/www/html/tmp \
         /var/www/html/tmp/upload_chunks \
         /var/www/html/chunks_tmp

# Set ownership and permissions
chown -R www-data:www-data /var/www/html/storage \
                           /var/www/html/data \
                           /var/www/html/shared \
                           /var/www/html/tmp \
                           /var/www/html/chunks_tmp

chmod -R 777 /var/www/html/storage \
             /var/www/html/data \
             /var/www/html/shared \
             /var/www/html/tmp \
             /var/www/html/chunks_tmp

# Ensure PHP error log file exists and is writable
touch /var/www/html/data/php_errors.log
chown www-data:www-data /var/www/html/data/php_errors.log
chmod 666 /var/www/html/data/php_errors.log

# 🛠 Run install.php if DB is missing
if [ ! -f /var/www/html/data/database.sqlite ]; then
  echo "🛠 Running install.php automatically..."
  php /var/www/html/public/install.php
else
  echo "✅ Database already exists. Skipping install."
fi

# 🚀 Start Apache (php:8.3-apache)
echo "🚀 Starting Apache..."
exec apache2-foreground