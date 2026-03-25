FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libpq-dev \
 && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/html

COPY /apache/public/ /var/www/html/

RUN echo "max_input_vars=5000" > /usr/local/etc/php/conf.d/max-input-vars.ini \
 && echo "upload_max_filesize=25M" > /usr/local/etc/php/conf.d/uploads.ini \
 && echo "post_max_size=30M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "memory_limit=128M" >> /usr/local/etc/php/conf.d/uploads.ini \
 && echo "max_file_uploads=20" >> /usr/local/etc/php/conf.d/uploads.ini

CMD ["sh", "-c", "php -S 0.0.0.0:$PORT -t /var/www/html/"]