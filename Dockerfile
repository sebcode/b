FROM php:8.0-apache

RUN apt-get update -yq && \
  apt-get install -yq libzip-dev

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN a2enmod rewrite
RUN docker-php-ext-install zip

WORKDIR /app
