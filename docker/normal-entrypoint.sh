#!/usr/bin/env bash
set -eux

mkdir -p /tmp/laravel-cache
chown -R www-data:www-data /tmp/laravel-cache

# background
nginx

# foreground
php-fpm
