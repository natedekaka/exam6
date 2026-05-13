#!/bin/bash
set -e

chmod -R 777 /var/www/html/uploads
mkdir -p /var/log/apache2
chown www-data:www-data /var/log/apache2

php-fpm -D

for i in $(seq 1 10); do
    if pgrep -x php-fpm > /dev/null 2>&1; then
        break
    fi
    sleep 1
done

exec apache2ctl -DFOREGROUND
