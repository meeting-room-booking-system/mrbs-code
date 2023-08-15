FROM php:8.2-apache

RUN a2enmod rewrite
RUN docker-php-ext-install mysqli pdo pdo_mysql
RUN apt-get update && apt-get install -y php8.2-intl locales && \
  sed -i 's/^# *\(en_US.UTF-8\)/\1/' /etc/locale.gen && \
  sed -i 's/^# *\(en_GB.UTF-8\)/\1/' /etc/locale.gen && \
  locale-gen

COPY web/ /var/www/html/
COPY docker-config.inc.php /var/www/html/config.inc.php
