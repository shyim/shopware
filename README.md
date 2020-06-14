# What is Shopware?

Shopware is a trendsetting ecommerce platform to power your online business. Our ecommerce solution offers the perfect combination of beauty & brains you need to build and customize a fully responsive online store.

![Shopware Logo](https://assets.shopware.com/media/logos/shopware_logo_blue.svg)


# How to use this image

To run Shopware 6 you will neeed an compatible MySQL or MariaDB container.

Smallest example with docker-compose

```yaml
version: "3.8"
services:
  mysql:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: shopware
      MYSQL_USER: shopware
      MYSQL_PASSWORD: shopware
  shopware:
    image: shyim/shopware:6.2.0
    environment:
      APP_SECRET: 440dec3766de53010c5ccf6231c182acfc90bd25cff82e771245f736fd276518
      INSTANCE_ID: 10612e3916e153dd3447850e944a03fabe89440970295447a30a75b151bd844e
      APP_URL: http://localhost
      DATABASE_HOST: mysql
      DATABASE_URL: mysql://shopware:shopware@mysql:3306/shopware
    ports:
      - 80:80
```

The installation will be accessible at `http://localhost`. The default credentials for the administration are `admin` and `shopware` as password.

Following environment can be set:

| Variable                     | Default Value    | Description                                            |
|------------------------------|------------------|--------------------------------------------------------|
| APP_ENV                      | prod             | Environment                                            |
| APP_SECRET                   | (empty)          | Can be generated with `openssl rand -hex 32`           |
| APP_URL                      | (empty)          | Where Shopware will be accessible                      |
| INSTANCE_ID                  | (empty)          | Unique Identifier for the Store: Can be generated with `openssl rand -hex 32`                        |
| DATABASE_HOST                | (empty)          | Host of MySQL (needed for for checking is MySQL alive) |
| DATABASE_URL                 | (empty)          | MySQL credentials as DSN                               |
| MAILER_URL                   | null://localhost | Mailer DSN (Admin Configuration overwrites this)       |
| SHOPWARE_ES_HOSTS            | (empty)          | Elasticsearch Hosts                                    |
| SHOPWARE_ES_ENABLED          | 0                | Elasticsearch Support Enabled?                         |
| SHOPWARE_ES_INDEXING_ENABLED | 0                | Elasticsearch Indexing Enabled?                        |
| SHOPWARE_ES_INDEX_PREFIX     | (empty)          | Elasticsearch Index Prefix                             |
| COMPOSER_HOME                | /tmp/composer    | Caching for the Plugin Manager                         |
| SHOPWARE_HTTP_CACHE_ENABLED  | 1                | Is HTTP Cache enabled?                                 |
| SHOPWARE_HTTP_DEFAULT_TTL    | 7200             | Default TTL for Http Cache                             |
| INSTALL_LOCALE               | en-GB            | Default locale for the Shop                            |
| INSTALL_CURRENCY             | EUR              | Default currency for the Shop                          |
| INSTALL_ADMIN_USERNAME       | admin            | Default admin username                                 |
| INSTALL_ADMIN_PASSWORD       | shopware         | Default admin password                                 |

When Shopware with SSL behind a reverse proxy such as NGINX which is responsible for doing TLS termination, be sure configure [Trusted Headers](https://symfony.com/doc/current/deployment/proxies.html).-

# Updates

When you update the image version, automaticly all required migrations will run. Downgrade works in similar way. Please check before here the Blue-Green compability of Shopware.

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