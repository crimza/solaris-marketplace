FROM php:7.1-fpm-alpine

ARG WWWUSER
ARG WWWGROUP
ARG APPUSER

WORKDIR /app
RUN apk add --no-cache zlib-dev libpng-dev libzip-dev imagemagick-dev freetype-dev libjpeg-turbo-dev $PHPIZE_DEPS
RUN docker-php-ext-configure gd --with-freetype-dir=/usr --with-jpeg-dir=/usr --with-png-dir=/usr
RUN docker-php-ext-install -j$(nproc) gd zip pdo pdo_mysql
COPY --from=composer:2.2 /usr/bin/composer /usr/local/bin/composer
RUN addgroup -g $WWWGROUP $APPUSER
RUN adduser -D -s /bin/sh -G $APPUSER -u $WWWUSER $APPUSER
