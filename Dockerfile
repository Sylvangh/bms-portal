# Use PHP with Apache
FROM php:8.2-apache

# Install MySQL extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy all your BMS PHP files into Apache's web root
COPY . /var/www/html/

# Expose port 80 (Render expects this)
EXPOSE 80
