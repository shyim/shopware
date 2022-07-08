# Shopware MyParcel Docker

Special docker image for coding on the MyParcel plugin for Shopware.

# How to use this image

Adjust the **docker-compose.yml** to change the default values and spin the container up.
The installation will be accessible at `http://localhost`. The default credentials for the administration are `admin` and `shopware` as password.
```yaml
docker compose up -d
```


# Following environment can be set:

| Variable                     | Default Value    | Description                                             |
|------------------------------|------------------|---------------------------------------------------------|
| APP_ENV                      | prod             | Environment                                             |
| APP_SECRET                   | (empty)          | Can be generated with `openssl rand -hex 32`            |
| APP_URL                      | (empty)          | Where Shopware will be accessible                       |
| INSTANCE_ID                  | (empty)          | Unique Identifier for the Store: Can be generated with `openssl rand -hex 32`                        |
| DATABASE_HOST                | (empty)          | Host of MySQL (needed for for checking is MySQL alive)  |
| BLUE_GREEN_DEPLOYMENT        | 1                | This needs super priviledge to create trigger           |
| DATABASE_URL                 | (empty)          | MySQL credentials as DSN                                |
| DATABASE_SSL_CA              | (empty)          | Path to SSL CA file (needs to be readable for uid 1000) |
| DATABASE_SSL_CERT            | (empty)          | Path to SSL Cert file (needs to be readable for uid 1000) |
| DATABASE_SSL_KEY             | (empty)          | Path to SSL Key file (needs to be readable for uid 1000) |
| DATABASE_SSL_DONT_VERIFY_SERVER_CERT | (empty)          | Disables verification of the server certificate (1 disables it) |
| MAILER_URL                   | null://localhost | Mailer DSN (Admin Configuration overwrites this)        |
| SHOPWARE_ES_HOSTS            | (empty)          | Elasticsearch Hosts                                     |
| SHOPWARE_ES_ENABLED          | 0                | Elasticsearch Support Enabled?                          |
| SHOPWARE_ES_INDEXING_ENABLED | 0                | Elasticsearch Indexing Enabled?                         |
| SHOPWARE_ES_INDEX_PREFIX     | (empty)          | Elasticsearch Index Prefix                              |
| COMPOSER_HOME                | /tmp/composer    | Caching for the Plugin Manager                          |
| SHOPWARE_HTTP_CACHE_ENABLED  | 1                | Is HTTP Cache enabled?                                  |
| SHOPWARE_HTTP_DEFAULT_TTL    | 7200             | Default TTL for Http Cache                              |
| SHOPWARE_AUTOMATICALLY_EMPTY_CACHE_ENABLED | false            | Empty cache automatically. See [Caches & Indexes > Empty cache automatically](https://docs.shopware.com/en/shopware-6-en/configuration/caches-indexes#empty-cache-automatically) |
| SHOPWARE_EMPTY_CACHE_INTERVAL| 86400 (24 hours) | Interval with which to clear the cache in seconds.      |
| DISABLE_ADMIN_WORKER         | false            | Disables the admin worker                               |
| INSTALL_LOCALE               | en-GB            | Default locale for the Shop                             |
| INSTALL_CURRENCY             | EUR              | Default currency for the Shop                           |
| INSTALL_ADMIN_USERNAME       | admin            | Default admin username                                  |
| INSTALL_ADMIN_PASSWORD       | shopware         | Default admin password                                  |
| CACHE_ADAPTER                | default          | Set this to redis to enable redis caching               |
| REDIS_CACHE_HOST             | redis            | Host for redis caching                                  |
| REDIS_CACHE_PORT             | 6379             | Redis cache port                                        |
| REDIS_CACHE_DATABASE         | 0                | Redis database index                                    |
| SESSION_ADAPTER              | default          | Set this to redis to enable redis session adapter       |
| REDIS_SESSION_HOST           | redis            | Host for redis session                                  |
| REDIS_SESSION_PORT           | 6379             | Redis session port                                      |
| REDIS_SESSION_DATABASE       | 0                | Redis session index                                     |
| ACTIVE_PLUGINS               | (empty)          | A list of plugins which should be installed and updated |
| TZ                           | Europe/Berlin    | PHP default timezone                                    |
| PHP_MAX_UPLOAD_SIZE          | 128m             | See php documentation                                   |
| PHP_MAX_EXECUTION_TIME       | 300              | See php documentation                                   |
| PHP_MEMORY_LIMIT             | 512m             | See php documentation                                   |
| FPM_PM                       | dynamic          | See php fpm documentation                               |
| FPM_PM_MAX_CHILDREN          | 5                | See php fpm documentation                               |
| FPM_PM_START_SERVERS         | 2                | See php fpm documentation                               |
| FPM_PM_MIN_SPARE_SERVERS     | 1                | See php fpm documentation                               |
| FPM_PM_MAX_SPARE_SERVERS     | 3                | See php fpm documentation                               |


# Volumes

| Path                           | Description                                     |
|--------------------------------|-------------------------------------------------|
| /state                         | Contains state about current installed version. |
| /var/www/html/custom/plugins   | Installed plugins                               |
| /var/www/html/files            | Documents and other private files               |
| /var/www/html/var/log          | Logs                                            |
| /var/www/html/public/theme     | Compiled theme files                            |
| /var/www/html/public/media     | Uploaded files                                  |
| /var/www/html/public/bundles   | Bundle Assets                                   |
| /var/www/html/public/sitemap   | Sitemap                                         |
| /var/www/html/public/thumbnail | Generated Thumbnails                            |
| /var/www/html/config/jwt       | JWT Certificate for API                         |



## Additional hooks

* To run script on installation, add a new file to `/etc/shopware/scripts/on-install/xx.sh`
* To run script on update, add a new file to `/etc/shopware/scripts/on-update/xx.sh`
* To run script on startup, add a new file to `/etc/shopware/scripts/on-startup/xx.sh`
