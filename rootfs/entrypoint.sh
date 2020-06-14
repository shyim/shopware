#!/usr/bin/env bash

until nc -z -v -w30 $DATABASE_HOST 3306
do
  echo "Waiting for database connection..."
  # wait for 5 seconds before check again
  sleep 5
done

if [[ -e /state/installed_version ]]; then
    IMAGE_VERSION=$(cat /shopware_version)
    INSTALLED_VERSION=$(cat /state/installed_version)

    if [[ $IMAGE_VERSION != $INSTALLED_VERSION ]]; then
        sudo -E -u www-data php /var/www/html/bin/console system:update:finish
    fi
else
   chown -R 1000 /var/www/html/custom/plugins /var/www/html/files /var/www/html/var/log /var/www/html/public/theme /var/www/html/public/media /var/www/html/public/bundles /var/www/html/public/sitemap /var/www/html/public/thumbnail /var/www/html/config/jwt
   sudo -E -u www-data php /var/www/html/bin/console system:install --create-database --force
   sudo -E -u www-data php /var/www/html/bin/console system:generate-jwt-secret
   sudo -E -u www-data php /fix-install.php
   sudo -E -u www-data php /var/www/html/bin/console user:create $INSTALL_ADMIN_USERNAME --admin --password=$INSTALL_ADMIN_PASSWORD -n
   sudo -E -u www-data php /var/www/html/bin/console sales-channel:create:storefront --name=Storefront --url=$APP_URL
   sudo -E -u www-data php /var/www/html/bin/console theme:change --all Storefront
   cp /shopware_version /state/installed_version
fi

for f in /etc/shopware/scripts/*; do source $f; done

/usr/bin/supervisord -c /etc/supervisord.conf
