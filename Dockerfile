FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libpq-dev \
 && docker-php-ext-install pdo pdo_pgsql

WORKDIR /app

COPY apache/public/ /app/

RUN echo "max_input_vars=5000" > /usr/local/etc/php/conf.d/max-input-vars.ini

CMD ["sh", "-c", "php -S 0.0.0.0:${PORT:-8080} -t /app"]
