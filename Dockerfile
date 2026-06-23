FROM php:8.3-apache

RUN docker-php-ext-install pdo pdo_mysql mbstring gd
RUN a2enmod rewrite

COPY . /var/www/html
WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html/storage \
    && chmod +x docker-entrypoint.sh

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

EXPOSE 80
ENTRYPOINT ["./docker-entrypoint.sh"]
