FROM php:8.3-cli

# System deps + PHP extensions needed by Laravel + MySQL
RUN apt-get update && apt-get install -y \
        git unzip libzip-dev libonig-dev libpng-dev \
    && docker-php-ext-install pdo pdo_mysql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN composer install --no-interaction --prefer-dist --optimize-autoloader \
    && cp -n .env.example .env \
    && php artisan key:generate --ansi

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
