# =============================================================================
# ERP ELEAM — Imagen de producción (nginx + php-fpm en un solo contenedor)
# =============================================================================
# Construcción:
#   docker compose build
#
# Esta imagen:
#   1. Compila los assets frontend con Vite (Node).
#   2. Instala PHP 8.2 con las extensiones necesarias (incluido pdo_pgsql).
#   3. Instala dependencias de Composer sin paquetes de desarrollo.
#   4. Sirve la aplicación con nginx + php-fpm (supervisado).
# =============================================================================

# ── Etapa 1: compilar assets frontend (Vite) ─────────────────────────────────
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json ./
RUN npm install --no-audit --no-fund

COPY vite.config.js ./
COPY resources ./resources

RUN npm run build


# ── Etapa 2: aplicación PHP de producción ────────────────────────────────────
FROM php:8.2-fpm-alpine AS app

# Extensiones del sistema y de PHP
RUN apk add --no-cache \
        nginx \
        supervisor \
        postgresql-dev \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        curl \
        $PHPIZE_DEPS \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pdo_sqlite \
        bcmath \
        intl \
        opcache \
        zip \
    && apk del $PHPIZE_DEPS

# Composer oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Configuración de PHP para producción
COPY docker/php.ini /usr/local/etc/php/conf.d/99-eleam.ini

# Dependencias PHP primero (aprovecha la caché de capas de Docker)
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

# Código de la aplicación
COPY . .

# Assets compilados desde la etapa 1
COPY --from=assets /app/public/build ./public/build

# Descubre paquetes, publica assets de Filament y crea el enlace de storage
RUN mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && composer dump-autoload --optimize --no-interaction \
    && php artisan package:discover --ansi \
    && php artisan filament:upgrade \
    && php artisan storage:link --force

# Configuración de nginx, supervisor y entrypoint
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/eleam.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Permisos de escritura para storage y caché
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwx storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/eleam.conf"]
