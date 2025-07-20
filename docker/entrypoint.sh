#!/bin/bash

# Script de entrada para contenedor Laravel con React/Inertia
# Todas las dependencias están preinstaladas durante el build

set -e

# Función de logging
log() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] $1"
}

warning() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ⚠️ $1"
}

success() {
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ✅ $1"
}

# Función para esperar a que la base de datos esté lista
wait_for_db() {
    log "⏳ Esperando conexión a la base de datos..."
    
    max_attempts=30
    attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if php artisan migrate:status >/dev/null 2>&1; then
            success "Base de datos conectada"
            return 0
        fi
        
        attempt=$((attempt + 1))
        log "Intento $attempt/$max_attempts - Esperando base de datos..."
        sleep 2
    done
    
    warning "No se pudo conectar a la base de datos después de $max_attempts intentos"
    return 1
}

log "🚀 Iniciando entrypoint de Laravel..."

# Crear directorios necesarios si no existen
log "📁 Verificando directorios..."
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/bootstrap/cache

# Configurar permisos
log "🔐 Configurando permisos..."
chown -R appuser:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Generar APP_KEY si no existe o está vacío
if ! grep -q "^APP_KEY=." /var/www/html/.env 2>/dev/null || [ -z "$(grep '^APP_KEY=' /var/www/html/.env | cut -d'=' -f2)" ]; then
    log "🔑 Generando APP_KEY..."
    php artisan key:generate --force
fi

# Limpiar caches antes de optimizar
log "🧹 Limpiando caches..."
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Optimizaciones para producción
log "⚡ Optimizando para producción..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Esperar base de datos y ejecutar migraciones si está disponible
if wait_for_db; then
    log "🗃️ Ejecutando migraciones..."
    php artisan migrate --force
    
    # Ejecutar seeders solo si se especifica
    if [ "$RUN_SEEDERS" = "true" ]; then
        log "🌱 Ejecutando seeders..."
        php artisan db:seed --force
    fi
else
    warning "No se pudo conectar a la base de datos, continuando sin migraciones"
fi

# Crear enlace simbólico para storage público
log "🔗 Creando enlaces simbólicos..."
php artisan storage:link

success "Entrypoint completado con éxito"

# Ejecutar el comando pasado como argumento
exec "$@"
