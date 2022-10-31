#!/usr/bin/env bash

for f in /etc/shopware/scripts/on-startup/*; do source $f; done

if [[ "$RUN_NGINX" == "1" ]]; then
    nginx -c /etc/nginx/cron.conf
fi

sudo -E -u www-data php /var/www/html/bin/console ${@:2}