FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader

RUN rm -f bootstrap/cache/*.php

RUN chmod -R 775 storage bootstrap/cache

CMD sh -c "php artisan config:clear && php artisan route:clear && php artisan cache:clear && php artisan serve --host=0.0.0.0 --port=${PORT:-8080}"