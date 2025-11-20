FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
 && docker-php-ext-install pdo pdo_pgsql \
 && a2enmod rewrite

WORKDIR /var/www/html

COPY apache/public/ /var/www/html/
# Aumentar el límite de variables de entrada
RUN echo "max_input_vars=5000" > /usr/local/etc/php/conf.d/max-input-vars.ini