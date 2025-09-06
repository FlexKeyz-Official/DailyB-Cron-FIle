FROM php:8.2-apache

# Install dependencies
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite
RUN a2enmod rewrite

# Copy project files into container
COPY . /var/www/html/

# Set working directory
WORKDIR /var/www/html/

# Expose HTTP port
EXPOSE 80
