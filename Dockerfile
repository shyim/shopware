FROM php:7.4-fpm-alpine

ENV TZ=Europe/Berlin \
    APP_ENV=prod \
    MAILER_URL=null://localhost \
    SHOPWARE_ES_HOSTS= \
    SHOPWARE_ES_ENABLED=0 \
    SHOPWARE_ES_INDEXING_ENABLED=0 \
    SHOPWARE_ES_INDEX_PREFIX= \
    COMPOSER_HOME=/tmp/composer \
    SHOPWARE_HTTP_CACHE_ENABLED=1 \
    SHOPWARE_HTTP_DEFAULT_TTL=7200 \
    BLUE_GREEN_DEPLOYMENT=1 \
    INSTALL_LOCALE=en-GB \
    INSTALL_CURRENCY=EUR \
    INSTALL_ADMIN_USERNAME=admin \
    INSTALL_ADMIN_PASSWORD=shopware \
    CACHE_ADAPTER=default \
    REDIS_CACHE_HOST=redis \
    REDIS_CACHE_PORT=6379 \
    REDIS_CACHE_DATABASE=0 \
    SESSION_ADAPTER=default \
    REDIS_SESSION_HOST=redis \
    REDIS_SESSION_PORT=6379 \
    REDIS_SESSION_DATABASE=1 \
    FPM_PM=dynamic \
    FPM_PM_MAX_CHILDREN=5 \
    FPM_PM_START_SERVERS=2 \
    FPM_PM_MIN_SPARE_SERVERS=1 \
    FPM_PM_MAX_SPARE_SERVERS=3 \
    PHP_MAX_UPLOAD_SIZE=128m \
    PHP_MAX_EXECUTION_TIME=300 \
    PHP_MEMORY_LIMIT=512m \
    LD_PRELOAD="/usr/lib/preloadable_libiconv.so php"

COPY --from=ghcr.io/shyim/supervisord /usr/local/bin/supervisord /usr/bin/supervisord
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/bin/
COPY --from=ghcr.io/shyim/gnu-libiconv:v3.14 /gnu-libiconv-1.15-r3.apk /gnu-libiconv-1.15-r3.apk

RUN apk add --no-cache \
      nginx \
      shadow \
      unzip \
      wget \
      sudo \
      bash \
      patch \
      jq && \
    apk add --no-cache --allow-untrusted /gnu-libiconv-1.15-r3.apk && rm /gnu-libiconv-1.15-r3.apk && \
    install-php-extensions bcmath gd intl mysqli pdo_mysql sockets bz2 gmp soap zip ffi redis opcache && \
    ln -s /usr/local/bin/php /usr/bin/php && \
    ln -sf /dev/stdout /var/log/nginx/access.log && \
    ln -sf /dev/stderr /var/log/nginx/error.log && \
    rm -rf /var/lib/nginx/tmp && \
    ln -sf /tmp /var/lib/nginx/tmp && \
    mkdir -p /var/tmp/nginx/ || true && \
    chown -R www-data:www-data /var/lib/nginx /var/tmp/nginx/ && \
    chmod 777 -R /var/tmp/nginx/ && \
    rm -rf /tmp/* && \
    chown -R www-data:www-data /var/www && \
    usermod -u 1000 www-data

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

COPY rootfs /

VOLUME /state /var/www/html/custom/plugins /var/www/html/files /var/www/html/var/log /var/www/html/public/theme /var/www/html/public/media /var/www/html/public/bundles /var/www/html/public/sitemap /var/www/html/public/thumbnail /var/www/html/config/jwt
EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]

HEALTHCHECK --timeout=10s CMD curl --silent --fail http://127.0.0.1:80/admin
