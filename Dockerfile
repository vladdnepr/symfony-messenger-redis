FROM php:8.1-cli

RUN apt-get update && apt-get install -y git zip

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
RUN install-php-extensions redis pcntl

COPY --from=composer:2.4.2 /usr/bin/composer /usr/bin/composer
