#!/usr/bin/env bash

mkdir -p /var/www/html/

set -euo pipefail

# make sure the folders framework/{sessions,views,cache} exist
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/testing
chmod -R ugo+rw /var/www/html/storage/framework

#check if node_modules folder exists
if [ ! -d /var/www/html/node_modules ]; then
    npm install
fi

# go to the folder and run npm install
cd /var/www/html

#run a npm build
npm run build

#check if vendor folder exists
if [ ! -d /var/www/html/vendor ]; then
    composer install
fi

if [ ! -f /var/www/html/storage/logs/laravel.log ]; then
    touch /var/www/html/storage/logs/laravel.log
fi
chmod o+w /var/www/html/storage/ -R
chown -R www-data:www-data /var/www/html/storage
chmod 777 /var/www/html/storage/logs/laravel.log

cd /var/www/html && php artisan cache:clear
cd /var/www/html && php artisan config:clear
cd /var/www/html && php artisan config:cache

# start the server
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
