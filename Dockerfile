ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-apache

RUN a2enmod rewrite
# Older images (PHP 7.2) are based on Debian buster, whose repositories have been archived
RUN if grep -q buster /etc/apt/sources.list 2>/dev/null; then \
      sed -i -e 's|deb.debian.org|archive.debian.org|g' -e 's|security.debian.org|archive.debian.org|g' -e '/buster-updates/d' /etc/apt/sources.list; \
    fi
RUN apt-get update && apt-get install -y libicu-dev locales-all \
 && apt-get clean
RUN docker-php-ext-install mysqli pdo pdo_mysql intl

COPY web/ /var/www/html/
COPY docker-config.inc.php /var/www/html/config.inc.php
