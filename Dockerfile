FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libsqlite3-dev libonig-dev \
    && docker-php-ext-install pdo_sqlite mbstring \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . .
RUN printf '<Directory /var/www/html>\nAllowOverride All\nRequire all granted\n</Directory>\n' > /etc/apache2/conf-available/repotools.conf \
    && a2enconf repotools \
    && mkdir -p /var/lib/repotools \
    && chown -R www-data:www-data /var/lib/repotools

ENV REPOTOOLS_DB_PATH=/var/lib/repotools/accounts.sqlite
EXPOSE 80
CMD ["sh", "-c", "chown www-data:www-data /var/lib/repotools && exec apache2-foreground"]
