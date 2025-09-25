# Use official PHP with Apache
FROM php:8.2-apache

# Install system dependencies & PHP extensions
RUN apt-get update && apt-get install -y git unzip libzip-dev && \
    docker-php-ext-install pdo pdo_mysql zip

# Enable Apache rewrite (needed for routing in OOP frameworks)
RUN a2enmod rewrite

# Install Composer (from official image)
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Install PHP dependencies (optimize for production)
RUN composer install --no-dev --optimize-autoloader

# Make Apache serve from the /public folder
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Expose port 80 (Render will map this automatically)
EXPOSE 80
