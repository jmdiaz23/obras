# syntax=docker/dockerfile:1

###############################################################################
# 1) Assets: compila CSS/JS con Vite (Tailwind 4)
###############################################################################
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci

COPY . .
RUN npm run build


###############################################################################
# 2) Dependencias PHP con Composer
###############################################################################
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts \
        --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-scripts


###############################################################################
# 3) Aplicación (PHP-FPM)
###############################################################################
FROM php:8.3-fpm-alpine AS app

# Extensiones necesarias (la imagen base ya trae mbstring, dom, xml, opcache,
# iconv, fileinfo, etc.). Instalamos solo las que faltan para Laravel y
# PhpSpreadsheet.
RUN apk add --no-cache --virtual .build-deps \
        libpng-dev \
        libjpeg-turbo-dev \
        freetype-dev \
        libzip-dev \
        icu-dev \
    && apk add --no-cache \
        libpng \
        libjpeg-turbo \
        freetype \
        libzip \
        icu-libs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath \
        gd \
        zip \
        intl \
    && apk del .build-deps

WORKDIR /var/www/html

# Código + dependencias ya compiladas
COPY --from=vendor /app /var/www/html

# Assets compilados por Vite
COPY --from=assets /app/public/build /var/www/html/public/build

# Configuración propia
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-obraos.ini
COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN sed -i 's/\r$//' /usr/local/bin/entrypoint \
    && chmod +x /usr/local/bin/entrypoint \
    && mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]


###############################################################################
# 4) Servidor web (Nginx)
###############################################################################
FROM nginx:1.27-alpine AS web

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Nginx sirve los archivos públicos ya compilados
COPY --from=app /var/www/html/public /var/www/html/public

EXPOSE 80
