FROM ubuntu:latest
LABEL authors="Ezequiel Pereira <info@ezequielhpp.net>"

RUN ln -snf /usr/share/zoneinfo/UTC /etc/localtime && echo UTC > /etc/timezone

# install nginx php8.1 and npm
RUN apt-get update && apt-get install -y nginx php8.1 php8.1-fpm php8.1-mysql php8.1-mbstring php8.1-xml php8.1-curl php8.1-zip php8.1-gd php8.1-bcmath php8.1-intl php8.1-redis php8.1-imagick php8.1-xdebug wget

# install the latest version of nodejs and npm
RUN apt-get install -y curl && curl -sL https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs

# install supervisor
RUN apt-get install -y supervisor

# upgrade npm
RUN npm install -g npm@10.0.0

# enable php8.1-fpm
RUN systemctl enable php8.1-fpm

# enable nginx
RUN systemctl enable nginx

# enable gd extension
RUN phpenmod gd

# enable imagick extension
RUN phpenmod imagick

# enable xdebug extension
RUN phpenmod xdebug

RUN echo "* * * * * root /usr/local/bin/php /var/www/html/artisan schedule:run >> /dev/null 2>&1"  >> /etc/cron.d/laravel-scheduler
RUN chmod 0644 /etc/cron.d/laravel-scheduler

# associate group www-data as 1000
RUN usermod -u 1000 www-data
RUN groupmod -g 1000 www-data

# copy nginx config
COPY server/nginx.conf /etc/nginx/sites-available/default

# copy php config
COPY server/php.ini /etc/php/8.1/cli/php.ini

# copy start script
COPY server/entrypoint.sh /usr/local/bin/entrypoint

# copy supervisord config
COPY server/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

RUN chmod +x /usr/local/bin/entrypoint

USER www-data
# copy project
COPY . /var/www/html

USER root

#chjeck if /var/www/.npm exists, if not create it
RUN cd /var/www && if [ ! -d ".npm" ]; then mkdir .npm; fi
RUN chown -R 33:1000 "/var/www/.npm"


EXPOSE 80

# run start script
ENTRYPOINT ["entrypoint"]
