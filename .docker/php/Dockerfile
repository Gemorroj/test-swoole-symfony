FROM php:8.0-cli-alpine

# Update repo and install some utilites
RUN set -xe \
    && apk update \
    && apk upgrade \
    && apk --no-cache add htop unzip gzip

# Composer
RUN set -xe \
    && curl -L -o /composer.phar https://github.com/composer/composer/releases/download/2.1.14/composer.phar \
    && chmod 755 /composer.phar

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

    # Swoole https://github.com/swoole/docker-swoole/blob/master/Dockerfile.alpine.twig \
    && docker-php-ext-install sockets \
    && pecl install swoole \
    && docker-php-ext-enable swoole

# Cleanup
RUN set -xe \
    && apk del --no-cache .build-deps \
    && apk del --no-cache icu-dev \
    && apk del --no-cache libsodium-dev \
    && rm -rf /tmp/* /var/cache/apk/*

EXPOSE 8000
CMD ["php", "/var/www/app/public/index.php"]

WORKDIR /var/www/app
