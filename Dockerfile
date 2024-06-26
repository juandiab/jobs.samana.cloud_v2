# Use the official PHP image as the base image
FROM php:8.1-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install mysqli pdo pdo_mysql

# Enable mod_rewrite
RUN a2enmod rewrite

# Set ServerName to avoid Apache warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application source including google-login
COPY html/ /var/www/html/
COPY config/config.php /var/www/config/config.php

# Copy .env file and other configuration files if needed
COPY .env /var/www/html/.env

# Use ARG to accept build-time variables
ARG GOOGLE_CLIENT_ID
ARG GOOGLE_CLIENT_SECRET
ARG MYSQL_ROOT_PASSWORD
ARG MYSQL_DATABASE
ARG MYSQL_USER
ARG MYSQL_PASSWORD
ARG APP_HOSTNAME

# Set environment variables from build-time ARGs
ENV GOOGLE_CLIENT_ID=${GOOGLE_CLIENT_ID}
ENV GOOGLE_CLIENT_SECRET=${GOOGLE_CLIENT_SECRET}
ENV MYSQL_ROOT_PASSWORD=${MYSQL_ROOT_PASSWORD}
ENV MYSQL_DATABASE=${MYSQL_DATABASE}
ENV MYSQL_USER=${MYSQL_USER}
ENV MYSQL_PASSWORD=${MYSQL_PASSWORD}
ENV APP_HOSTNAME=${APP_HOSTNAME}

# Expose ports
EXPOSE 80

# Set the entrypoint
CMD ["apache2-foreground"]
