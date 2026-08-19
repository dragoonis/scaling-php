# Use the official PHP 8.4 FPM image
FROM php:8.4-fpm

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libsqlite3-dev \
    sqlite3 \
    libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-configure intl \
    && docker-php-ext-install gd zip intl pdo pdo_mysql pdo_sqlite

RUN apt-get update && apt-get install -y git

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions igbinary redis

# Install build dependencies for OpenSwoole
RUN apt-get update && apt-get install -y \
    build-essential \
    autoconf \
    libtool \
    pkg-config

RUN apt-get update && apt-get install nginx procps nano htop tree -y

# Permission Management
RUN usermod -d /var/www -u 1000 www-data && usermod --shell /bin/bash www-data && groupmod -g 1000 www-data

# Optional: Install composer (dependency manager)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY ./docker/normal-entrypoint.sh /entrypoint.sh

# PHP INI
COPY ./docker/symfony.prod.ini /usr/local/etc/php/conf.d/symfony.prod.ini

# FPM INI
COPY ./docker/fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf

RUN echo "" > /etc/nginx/sites-enabled/default
COPY ./docker/nginx/aa-nginx.conf /etc/nginx/conf.d/aa-nginx.conf

# Copy your application code
COPY --chown=www-data:www-data . /var/www/html

# Set working directory
WORKDIR /var/www/html

# Expose port 9000 for php-fpm
EXPOSE 9000 80

ENTRYPOINT [ "/var/www/html/docker/normal-entrypoint.sh" ]
