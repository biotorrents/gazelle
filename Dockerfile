##
# https://docs.docker.com/build/building/best-practices/
#

# https://hub.docker.com/_/php
FROM php:8.4-alpine

# copy the source code
COPY . /usr/src/gazelle
WORKDIR /usr/src/gazelle

# use this configuration file
RUN mv "./docker/php/php.ini" "$PHP_INI_DIR/php.ini"

# install system software
RUN apk install \
    git \
    make \
    php83-dev

# install snuffleupagus
# https://snuffleupagus.readthedocs.io/installation.html
RUN git clone https://github.com/jvoisin/snuffleupagus \
    && cd snuffleupagus/src \
    && phpize \
    && ./configure --enable-snuffleupagus \
    && make \
    && make install

# install php extensions
RUN docker-php-ext-install \
    apcu \
    curl \
    gd \
    mbstring \
    mysqli \
    pdo \
    redis \
    zip

# install composer
# https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
RUN ./docker/php/composer.sh

# expose some ports
EXPOSE 80/tcp
EXPOSE 443/tcp
EXPOSE 34000/tcp

# start the app
CMD [ "php", "./public/index.php" ]


###


COPY misc/docker/ /var/www/misc/docker
COPY lib /var/www/lib
COPY bin/ /var/www/bin
COPY --from=composer:2.8.3 /usr/bin/composer /usr/local/bin/composer

# Permissions and configuration layer
RUN useradd -ms /bin/bash gazelle \
    && cp /var/www/misc/docker/web/php.ini /etc/php/${PHP_VER}/cli/php.ini \
    && cp /var/www/misc/docker/web/php.ini /etc/php/${PHP_VER}/fpm/php.ini \
    && cp /var/www/misc/docker/web/xdebug.ini /etc/php/${PHP_VER}/mods-available/xdebug.ini \
    && cp /var/www/misc/docker/web/www.conf /etc/php/${PHP_VER}/fpm/pool.d/www.conf \
    && cp /var/www/misc/docker/web/nginx.conf /etc/nginx/sites-available/gazelle.conf \
    && ln -s /etc/nginx/sites-available/gazelle.conf /etc/nginx/sites-enabled/gazelle.conf \
    && rm -f /etc/nginx/sites-enabled/default \
    && echo "Initialize Boris..." \
    && grep '^disable_functions' /etc/php/${PHP_VER}/cli/php.ini \
    | sed -r 's/pcntl_(fork|signal|signal_dispatch|waitpid),//g' \
    > /etc/php/${PHP_VER}/cli/conf.d/99-boris.ini \
    && echo "Generate file storage directories..." \
    && perl /var/www/bin/generate-storage-dirs /var/lib/gazelle/torrent 2 100 \
    && perl /var/www/bin/generate-storage-dirs /var/lib/gazelle/riplog 2 100 \
    && perl /var/www/bin/generate-storage-dirs /var/lib/gazelle/riploghtml 2 100 \
    && chown -R gazelle:gazelle /var/lib/gazelle /var/www

ENTRYPOINT [ "/bin/bash", "/var/www/misc/docker/web/entrypoint.sh" ]
