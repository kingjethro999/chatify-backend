FROM php:8.2-apache

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    libonig-dev \
    libzip-dev \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy Composer from the official Composer image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Optionally update Composer
RUN composer self-update --2

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Verify Composer is working (optional)
RUN composer --version

# Install PHP dependencies in verbose mode to get detailed output
RUN composer install --optimize-autoloader --no-dev -vvv

# Set appropriate permissions (if needed)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port 80
EXPOSE 80

# Start Apache in the foreground
CMD ["apache2-foreground"]
