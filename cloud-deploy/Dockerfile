# Use multi-stage build to copy Cloud SQL Proxy
FROM gcr.io/cloudsql-docker/gce-proxy:1.19.1 AS cloud_sql_proxy

FROM php:8.2-apache

# Copy Cloud SQL Proxy binary
COPY --from=cloud_sql_proxy /cloud_sql_proxy /cloud_sql_proxy

# Create the cloudsql directory
RUN mkdir -p /cloudsql

# Install dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libicu72 \
    libicu-dev \
    locales-all \
    gettext-base \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql intl

# Configure Apache with a default port (will be updated at runtime)
RUN echo "Listen 8080" > /etc/apache2/ports.conf \
    && echo "<VirtualHost *:8080>\n\
    ServerAdmin \${ADMIN_EMAIL}\n\
    DocumentRoot /var/www/html\n\
    ErrorLog /dev/stderr\n\
    CustomLog /dev/stdout combined\n\
    <Directory /var/www/html>\n\
        Options Indexes FollowSymLinks\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    </VirtualHost>" > /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite headers \
    && a2ensite 000-default

# Copy application files
COPY web/ /var/www/html/

# Copy both config files
COPY cloud-deploy/cloud-config.inc.php /var/www/html/config.cloud.php
COPY cloud-deploy/local-config.inc.php /var/www/html/config.local.php

# Copy and set up entrypoint
COPY cloud-deploy/docker-entrypoint.sh /usr/local/bin/

# Set proper permissions
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
RUN chmod -R 755 /var/www/html

# Default port (will be overridden by Cloud Run)
ENV PORT=8080

# Enable Apache envvars
RUN sed -i 's/#\(.*\)mod_env.so/\1mod_env.so/' /etc/apache2/mods-available/env.load \
    && a2enmod env

# Set entrypoint
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# Expose port
EXPOSE 8080
