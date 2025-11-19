FROM php:8.2-apache

# Instalar el driver de PDO para PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Habilitar mod_rewrite si usas URLs amigables
RUN a2enmod rewrite

# Directorio de trabajo (root de Apache)
WORKDIR /var/www/html

# Copiar el contenido de apache/public al DocumentRoot
COPY apache/public/ /var/www/html/

# (Opcional) si quieres que info.php también sea accesible:
COPY info.php /var/www/html/info.php