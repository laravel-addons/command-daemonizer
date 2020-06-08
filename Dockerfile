FROM php:7.2-cli

RUN mkdir /command-daemonizer
WORKDIR /command-daemonizer

RUN apt-get update
RUN apt-get install -y git zip unzip
RUN pecl install xdebug-2.6.0 && \
    docker-php-ext-enable xdebug

RUN curl --silent --show-error https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
