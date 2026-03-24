FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
 && docker-php-ext-install pdo pdo_pgsql \
 && a2dismod mpm_event || true \
 && a2dismod mpm_worker || true \
 && rm -f /etc/apache2/mods-enabled/mpm_event.load \
          /etc/apache2/mods-enabled/mpm_event.conf \
          /etc/apache2/mods-enabled/mpm_worker.load \
          /etc/apache2/mods-enabled/mpm_worker.conf \
 && a2enmod mpm_prefork rewrite

WORKDIR /var/www/html

COPY apache/public/ /var/www/html/

RUN echo "max_input_vars=5000" > /usr/local/etc/php/conf.d/max-input-vars.ini \
 && chown -R www-data:www-data /var/www/html \
 && apache2ctl -M | grep mpm
