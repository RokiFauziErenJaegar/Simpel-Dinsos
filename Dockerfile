# SIMPEL DINSOS — Container untuk Railway
# Single container: nginx + php-fpm 8.3 + supervisord
# Mendengarkan di $PORT (Railway inject otomatis, default 8080)

FROM php:8.3-fpm-alpine AS base

# ===== System deps =====
RUN apk add --no-cache \
    nginx \
    bash \
    curl \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    mariadb-client \
    nodejs \
    npm \
    git \
    unzip

# ===== PHP extensions =====
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        zip \
        intl \
        gd \
        opcache \
        bcmath \
        exif

# ===== Composer =====
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ===== App user =====
RUN addgroup -g 1000 app && adduser -G app -g app -s /bin/sh -D -u 1000 app

WORKDIR /app

# ===== Composer install (cache layer) =====
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --optimize-autoloader \
    --prefer-dist

# ===== NPM build (cache layer) =====
COPY package.json package-lock.json* ./
RUN npm ci --no-audit --no-fund

# ===== Copy aplikasi =====
COPY . .

# ===== Build asset frontend =====
RUN npm run build && rm -rf node_modules

# ===== Composer scripts (post-install) =====
RUN composer dump-autoload --optimize --no-dev

# ===== Permission =====
RUN mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
        storage/app/public storage/app/secure storage/app/private \
        bootstrap/cache \
    && chown -R app:app /app \
    && chmod -R 775 storage bootstrap/cache

# ===== Config nginx & php =====
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-app.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini

# ===== Entrypoint =====
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh \
    && chmod +x /usr/local/bin/entrypoint.sh

# ===== Nginx & php-fpm need /var/run + /var/lib/nginx =====
RUN mkdir -p /var/run/php /run/nginx /var/lib/nginx/tmp /var/lib/nginx/logs \
    && chown -R app:app /var/run/php /run/nginx /var/lib/nginx \
    && touch /var/lib/nginx/logs/error.log /var/lib/nginx/logs/access.log \
    && chown app:app /var/lib/nginx/logs/*.log

# Railway inject $PORT, default 8080
ENV PORT=8080
EXPOSE 8080

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
