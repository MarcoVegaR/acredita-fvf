normalmente el despliegue es doc#!/bin/bash

# Script para iniciar el sistema de acreditación FVF en Docker
# Uso: ./docker-start.sh [dev|prod]

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Función para logging
log() {
    echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

success() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] ✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}[$(date +'%Y-%m-%d %H:%M:%S')] ⚠️  $1${NC}"
}

error() {
    echo -e "${RED}[$(date +'%Y-%m-%d %H:%M:%S')] ❌ $1${NC}"
}

# Verificar que Docker esté instalado
if ! command -v docker &> /dev/null; then
    error "Docker no está instalado"
    exit 1
fi

if ! docker compose version &> /dev/null; then
    error "Docker Compose no está instalado o no es la versión v2"
    exit 1
fi

# Determinar el modo (desarrollo o producción)
MODE=${1:-prod}

log "🚀 Iniciando Sistema de Acreditación FVF en modo: $MODE"

# Verificar que el archivo .env.prod existe
if [ "$MODE" = "prod" ] && [ ! -f .env.prod ]; then
    error "Archivo .env.prod no encontrado"
    exit 1
fi

# Crear archivo .env basado en el modo
if [ "$MODE" = "prod" ]; then
    log "📄 Copiando configuración de producción..."
    cp .env.prod .env
elif [ "$MODE" = "dev" ]; then
    log "📄 Usando configuración de desarrollo..."
    if [ ! -f .env ]; then
        cp .env.example .env
        warning "Archivo .env creado desde .env.example. Revisa la configuración."
    fi
fi

# Crear directorios necesarios
log "📁 Creando directorios necesarios..."
mkdir -p storage/app/public
mkdir -p storage/framework/{cache,sessions,views}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Parar contenedores existentes si están corriendo
log "🛑 Deteniendo contenedores existentes..."
docker compose down --remove-orphans 2>/dev/null || true

# Construir e iniciar contenedores
log "🔨 Construyendo e iniciando contenedores..."
if [ "$MODE" = "dev" ]; then
    docker compose --profile development up -d --build
else
    docker compose up -d --build
fi

# Esperar a que los servicios estén listos
log "⏳ Esperando a que los servicios estén listos..."
sleep 10

# Verificar que los contenedores estén corriendo
if ! docker compose ps --services --filter "status=running" | grep -q "app"; then
    error "El contenedor de la aplicación no está corriendo"
    docker compose logs app
    exit 1
fi

# Mostrar estado de los servicios
log "📊 Estado de los servicios:"
docker compose ps

# URLs útiles
success "Sistema iniciado correctamente!"
echo
echo "🌐 URLs disponibles:"
echo "   Aplicación: http://localhost:8094"
if [ "$MODE" = "dev" ]; then
    echo "   pgAdmin: http://localhost:8095 (admin@acredita.com / admin123)"
    echo "   Mailhog: http://localhost:8096"
fi
echo "   Base de datos PostgreSQL: localhost:5435"
echo
echo "📋 Comandos útiles:"
echo "   Ver logs: docker compose logs -f app"
echo "   Entrar al contenedor: docker compose exec app bash"
echo "   Parar servicios: docker compose down"
echo "   Reiniciar: ./docker-start.sh $MODE"
echo
echo "🔧 Pasos manuales recomendados:"
echo "   1. docker compose exec app bash"
echo "   2. composer install --optimize-autoloader"
echo "   3. npm install && npm run build"
echo "   4. php artisan migrate --force"
echo "   5. php artisan db:seed --force (opcional)"
echo "   6. chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache"

# Verificar el health check
log "🔍 Verificando health check..."
sleep 5
if curl -f http://localhost:8094/health &> /dev/null; then
    success "Health check pasó correctamente"
else
    warning "Health check falló - revisa los logs: docker-compose logs app"
fi

echo
success "🎉 ¡Sistema de Acreditación FVF listo para usar!"
