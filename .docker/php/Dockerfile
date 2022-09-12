FROM php:8.1-cli-alpine

# Update repo and install some utilites
RUN set -xe \
    && apk update \
    && apk upgrade \
    && apk --no-cache add htop unzip gzip

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# PHP
RUN set -xe \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \

    # Sodium
    && apk add --no-cache libsodium-dev \
    && docker-php-ext-install sodium \
    && docker-php-ext-enable sodium \

    # INTL
    && apk add --no-cache icu-dev icu-libs \
    && docker-php-ext-install intl \
    && docker-php-ext-enable intl \

    # Yasd
    && apk add --no-cache boost boost-dev \
    && docker-php-source extract \
    && mkdir /usr/src/php/ext/yasd \
    && curl -sfL https://github.com/swoole/yasd/archive/master.tar.gz -o yasd.tar.gz \
    && tar xfz yasd.tar.gz --strip-components=1 -C /usr/src/php/ext/yasd \
    && docker-php-ext-configure yasd \
    && docker-php-ext-install -j$(nproc) yasd \
    && rm -f yasd.tar.gz \
    && docker-php-source delete \

    # Swoole https://github.com/swoole/docker-swoole/blob/master/Dockerfile.alpine.twig
    && apk add --no-cache libstdc++ \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS curl-dev openssl-dev pcre-dev pcre2-dev zlib-dev \
    && docker-php-ext-install sockets \
    && docker-php-source extract \
    && mkdir /usr/src/php/ext/swoole \
    && curl -sfL https://github.com/swoole/swoole-src/archive/master.tar.gz -o swoole.tar.gz \
    && tar xfz swoole.tar.gz --strip-components=1 -C /usr/src/php/ext/swoole \
    && docker-php-ext-configure swoole \
        --enable-mysqlnd \
        --enable-openssl \
        --enable-sockets \
        --enable-swoole-curl \
    && docker-php-ext-install -j$(nproc) swoole \
    && rm -f swoole.tar.gz \
    && docker-php-source delete

# Cleanup
RUN set -xe \
    && apk del --no-cache .build-deps \
    && apk del --no-cache icu-dev \
    && apk del --no-cache libsodium-dev \
    && rm -rf /tmp/* /var/cache/apk/*

EXPOSE 8000
CMD ["php", "-e", "/var/www/app/public/index.php"]

WORKDIR /var/www/app
