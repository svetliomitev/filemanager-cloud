#!/bin/bash

# 🔧 Ensure all required folders exist and are writable
echo "🔧 Fixing folder permissions..."
mkdir -p /var/www/html/storage /var/www/html/data /var/www/html/shared /var/www/html/tmp
chown -R www-data:www-data /var/www/html/storage /var/www/html/data /var/www/html/shared /var/www/html/tmp
chmod -R 777 /var/www/html/storage /var/www/html/data /var/www/html/shared /var/www/html/tmp

# 🔧 Ensure PHP error log file exists and is writable
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

# 🚀 Start Apache (required for php:8.3-apache)
echo "🚀 Starting Apache..."
exec apache2-foreground