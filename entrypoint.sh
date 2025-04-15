#!/bin/bash

# ✅ Fix ownership and permissions at container runtime (not just build time)
echo "🔧 Fixing folder permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/data /var/www/html/shared
chmod -R 777 /var/www/html/storage /var/www/html/data /var/www/html/shared

# ✅ Run install script if DB does not exist
if [ ! -f /var/www/html/data/database.sqlite ]; then
  echo "🛠 Running install.php automatically..."
  php /var/www/html/public/install.php
else
  echo "✅ Database already exists. Skipping install."
fi

# ✅ Start Apache in foreground
echo "🚀 Starting Apache..."
exec apache2-foreground