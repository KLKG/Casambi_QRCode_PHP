# Casambi_QRCode_PHP – PHP 8.3 + Apache (läuft auch auf Hosts mit altem PHP, z. B. Ubuntu 20.04)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader --ignore-platform-reqs

FROM php:8.3-apache

RUN apt-get update \
 && apt-get install -y --no-install-recommends libpng-dev libjpeg62-turbo-dev libwebp-dev libfreetype6-dev openssl \
 && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
 && docker-php-ext-install -j"$(nproc)" gd mysqli sockets \
 && rm -rf /var/lib/apt/lists/* \
 && a2enmod rewrite headers ssl

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/apache-site.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

WORKDIR /var/www/app
COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN chown -R www-data:www-data config logs

ENTRYPOINT ["app-entrypoint"]
CMD ["apache2-foreground"]
