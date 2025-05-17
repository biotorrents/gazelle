##
# https://docs.docker.com/build/building/best-practices/
#

# https://hub.docker.com/_/php
FROM php:8.4-alpine


# copy the source code
COPY . /usr/src/gazelle
WORKDIR /usr/src/gazelle


# use this configuration file
RUN mv "./utilities/docker/php/php.ini" "$PHP_INI_DIR/php.ini"


# https://github.com/jvoisin/snuffleupagus/tree/master/config
RUN mv "./utilities/docker/php/snuffleupagus/default_php8.rules" \
    "$PHP_INI_DIR/conf.d/default_php8.rules"

RUN mv "./utilities/docker/php/snuffleupagus/detect_dangerous_extensions.rules" \
    "$PHP_INI_DIR/conf.d/detect_dangerous_extensions.rules"

RUN mv "./utilities/docker/php/snuffleupagus/ini_protection.rules" \
    "$PHP_INI_DIR/conf.d/ini_protection.rules"


# https://github.com/mlocati/docker-php-extension-installer
ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN install-php-extensions \
    @composer \
    apcu \
    gd \
    pdo_mysql \
    pdo_pgsql \
    redis \
    snuffleupagus \
    xdebug \
    zip


# expose some ports
EXPOSE 80/tcp
EXPOSE 443/tcp
EXPOSE 34000/tcp


# start the app
CMD [ "php", "./public/index.php" ]
