FROM php:8.4-cli

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/* \
    && printf 'upload_max_filesize = 2M\npost_max_size = 40M\nmax_file_uploads = 20\n' \
        > "$PHP_INI_DIR/conf.d/uploads.ini"

WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-autoloader --no-scripts

COPY . .
RUN composer dump-autoload --no-interaction

EXPOSE 8080

# Command line by default: run in the mounted folder, reads ./loto_config.ini and writes the CSVs there
WORKDIR /work
ENTRYPOINT ["php", "/app/sort_donation.php"]
