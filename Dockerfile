FROM php:8.5-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
    git unzip ca-certificates curl nodejs yarnpkg \
    libpng-dev libjpeg-dev libfreetype6-dev libicu-dev libzip-dev libonig-dev \
    sqlite3 libsqlite3-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_sqlite gd intl zip mbstring \
    && a2enmod rewrite headers \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
COPY . /var/www/html

RUN cp config-dist.php data/config.php \
    && mkdir -p data \
    && chown -R www-data:www-data /var/www/html \
    && composer install --no-interaction --no-progress --prefer-dist --no-dev --optimize-autoloader \
    && yarnpkg install --frozen-lockfile --production=true \
    && rm -f /etc/apache2/sites-available/000-default.conf \
    && printf '%s\n' \
        '<VirtualHost *:80>' \
        '    DocumentRoot /var/www/html/public' \
        '    <Directory /var/www/html/public>' \
        '        AllowOverride All' \
        '        Require all granted' \
        '        Options FollowSymLinks' \
        '    </Directory>' \
        '</VirtualHost>' \
        > /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite

EXPOSE 80

CMD ["apache2-foreground"]
