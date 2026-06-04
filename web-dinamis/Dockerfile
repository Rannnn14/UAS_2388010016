FROM php:8.2-apache

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Enable Apache rewrite module (optional but good practice)
RUN a2enmod rewrite

# Copy source code into container
COPY . /var/www/html/

# Expose port 80
EXPOSE 80
