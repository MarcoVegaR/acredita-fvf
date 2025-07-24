# 🚀 Configuración de Workers de Cola para Producción

## 📋 Problema Solucionado

Anteriormente, los jobs de colas específicas (`credentials`, `print_batches`) requerían ejecución manual de workers:
```bash
# Manual - NO recomendado en producción
php artisan queue:work --queue=credentials
php artisan queue:work --queue=print_batches
```

## ✅ Solución Implementada

### **1. Workers Automáticos via Supervisor**

El Dockerfile ahora incluye **3 workers automáticos**:

| **Worker** | **Cola** | **Procesos** | **Función** |
|------------|----------|--------------|-------------|
| `laravel-queue` | `default` | 1 | Regeneración masiva de credenciales |
| `laravel-queue-credentials` | `credentials` | 2 | Generación de credenciales individuales |
| `laravel-queue-print-batches` | `print_batches` | 1 | Generación de PDFs de lotes |

### **2. Configuración de Supervisor** (`docker/supervisor/supervisord.conf`)

```ini
[program:laravel-queue]
command=php /var/www/html/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=appuser
numprocs=1

[program:laravel-queue-credentials]
command=php /var/www/html/artisan queue:work --queue=credentials --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=appuser
numprocs=2

[program:laravel-queue-print-batches]
command=php /var/www/html/artisan queue:work --queue=print_batches --sleep=3 --tries=1 --timeout=300 --max-time=3600
autostart=true
autorestart=true
user=appuser
numprocs=1
```

### **3. Logs de Workers**

Los logs de cada worker se almacenan por separado:
- `/var/log/supervisor/laravel-queue.log`
- `/var/log/supervisor/laravel-queue-credentials.log`
- `/var/log/supervisor/laravel-queue-print-batches.log`

## 📦 Despliegue

### **Opción 1: Docker Compose (Recomendado)**
```bash
docker-compose up -d --build
```

### **Opción 2: Docker Build Manual**
```bash
docker build -t acredita-fvf .
docker run -d --name acredita-app -p 8094:80 acredita-fvf
```

## 🔍 Verificación

### **1. Verificar que los workers estén corriendo:**
```bash
docker exec -it acredita_app supervisorctl status
```

**Salida esperada:**
```
laravel-queue                    RUNNING   pid 123, uptime 0:01:23
laravel-queue-credentials:00     RUNNING   pid 124, uptime 0:01:23
laravel-queue-credentials:01     RUNNING   pid 125, uptime 0:01:23
laravel-queue-print-batches:00   RUNNING   pid 126, uptime 0:01:23
```

### **2. Verificar logs de workers:**
```bash
# Logs generales
docker exec -it acredita_app tail -f /var/log/supervisor/laravel-queue.log

# Logs de credenciales
docker exec -it acredita_app tail -f /var/log/supervisor/laravel-queue-credentials.log

# Logs de lotes de impresión
docker exec -it acredita_app tail -f /var/log/supervisor/laravel-queue-print-batches.log
```

### **3. Monitorear cola en tiempo real:**
```bash
docker exec -it acredita_app php artisan queue:monitor
```

### **4. Validar configuración completa (EC2):**
```bash
# Ejecutar script de validación automática
docker exec -it acredita_app /var/www/html/docker/validate-permissions.sh
```

## 🎯 Funcionamiento Automático

| **Acción del Usuario** | **Cola Usada** | **Worker Responsable** |
|-----------------------|-----------------|----------------------|
| **Crear credencial individual** | `credentials` | `laravel-queue-credentials` |
| **Generar lote de impresión** | `print_batches` | `laravel-queue-print-batches` |
| **Regenerar credenciales masivamente** | `default` | `laravel-queue` |

## 🛠️ Troubleshooting

### **🚨 PROBLEMA EC2: Regeneración no funciona (Resuelto)**

**Síntomas:**
- Regeneración de credenciales falla silenciosamente
- Errores de permisos en logs
- Necesidad de ejecutar comandos manuales post-despliegue

**Causa:**
Permisos insuficientes en directorios de storage en EC2.

**Solución (Automática):**
El `entrypoint.sh` ahora configura automáticamente:
- **Permisos 777** para todos los directorios
- **Owner www-data:www-data** para compatibilidad con Nginx
- **Todos los subdirectorios** necesarios

**Validación:**
```bash
# Validar que todo esté configurado correctamente
docker exec -it acredita_app /var/www/html/docker/validate-permissions.sh
```

**Corrección manual (si es necesario):**
```bash
# Solo si la validación falla
docker exec -it acredita_app chmod -R 777 /var/www/html/storage
docker exec -it acredita_app chown -R www-data:www-data /var/www/html/storage
```

---

### **Worker no procesa jobs:**
```bash
# Reiniciar workers específicos
docker exec -it acredita_app supervisorctl restart laravel-queue-credentials:*
docker exec -it acredita_app supervisorctl restart laravel-queue-print-batches:*
```

### **Verificar estado de cola:**
```bash
docker exec -it acredita_app php artisan queue:work --queue=credentials --once --verbose
```

### **Limpiar jobs fallidos:**
```bash
docker exec -it acredita_app php artisan queue:flush
```

## 📊 Configuración Optimizada

- **`numprocs=2`** para `credentials`: Maneja múltiples credenciales simultáneamente
- **`numprocs=1`** para `print_batches`: Evita conflictos en generación de PDFs
- **`timeout=300`** para lotes: Permite tiempo suficiente para PDFs grandes
- **`tries=1`** para lotes: Evita reintentos en jobs de larga duración
- **`max-time=3600`**: Reinicia workers cada hora para prevenir memory leaks

---

✅ **Con esta configuración, NUNCA más será necesario ejecutar workers manualmente en producción.**
