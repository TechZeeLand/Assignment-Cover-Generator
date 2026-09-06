# syntax=docker/dockerfile:1

# ---------- Stage 1: install PHP dependencies with Composer ----------
FROM composer:2 AS vendor

WORKDIR /app
COPY composer.json ./
# --ignore-platform-reqs: this stage uses the slim `composer:2` image just to
# resolve/download packages; it doesn't have ext-gd (which mpdf/mpdf
# requires) installed. The actual runtime image below does install
# ext-gd/mbstring/zip, so it's safe to skip that check here.
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts \
    --ignore-platform-reqs

# ---------- Stage 2: runtime image (nginx + php-fpm, one container) ----------
FROM php:8.2-fpm

# nginx + supervisor to run both processes in one container,
# plus the build/runtime libs mPDF needs (GD, mbstring, zip), plus curl
# for the container HEALTHCHECK.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        curl \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" gd mbstring zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /etc/nginx/sites-enabled/default

# Keep uploads reasonably sized (font files are small, but be generous).
RUN { \
        echo "upload_max_filesize = 8M"; \
        echo "post_max_size = 10M"; \
        echo "memory_limit = 256M"; \
    } > /usr/local/etc/php/conf.d/assignment-cover-generator.ini

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

WORKDIR /var/www/html

COPY --from=vendor /app/vendor ./vendor
COPY composer.json ./
COPY public ./public
COPY src ./src

# Persisted, world-writable-by-www-data font storage (mounted as a volume).
RUN mkdir -p storage/fonts \
    && chown -R www-data:www-data /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/public

VOLUME ["/var/www/html/storage/fonts"]

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=15s \
    CMD curl -fs http://127.0.0.1/ -o /dev/null || exit 1

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
