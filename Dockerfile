FROM php:8.2-apache

# Install apt-utils first to avoid warnings
RUN apt-get update && apt-get install -y apt-utils

# Install dependencies
RUN apt-get install -y \
    libicu72 \
    libicu-dev \
    locales-all \
    wget \
    && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite
RUN docker-php-ext-install mysqli pdo pdo_mysql intl

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
RUN echo "Listen \${PORT}" >> /etc/apache2/ports.conf
RUN sed -i 's/Listen 80//' /etc/apache2/ports.conf
RUN sed -i 's/:80/:${PORT}/' /etc/apache2/sites-available/000-default.conf

# Install Cloud SQL Auth proxy v2
RUN wget https://storage.googleapis.com/cloud-sql-connectors/cloud-sql-proxy/v2.8.1/cloud-sql-proxy.linux.amd64 -O /usr/local/bin/cloud-sql-proxy && \
    chmod +x /usr/local/bin/cloud-sql-proxy

# Copy application files
COPY web/ /var/www/html/

# Copy cloud configuration
COPY cloud-deploy/cloud-config.inc.php /var/www/html/config.inc.php

# Set proper permissions for web files
RUN chown -R www-data:www-data /var/www/html/

# Install netcat for startup script
RUN apt-get update && apt-get install -y netcat-openbsd && rm -rf /var/lib/apt/lists/*

# Create directory for Cloud SQL proxy socket and set permissions
RUN mkdir -p /cloudsql && \
    chown -R www-data:www-data /cloudsql && \
    chmod 777 /cloudsql

# Create startup script
RUN echo '#!/bin/bash\n\
# Clean up any existing socket files\n\
rm -f /cloudsql/${INSTANCE_CONNECTION_NAME} 2>/dev/null\n\
\n\
# Start Cloud SQL proxy with unix socket as www-data user\n\
su www-data -s /bin/bash -c "cloud-sql-proxy --unix-socket /cloudsql --instances=${INSTANCE_CONNECTION_NAME}" & \n\
PROXY_PID=$!\n\
\n\
# Wait for the socket file to be created and verify permissions\n\
while [ ! -S "/cloudsql/${INSTANCE_CONNECTION_NAME}" ]; do\n\
  echo "Waiting for Cloud SQL Proxy socket..."\n\
  sleep 1\n\
done\n\
\n\
# Ensure socket file has correct permissions\n\
chmod 777 /cloudsql/${INSTANCE_CONNECTION_NAME}\n\
echo "Cloud SQL Proxy socket is ready with correct permissions!"\n\
\n\
# Start Apache in foreground\n\
apache2-foreground' > /usr/local/bin/startup.sh && \
    chmod +x /usr/local/bin/startup.sh

# Use the PORT environment variable
ENV PORT 8080
EXPOSE ${PORT}

CMD ["/usr/local/bin/startup.sh"]
