FROM php:8.4-cli

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --no-interaction

# Run in the mounted folder: reads ./loto_config.ini, writes the CSVs there
WORKDIR /work
ENTRYPOINT ["php", "/app/sort_donation.php"]
