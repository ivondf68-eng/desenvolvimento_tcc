FROM php:8.3-apache

# Instalar extensões necessárias para PDO + PostgreSQL
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Copiar código para dentro do container
COPY . /var/www/html/

EXPOSE 80
