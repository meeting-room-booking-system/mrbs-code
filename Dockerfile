FROM php:8.4-apache

RUN a2enmod rewrite
RUN apt-get update && apt-get install -y libicu-dev locales-all \
 && apt-get clean
RUN docker-php-ext-install mysqli pdo pdo_mysql intl

COPY web/ /var/www/html/
COPY docker-config.inc.php /var/www/html/config.inc.php
