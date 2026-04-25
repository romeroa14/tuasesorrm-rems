# CodeIgniter 4 — PHP 8.2 + Apache, DocumentRoot = public/
FROM php:8.2-apache

# Paquetes para extensiones
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libpq-dev \
    unzip \
  && docker-php-ext-configure intl \
  && docker-php-ext-install -j"$(nproc)" intl mysqli pdo pdo_mysql zip gd pdo_pgsql opcache \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# DocumentRoot = public/ (CodeIgniter front controller)
COPY deploy/docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

COPY deploy/docker/entrypoint.sh /usr/local/bin/rems-entrypoint.sh
RUN chmod +x /usr/local/bin/rems-entrypoint.sh

WORKDIR /var/www/html

# El código se monta con volume en runtime; en build no copiamos el app (así se usa el mismo deploy en el servidor)
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/rems-entrypoint.sh"]
