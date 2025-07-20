#!/bin/bash

# Script simple siguiendo el flujo del usuario
# 1. docker compose up -d --build
# 2. Entrar al contenedor
# 3. composer install, npm install, migrate, permisos

set -e

echo "🚀 Iniciando contenedores con Docker Compose..."
echo "📄 Usando configuración de producción (.env.prod)"
cp .env.prod .env
docker compose up -d --build

echo "⏳ Esperando que los contenedores estén listos..."
sleep 10

echo "📦 Ejecutando setup dentro del contenedor..."

# Obtener el nombre del contenedor
CONTAINER_NAME="acredita_app"

# Ejecutar comandos dentro del contenedor usando docker exec
docker exec -it -u root $CONTAINER_NAME bash -c "
    echo '📦 Instalando dependencias de Composer...'
    composer install --optimize-autoloader --no-dev

    echo '🎨 Instalando dependencias de Node.js...'
    npm install

    echo '🔨 Construyendo assets...'
    npm run build

    echo '🗄️ Ejecutando migraciones...'
    php artisan migrate --force

    echo '🌱 Ejecutando seeders...'
    php artisan db:seed --force

    echo '🔗 Creando enlaces de storage...'
    php artisan storage:link

    echo '🧹 Limpiando y optimizando caches...'
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache

    echo '🔐 Configurando permisos...'
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

    echo '✅ Setup completado!'
"

echo
echo "🎉 ¡Sistema listo!"
echo "🌐 Aplicación disponible en: http://localhost:8094"
echo "🗄️ pgAdmin disponible en: http://localhost:8095 (admin@acredita.com / admin123)"
echo
echo "📋 Comandos útiles:"
echo "   Ver logs: docker logs -f acredita_app"
echo "   Entrar al contenedor: docker exec -it -u root acredita_app bash"
echo "   Parar servicios: docker compose down"
