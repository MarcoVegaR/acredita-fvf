#!/bin/bash
set -e

# Función para logging
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

log "🚀 Iniciando entrypoint de Laravel..."

# Función para verificar si las dependencias están instaladas
check_dependencies() {
    if [ -f /var/www/html/vendor/autoload.php ]; then
        log "✅ Dependencias encontradas"
        return 0
    else
        log "⚠️ vendor/autoload.php no existe"
        log "⚠️ Por favor ejecuta 'composer install' manualmente dentro del contenedor"
        return 1
    fi
}

# Esperar a que la base de datos esté disponible
wait_for_db() {
    if [ -n "$DB_HOST" ] && [ "$DB_CONNECTION" != "sqlite" ]; then
        # Solo intentar si las dependencias están instaladas
        if check_dependencies; then
            log "⏳ Esperando conexión a la base de datos..."
            until php artisan migrate:status >/dev/null 2>&1; do
                log "⏳ Esperando a que la base de datos esté disponible..."
                sleep 2
            done
            log "✅ Base de datos disponible"
        else
            log "⏳ Omitiendo comprobación de base de datos (dependencias no instaladas)"
        fi
    fi
}

# Crear directorios necesarios
log "📁 Creando directorios necesarios..."
mkdir -p /var/log/supervisor
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/{cache,sessions,views}
mkdir -p /var/www/html/storage/app/public

# Configurar permisos
log "🔐 Configurando permisos..."
chown -R appuser:www-data /var/www/html/storage /var/www/html/bootstrap/cache
find /var/www/html/storage -type f -exec chmod 664 {} \;
find /var/www/html/storage -type d -exec chmod 775 {} \;
find /var/www/html/bootstrap/cache -type f -exec chmod 664 {} \;
find /var/www/html/bootstrap/cache -type d -exec chmod 775 {} \;

# Cambiar al usuario de la aplicación para comandos de Laravel
cd /var/www/html

# Solo ejecutar comandos Laravel si las dependencias están instaladas
if check_dependencies; then
    # Generar APP_KEY si no existe
    if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
        log "🔑 Generando APP_KEY..."
        php artisan key:generate --force
    fi

    # Cache de configuración para mejor performance en producción
    if [ "$APP_ENV" = "production" ]; then
        log "⚡ Optimizando para producción..."
        
        # Verificar que todas las configuraciones estén correctas
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
        
        # Cache de producción
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        
        # Optimización de composer
        composer dump-autoload --optimize --no-dev --classmap-authoritative
    else
        log "🔧 Modo desarrollo - limpiando caches..."
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
    fi

    # Ejecutar migraciones
    if [ "$RUN_MIGRATIONS" = "true" ]; then
        log "🗄️ Ejecutando migraciones..."
        wait_for_db
        php artisan migrate --force
    fi

    # Ejecutar seeders
    if [ "$RUN_SEEDERS" = "true" ]; then
        log "🌱 Ejecutando seeders..."
        wait_for_db
        php artisan db:seed --force
    fi

    # Crear link simbólico para storage
    if [ ! -L /var/www/html/public/storage ]; then
        log "🔗 Creando link simbólico para storage..."
        php artisan storage:link
    fi

    # Limpiar y reiniciar caches si es necesario
    if [ "$CLEAR_CACHE" = "true" ]; then
        log "🧹 Limpiando caches..."
        php artisan cache:clear
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
    fi
else
    log "⚠️ Omitiendo comandos Laravel - Se requiere instalación manual"
    log "⚠️ Después de iniciar el contenedor, ejecuta:"
    log "    docker exec -it -u root acredita_app bash"
    log "    cd /var/www/html"
    log "    composer install"
    log "    npm install && npm run build"
    log "    php artisan migrate"
fi

log "✅ Entrypoint completado con éxito"

# Ejecutar el comando pasado como parámetros
exec "$@"
