FROM php:8.2-fpm

WORKDIR /var/www

RUN apt-get update && apt-get install -y \
    git curl libpng-dev libonig-dev libxml2-dev zip unzip

RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install --no-interaction --no-dev --optimize-autoloader

RUN chown -R www-data:www-data /var/www/storage

EXPOSE 8000

CMD sh -c "until php -r \"new PDO('mysql:host=' . (getenv('DB_HOST') ?: 'db') . ';port=' . (getenv('DB_PORT') ?: 3306), getenv('DB_USERNAME') ?: 'root', getenv('DB_PASSWORD') ?: '');\" 2>/dev/null; do echo 'Waiting for database...'; sleep 2; done && php artisan config:clear && php artisan migrate --force && php artisan l5-swagger:generate && php artisan serve --host=0.0.0.0 --port=8000"