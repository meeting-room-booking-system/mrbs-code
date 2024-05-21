FROM php:7.3-apache

RUN a2enmod rewrite
RUN apt-get update && apt-get install -y libicu72 libicu-dev locales-all
RUN docker-php-ext-install mysqli pdo pdo_mysql 