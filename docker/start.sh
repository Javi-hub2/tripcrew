#!/bin/sh
# Start van de container op Railway: poort instellen, database bijwerken, Apache starten.
set -e

# Railway geeft de poort door in $PORT; Apache luistert standaard op 80.
PORT="${PORT:-80}"
sed -ri "s/Listen [0-9]+/Listen ${PORT}/" /etc/apache2/ports.conf
sed -ri "s/<VirtualHost \*:[0-9]+>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Alleen nieuwe migraties; bestaande data blijft staan.
php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec apache2-foreground
