#!/bin/bash

# Automatically run install.php if database does not exist
if [ ! -f /var/www/html/data/database.sqlite ]; then
  echo "🛠 Running initial installation..."
  php /var/www/html/public/install.php
fi

# Start Apache in foreground
apache2-foreground