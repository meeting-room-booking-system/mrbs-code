#!/bin/bash
set -e

# Log port usage
netstat -tuln

# Start Cloud SQL Proxy
/cloud_sql_proxy -dir=/cloudsql -instances=$INSTANCE_CONNECTION_NAME &
echo "Cloud SQL Proxy started with INSTANCE_CONNECTION_NAME=$INSTANCE_CONNECTION_NAME"

# Configure PHP error logging
sed -i 's/error_log.*/error_log = \/dev\/stderr/' /usr/local/etc/php/php.ini-production
cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini

# Enable error reporting for debugging
echo "display_errors = On" >> /usr/local/etc/php/conf.d/error-logging.ini
echo "display_startup_errors = On" >> /usr/local/etc/php/conf.d/error-logging.ini
echo "error_reporting = E_ALL" >> /usr/local/etc/php/conf.d/error-logging.ini
echo "log_errors = On" >> /usr/local/etc/php/conf.d/error-logging.ini

# Set up correct config file based on environment
if [ -n "$INSTANCE_CONNECTION_NAME" ]; then
    echo "Running in Cloud environment"
    cp /var/www/html/config.cloud.php /var/www/html/config.inc.php
else
    echo "Running in local environment"
    cp /var/www/html/config.local.php /var/www/html/config.inc.php
fi

# Update Apache port configuration if PORT is set
if [ -n "$PORT" ]; then
    echo "Configuring Apache to listen on port $PORT"
    # Update ports.conf
    echo "Listen $PORT" > /etc/apache2/ports.conf
    # Update virtual host
    sed -i "s/:8080/:$PORT/" /etc/apache2/sites-available/000-default.conf
fi

# Ensure proper permissions
chown -R www-data:www-data /var/www/html

# Start Apache in the foreground
echo "Starting Apache..."
apache2-foreground
