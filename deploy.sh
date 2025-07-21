#!/bin/bash
# Script de despliegue seguro para Acredita FVF
# Asegura la correcta configuración y reconstrucción de todos los recursos

set -e  # Salir inmediatamente si algún comando falla

echo "=== INICIANDO DESPLIEGUE DE ACREDITA FVF ==="
echo "$(date)"

# 1. Actualizar desde Git
echo "Actualizando desde Git..."
git pull origin main

# 2. Asegurar que existen directorios críticos antes de recompilar
echo "Creando directorios críticos localmente..."
mkdir -p public/fonts
chmod -R 755 public/fonts

# 3. Verificar archivo de fuente
if [ ! -f "public/fonts/arial.ttf" ]; then
    echo "ADVERTENCIA: Archivo arial.ttf no encontrado localmente. Descargando..."
    curl -o public/fonts/arial.ttf https://github.com/matomo-org/travis-scripts/raw/master/fonts/Arial.ttf
    chmod 644 public/fonts/arial.ttf
else
    echo "✅ Archivo de fuente arial.ttf encontrado localmente"
fi

# 4. Reconstruir contenedores
echo "Reconstruyendo contenedores Docker..."
docker compose down
docker compose up -d --build

# 5. Esperar a que el contenedor esté listo
echo "Esperando a que el contenedor de la aplicación esté listo..."
sleep 10

# 6. Verificar y crear directorio fonts en el contenedor
echo "Verificando directorio fonts en el contenedor..."
if ! docker exec acredita_app ls -la public/fonts > /dev/null 2>&1; then
    echo "Creando directorio fonts en el contenedor..."
    docker exec acredita_app mkdir -p public/fonts
    docker exec acredita_app chmod -R 755 public/fonts
fi

# 7. Verificar y copiar fuente al contenedor
echo "Copiando archivo de fuente al contenedor..."
docker cp public/fonts/arial.ttf acredita_app:/var/www/html/public/fonts/

# 8. Instalar dependencias y compilar assets
echo "Instalando dependencias y compilando assets..."
docker exec acredita_app composer install --no-interaction
docker exec acredita_app npm install
docker exec acredita_app npm run build

# 9. Limpiar caché
echo "Limpiando caché..."
docker exec acredita_app php artisan config:clear
docker exec acredita_app php artisan cache:clear
docker exec acredita_app php artisan optimize:clear

# 10. Verificar estado final
echo "Verificando estado final del despliegue..."
echo "Directorio fonts en el contenedor:"
docker exec acredita_app ls -la public/fonts

# 11. Verificar que el servicio está funcionando
echo "Verificando que el servicio está funcionando..."
if curl -s http://localhost:8094/health | grep -q "ok"; then
    echo "✅ DESPLIEGUE EXITOSO - Servicio funcionando correctamente"
else
    echo "⚠️ ADVERTENCIA: El servicio puede no estar funcionando correctamente"
fi

echo "=== FIN DEL DESPLIEGUE ==="
echo "$(date)"
