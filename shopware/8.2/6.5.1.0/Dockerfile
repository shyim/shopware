FROM ghcr.io/shyim/shopware-nginx:8.2.9

COPY --from=composer/composer:2-bin /composer /usr/local/bin/composer

RUN cd /var/www/ &&\
    rm -rf /var/www/html && \
    composer create-project shopware/production:6.5.1.0 /var/www/html && \
    cd /var/www/html && \
    mkdir /state && \
    touch /var/www/html/install.lock && \
    echo "6.5.1.0" > /shopware_version && \
    chown -R www-data:www-data /var/www

VOLUME /state /var/www/html/custom/plugins /var/www/html/files /var/www/html/var/log /var/www/html/public/theme /var/www/html/public/media /var/www/html/public/bundles /var/www/html/public/sitemap /var/www/html/public/thumbnail /var/www/html/config/jwt
