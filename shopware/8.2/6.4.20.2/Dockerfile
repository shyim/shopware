FROM ghcr.io/shyim/shopware-nginx:8.2.8

COPY --from=composer/composer:2-bin /composer /usr/local/bin/composer

RUN cd /var/www/html && \
    wget -qq "https://releases.shopware.com/sw6/install_v6.4.20.2_b0ed0f7e489f3e6b7e1fce6a50756a0ba4abf8e0.zip" && \
    unzip -qq *.zip && \
    rm *.zip && \
    mkdir /state && \
    touch /var/www/html/install.lock && \
    echo "6.4.20.2" > /shopware_version && \
    rm -f /var/www/html/config/services/defaults_test.xml && \
    cp /etc/shopware/configs/services/jwt.xml /var/www/html/config/services/ && \
    cp /etc/shopware/configs/services/services.xml /var/www/html/config/ && \
    chown -R www-data:www-data /var/www

VOLUME /state /var/www/html/custom/plugins /var/www/html/files /var/www/html/var/log /var/www/html/public/theme /var/www/html/public/media /var/www/html/public/bundles /var/www/html/public/sitemap /var/www/html/public/thumbnail /var/www/html/config/jwt
