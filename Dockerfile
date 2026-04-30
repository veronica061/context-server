FROM php:8.2-cli

RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-install pdo_sqlite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY context_service /app/context_service

WORKDIR /app/context_service
RUN composer install --no-interaction --optimize-autoloader

RUN mkdir -p database && touch database/database.sqlite && chmod 666 database/database.sqlite

RUN if [ ! -f .env ]; then \
    cp .env.example .env 2>/dev/null || echo "APP_ENV=local\nAPP_DEBUG=true\nAPP_KEY=\nDB_CONNECTION=sqlite\nDB_DATABASE=/app/context_service/database/database.sqlite" > .env; \
    fi

RUN php artisan key:generate

RUN php artisan migrate --force

EXPOSE 8000

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]