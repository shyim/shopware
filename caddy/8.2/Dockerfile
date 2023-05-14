#syntax=docker/dockerfile:1.4

FROM php:8.2.6-fpm-alpine

ENV APP_ENV=prod \
    MAILER_URL=null://localhost \
    DATABASE_PORT=3306 \
    OPENSEARCH_URL= \
    SHOPWARE_ES_ENABLED=0 \
    SHOPWARE_ES_INDEXING_ENABLED=0 \
    SHOPWARE_ES_INDEX_PREFIX= \
    COMPOSER_HOME=/tmp/composer \
    SHOPWARE_HTTP_CACHE_ENABLED=1 \
    SHOPWARE_HTTP_DEFAULT_TTL=7200 \
    BLUE_GREEN_DEPLOYMENT=0 \
    INSTALL_LOCALE=en-GB \
    INSTALL_CURRENCY=EUR \
    INSTALL_ADMIN_USERNAME=admin \
    INSTALL_ADMIN_PASSWORD=shopware \
    FPM_PM=dynamic \
    FPM_PM_MAX_CHILDREN=5 \
    FPM_PM_START_SERVERS=2 \
    FPM_PM_MIN_SPARE_SERVERS=1 \
    FPM_PM_MAX_SPARE_SERVERS=3 \
    PHP_SESSION_HANDLER=files \
    PHP_SESSION_SAVE_PATH= \
    PHP_MAX_UPLOAD_SIZE=128m \
    PHP_MAX_EXECUTION_TIME=300 \
    PHP_MEMORY_LIMIT=512m \
    PHP_ERROR_REPORTING=E_ALL

COPY --from=ghcr.io/shyim/supervisord --link /usr/local/bin/supervisord /usr/bin/supervisord
COPY --from=mlocati/php-extension-installer --link /usr/bin/install-php-extensions /usr/bin/
COPY --from=caddy --link /usr/bin/caddy /usr/bin/caddy

RUN apk add --no-cache icu-data-full curl jq && \
    install-php-extensions bcmath gd intl mysqli pdo_mysql sockets bz2 gmp soap zip ffi redis opcache apcu amqp && \
    mkdir -p /var/www/html

USER www-data

COPY --link caddy/rootfs /

EXPOSE 8000
WORKDIR /var/www/html

ENTRYPOINT [ "/usr/bin/supervisord", "-c", "/etc/supervisord.conf" ]
