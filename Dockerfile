ARG PHP_VERSION=7.4

FROM ghcr.io/shyim/shopware-php:${PHP_VERSION}

ARG SHOPWARE_DL=https://www.shopware.com/de/Download/redirect/version/sw6/file/install_6.2.0_1589874223.zip
ARG SHOPWARE_VERSION=6.2.0

COPY patches /usr/local/src/sw-patches

RUN cd /var/www/html && \
    wget -qq $SHOPWARE_DL && \
    unzip -qq *.zip && \
    rm *.zip && \
    mkdir /state && \
    touch /var/www/html/install.lock && \
    echo $SHOPWARE_VERSION > /shopware_version && \
    for f in /usr/local/src/sw-patches/*.patch; do patch -p1 < $f || true; done && \
    chown -R www-data:www-data /var/www

VOLUME /state /var/www/html/custom/plugins /var/www/html/files /var/www/html/var/log /var/www/html/public/theme /var/www/html/public/media /var/www/html/public/bundles /var/www/html/public/sitemap /var/www/html/public/thumbnail /var/www/html/config/jwt