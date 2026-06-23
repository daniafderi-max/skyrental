FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy project
COPY . .

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Permissions
RUN chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache

# Laravel optimization
RUN php artisan config:cache && php artisan route:cache && php artisan view:cache

# Expose port (Render pakai 10000)
EXPOSE 10000

# Start server
CMD php artisan serve --host=0.0.0.0 --port=10000