FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev libonig-dev libpq-dev libcurl4-openssl-dev libjpeg62-turbo-dev libpng-dev libwebp-dev unzip \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_sqlite pdo_pgsql mbstring curl gd \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts --no-plugins --optimize-autoloader
COPY . .
RUN printf '<Directory /var/www/html>\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-available/repotools.conf \
    && a2enconf repotools \
    && a2enmod rewrite headers \
    && mkdir -p /var/lib/repotools \
    && chown -R www-data:www-data /var/lib/repotools

ENV REPOTOOLS_DB_PATH=/var/lib/repotools/accounts.sqlite
ENV REPOTOOLS_REQUIRE_DATABASE_URL=1
RUN printf 'upload_max_filesize=6M\npost_max_size=14M\n' > /usr/local/etc/php/conf.d/uploads.ini
EXPOSE 80
RUN chmod +x /var/www/html/scripts/container-entrypoint.sh /var/www/html/scripts/push-loop.sh
ENTRYPOINT ["/var/www/html/scripts/container-entrypoint.sh"]
CMD ["apache2-foreground"]
