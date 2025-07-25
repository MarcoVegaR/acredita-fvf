# Imagen base para aplicación Laravel con React/Inertia
FROM php:8.2-fpm-alpine AS php-base

# Instalar dependencias del sistema
# Instalar dependencias del sistema y de desarrollo
RUN apk add --no-cache \
    nginx \
    supervisor \
    bash \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    postgresql-dev \
    mysql-client \
    imagemagick \
    imagemagick-dev \
    # Dependencias temporales para compilación
    && apk add --virtual .build-deps \
    $PHPIZE_DEPS \
    autoconf \
    g++ \
    make \
    # Configurar y compilar extensiones PHP
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        zip \
        gd \
        intl \
        opcache \
        bcmath \
        xml \
        soap \
    # Instalar ImageMagick extension
    && pecl install imagick \
    && docker-php-ext-enable imagick \
    # Limpiar dependencias de desarrollo
    && apk del .build-deps

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Instalar Node.js y NPM
RUN apk add --no-cache nodejs npm

# Configurar usuario
RUN adduser -D -s /bin/bash -u 1000 appuser \
    && mkdir -p /var/www/html \
    && chown -R appuser:appuser /var/www/html

WORKDIR /var/www/html

# Copiar archivos de dependencias primero para aprovechar cache de Docker
COPY --chown=appuser:appuser composer.json composer.lock package.json package-lock.json ./

# Crear archivo .env desde .env.prod para build
COPY --chown=appuser:appuser .env.prod .env

# Cambiar a usuario appuser para instalaciones
USER appuser

# Instalar dependencias de Composer (sin dev para producción, sin scripts)
RUN composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist --no-scripts

# Instalar Faker específicamente para seeders en producción (sin scripts)
RUN composer require fakerphp/faker --no-interaction --no-scripts

# Instalar dependencias de NPM
RUN npm ci --only=production

# Copiar el resto del código fuente
USER root
COPY --chown=appuser:appuser . .

# Cambiar a appuser para ejecutar scripts de Composer
USER appuser

# Ejecutar scripts de Composer ahora que artisan está disponible
RUN composer run-script post-autoload-dump \
    && php artisan package:discover --ansi

# Cambiar de vuelta a root
USER root

# Crear TODOS los directorios necesarios y configurar permisos para EC2
RUN mkdir -p storage/logs storage/framework/{cache,sessions,views} bootstrap/cache public/build public/fonts \
    && mkdir -p /var/log/supervisor \
    # Directorios para credenciales (según config/credentials.php)
    && mkdir -p storage/app/public/credentials/{qr,images,pdf} \
    # Directorios para templates
    && mkdir -p storage/app/public/templates/events \
    # Directorios para lotes de impresión
    && mkdir -p storage/app/public/print-batches \
    # Directorios legacy para compatibilidad
    && mkdir -p storage/app/public/qr-codes storage/app/public/pdfs \
    # Configurar ownership inicial (será sobrescrito en entrypoint para EC2)
    && chown -R appuser:www-data storage bootstrap/cache public/build public/fonts \
    # Configurar permisos 777 para todos los directorios críticos
    && chmod -R 777 storage \
    && chmod -R 777 bootstrap/cache

# Cambiar a appuser para compilar assets
USER appuser

# Compilar assets para producción
RUN npm run build

# Cambiar de vuelta a root para configuraciones finales
USER root

# Configurar Nginx
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Configurar PHP-FPM
COPY docker/php/php.ini /usr/local/etc/php/php.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Configurar Supervisor
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Script de entrada
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Exponer puertos
EXPOSE 80 443

# Healthcheck
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost/health || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
