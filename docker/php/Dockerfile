FROM php:8.4-fpm-bookworm
ARG HOST_UID=1000
ARG HOST_GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        msmtp \
        passwd \
        cron \
        $PHPIZE_DEPS \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libzip-dev \
        libicu-dev \
    && docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        gd \
        mysqli \
        pdo_mysql \
        zip \
        intl \
        bcmath \
        exif

RUN groupmod -o -g "$HOST_GID" www-data \
   && usermod -o -u "$HOST_UID" -g "$HOST_GID" www-data

RUN --mount=type=bind,from=ghcr.io/php/pie:bin,source=/pie,target=/usr/local/bin/pie \
    pie install --no-cache "xdebug/xdebug:3.5.3" \
    && pie install --no-cache "phpredis/phpredis:6.3.0"

RUN apt-get purge -y --auto-remove $PHPIZE_DEPS \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && mkdir -p "$PHP_INI_DIR/bitrix.d" "$PHP_INI_DIR/xdebug.d"

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY bitrix.d/ "$PHP_INI_DIR/bitrix.d/"
COPY xdebug.d/ "$PHP_INI_DIR/xdebug.d/"
COPY msmtprc /etc/msmtprc

RUN chmod 0644 /etc/msmtprc

ENV PHP_INI_SCAN_DIR=":$PHP_INI_DIR/bitrix.d:$PHP_INI_DIR/xdebug.d"

WORKDIR /var/www/html
