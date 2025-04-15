#!/bin/bash

# Run install script if DB does not exist
if [ ! -f /var/www/html/data/database.sqlite ]; then
  echo "🛠 Running install.php automatically..."
  php /var/www/html/public/install.php
fi

# Start Apache in foreground
exec apache2-foreground