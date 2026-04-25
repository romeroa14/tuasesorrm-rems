#!/bin/sh
set -e
cd /var/www/html
if [ ! -d "vendor" ]; then
  echo "[rems] vendor/ not found, running composer install..."
  composer install --no-dev --no-interaction --optimize-autoloader
fi
exec apache2-foreground
