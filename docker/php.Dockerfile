FROM php:8.3-fpm-bookworm
RUN docker-php-ext-install pdo_mysql \
    && mkdir -p /var/lib/php/sessions \
    && chown www-data:www-data /var/lib/php/sessions
COPY backend/ /var/www/html/
RUN mkdir -p /var/www/html/storage/uploads && chown -R www-data:www-data /var/www/html/storage
COPY docker/php.ini /usr/local/etc/php/conf.d/fasiliti.ini
WORKDIR /var/www/html
