FROM php:7.3-apache

RUN a2enmod rewrite
RUN docker-php-ext-install mysqli

#COPY docker_app/php.ini/ $PHP_INI_DIR/conf.d/
RUN docker-php-ext-install mysqli pdo pdo_mysql

COPY web/ /var/www/html/
COPY docker-config.inc.php /var/www/html/config.inc.php
