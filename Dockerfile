FROM shyim/shopware-platform-nginx-production:php74

ENV APP_ENV=prod \
    MAILER_URL=null://localhost \
    SHOPWARE_ES_HOSTS= \
    SHOPWARE_ES_ENABLED=0 \
    SHOPWARE_ES_INDEXING_ENABLED=0 \
    SHOPWARE_ES_INDEX_PREFIX= \
    COMPOSER_HOME=/tmp/composer \
    SHOPWARE_HTTP_CACHE_ENABLED=1 \
    SHOPWARE_HTTP_DEFAULT_TTL=7200 \
    INSTALL_LOCALE=en-GB \
    INSTALL_CURRENCY=EUR \
    INSTALL_ADMIN_USERNAME=admin \
    INSTALL_ADMIN_PASSWORD=shopware


ARG SHOPWARE_DL=https://www.shopware.com/de/Download/redirect/version/sw6/file/install_6.2.0_1589874223.zip
ARG SHOPWARE_VERSION=6.2.0

RUN cd /var/www/html && \
	wget $SHOPWARE_DL && \
    unzip *.zip && \
    rm *.zip && \
    mkdir /state && \
    apk add --no-cache sudo && \
    touch /var/www/html/install.lock && \
    echo $SHOPWARE_VERSION > /shopware_version && \
    chown -R 1000 /var/www/html

COPY rootfs / 

VOLUME /state /var/www/html/custom/plugins /var/www/html/files /var/www/html/var/log /var/www/html/public/theme /var/www/html/public/media /var/www/html/public/bundles /var/www/html/public/sitemap /var/www/html/public/thumbnail

CMD ["/bin/sh", "/entrypoint.sh"]