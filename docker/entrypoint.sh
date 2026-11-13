#!/bin/sh
set -e
APP=/var/www/html
mkdir -p "$APP/storage/data" "$APP/storage/fonts" "$APP/storage/tmp"
chown -R www-data:www-data "$APP/storage"
# first run / upgrade: import old settings, create the admin account (password printed once in the log)
su -s /bin/sh www-data -c "php $APP/bin/seed.php" || true
exec "$@"
