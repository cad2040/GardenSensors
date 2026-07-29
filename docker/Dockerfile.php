FROM php:8.3-apache-bookworm

ENV APACHE_DOCUMENT_ROOT=/var/www/html/garden-sensors/public \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip curl \
        libzip-dev \
        python3 python3-pip python3-venv \
    && docker-php-ext-install pdo pdo_mysql \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Point Apache at public/
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf \
    && sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && printf '%s\n' \
        '<Directory /var/www/html/garden-sensors/public>' \
        '    AllowOverride All' \
        '    Require all granted' \
        '    DirectoryIndex index.php index.html' \
        '</Directory>' \
        > /etc/apache2/conf-available/garden-sensors.conf \
    && a2enconf garden-sensors

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html/garden-sensors

# Install PHP deps from lockfile first (better layer caching)
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# App source
COPY . .

# Python deps for plot.php (same image for CLI parity)
RUN python3 -m venv /opt/gs-venv \
    && /opt/gs-venv/bin/pip install --no-cache-dir -r requirements.txt \
    && ln -sf /opt/gs-venv/bin/python /usr/local/bin/python \
    && ln -sf /opt/gs-venv/bin/python /usr/local/bin/python3

RUN mkdir -p cache logs \
    && chown -R www-data:www-data /var/www/html/garden-sensors

HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
  CMD curl -fsS http://127.0.0.1/login.php >/dev/null || exit 1

EXPOSE 80
