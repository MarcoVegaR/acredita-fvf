#!/bin/bash

# Script de validación de permisos para EC2
# Ejecutar después del despliegue: docker exec -it acredita_app /var/www/html/docker/validate-permissions.sh

echo "🔍 Validando configuración de directorios y permisos..."
echo ""

# Función para verificar directorio
check_directory() {
    local dir="$1"
    local expected_perm="$2"
    local expected_owner="$3"
    
    if [ -d "$dir" ]; then
        local actual_perm=$(stat -c "%a" "$dir")
        local actual_owner=$(stat -c "%U:%G" "$dir")
        
        echo -n "📁 $dir: "
        
        if [ "$actual_perm" = "$expected_perm" ] && [ "$actual_owner" = "$expected_owner" ]; then
            echo "✅ OK ($actual_perm $actual_owner)"
        else
            echo "❌ FAIL (actual: $actual_perm $actual_owner, expected: $expected_perm $expected_owner)"
            return 1
        fi
    else
        echo "❌ $dir: NO EXISTE"
        return 1
    fi
    return 0
}

# Función para verificar worker de cola
check_worker() {
    local worker_name="$1"
    local status=$(supervisorctl status "$worker_name" 2>/dev/null | awk '{print $2}')
    
    echo -n "🔄 Worker $worker_name: "
    if [ "$status" = "RUNNING" ]; then
        echo "✅ RUNNING"
        return 0
    else
        echo "❌ $status"
        return 1
    fi
}

echo "1️⃣ Verificando directorios de credenciales:"
failed=0

check_directory "/var/www/html/storage/app/public/credentials/qr" "777" "www-data:www-data" || ((failed++))
check_directory "/var/www/html/storage/app/public/credentials/images" "777" "www-data:www-data" || ((failed++))
check_directory "/var/www/html/storage/app/public/credentials/pdf" "777" "www-data:www-data" || ((failed++))

echo ""
echo "2️⃣ Verificando directorios de templates:"
check_directory "/var/www/html/storage/app/public/templates/events" "777" "www-data:www-data" || ((failed++))

echo ""
echo "3️⃣ Verificando directorios de lotes:"
check_directory "/var/www/html/storage/app/public/print-batches" "777" "www-data:www-data" || ((failed++))

echo ""
echo "4️⃣ Verificando directorios legacy:"
check_directory "/var/www/html/storage/app/public/qr-codes" "777" "www-data:www-data" || ((failed++))
check_directory "/var/www/html/storage/app/public/pdfs" "777" "www-data:www-data" || ((failed++))

echo ""
echo "5️⃣ Verificando directorios base de Laravel:"
check_directory "/var/www/html/storage" "777" "www-data:www-data" || ((failed++))
check_directory "/var/www/html/bootstrap/cache" "777" "www-data:www-data" || ((failed++))

echo ""
echo "6️⃣ Verificando workers de cola:"
worker_failed=0
check_worker "laravel-queue" || ((worker_failed++))
check_worker "laravel-queue-credentials:laravel-queue-credentials_00" || ((worker_failed++))
check_worker "laravel-queue-credentials:laravel-queue-credentials_01" || ((worker_failed++))
check_worker "laravel-queue-print-batches:laravel-queue-print-batches_00" || ((worker_failed++))

echo ""
echo "7️⃣ Verificando archivos de log de workers:"
log_failed=0
for log_file in /var/log/supervisor/laravel-queue.log /var/log/supervisor/laravel-queue-credentials.log /var/log/supervisor/laravel-queue-print-batches.log; do
    if [ -f "$log_file" ]; then
        echo "✅ $log_file existe"
    else
        echo "❌ $log_file NO EXISTE"
        ((log_failed++))
    fi
done

echo ""
echo "📊 RESUMEN:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"

if [ $failed -eq 0 ]; then
    echo "✅ DIRECTORIOS: Todos los directorios están configurados correctamente"
else
    echo "❌ DIRECTORIOS: $failed directorios tienen problemas"
fi

if [ $worker_failed -eq 0 ]; then
    echo "✅ WORKERS: Todos los workers están corriendo"
else
    echo "❌ WORKERS: $worker_failed workers tienen problemas"
fi

if [ $log_failed -eq 0 ]; then
    echo "✅ LOGS: Todos los archivos de log existen"
else
    echo "❌ LOGS: $log_failed archivos de log faltan"
fi

total_failed=$((failed + worker_failed + log_failed))

if [ $total_failed -eq 0 ]; then
    echo ""
    echo "🎉 ¡TODO CONFIGURADO CORRECTAMENTE!"
    echo "✅ La regeneración de credenciales debería funcionar automáticamente"
    echo "✅ La generación de credenciales individuales debería funcionar automáticamente"
    echo "✅ La generación de lotes de impresión debería funcionar automáticamente"
    exit 0
else
    echo ""
    echo "⚠️  Se encontraron $total_failed problemas que necesitan atención"
    echo ""
    echo "🛠️  Para corregir problemas de permisos manualmente:"
    echo "   docker exec -it acredita_app chmod -R 777 /var/www/html/storage"
    echo "   docker exec -it acredita_app chown -R www-data:www-data /var/www/html/storage"
    echo ""
    echo "🛠️  Para reiniciar workers:"
    echo "   docker exec -it acredita_app supervisorctl restart all"
    exit 1
fi
