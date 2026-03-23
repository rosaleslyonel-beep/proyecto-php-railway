FROM php:8.2-apache

RUN apt-get update && apt-get install -y \
    libpq-dev \
 && docker-php-ext-install pdo pdo_pgsql

# Deshabilitar módulos conflictivos uno por uno de forma segura
RUN a2dismod mpm_event || true
RUN a2dismod mpm_worker || true

# Asegurar que mpm_prefork y rewrite estén activos
RUN a2enmod mpm_prefork rewrite

WORKDIR /var/www/html

COPY apache/public/ /var/www/html/

RUN echo "max_input_vars=5000" > /usr/local/etc/php/conf.d/max-input-vars.ini

# Opcional: Asegurar permisos correctos para Railway
RUN chown -R www-data:www-data /var/www/html