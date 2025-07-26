# CCoders Setup - Aplicación Laravel 12 con React Starter Kit

Esta aplicación utiliza el Starter Kit oficial de React de Laravel 12, que incluye React y TypeScript en el frontend con Inertia 2 como puente, creando una experiencia SPA moderna y potente sin sacrificar la simpleza de Laravel.

## Características principales

- **Backend**: Laravel 12
- **Frontend**: React + TypeScript (starter kit oficial)
- **Navegación SPA**: Inertia 2
- **UI Components**: Shadcn UI (incluido en el starter kit)
- **Sistema de tablas**: TanStack Table
- **Estilos**: Tailwind CSS (incluido en el starter kit)
- **Iconos**: Lucide Icons
- **Formularios**: React Hook Form
- **Validación**: Zod
- **Autenticación**: Sistema integrado de Laravel
- **Traducciones**: Sistema centralizado

## Requisitos previos

- PHP 8.3 o superior (recomendado PHP 8.4)
- Composer 2
- Node.js 18 o superior (recomendado Node.js 20+)
- npm o yarn
- Git
- MySQL, PostgreSQL o SQLite

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/MarcoVegaR/ccoders-setup.git
cd ccoders-setup
```

### 2. Instalar PHP (si no está instalado)

#### En macOS:
```bash
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.4)"
source ~/.profile
```

#### En Ubuntu/Debian:
```bash
sudo apt update
sudo apt install php8.4 php8.4-cli php8.4-common php8.4-curl php8.4-mbstring php8.4-mysql php8.4-xml php8.4-zip
```

### 3. Instalar Composer (si no está instalado)

#### En macOS:
```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

#### En Ubuntu/Debian:
```bash
sudo apt update
sudo apt install composer
```

### 4. Instalar Node.js y npm (si no están instalados)

#### En macOS:
```bash
brew install node
```

#### En Ubuntu/Debian:
```bash
sudo apt update
sudo apt install nodejs npm
```

### 5. Configurar el entorno

```bash
# Copiar el archivo de entorno
cp .env.example .env

# Generar clave de aplicación
php artisan key:generate
```

### 6. Instalar dependencias

```bash
# Instalar dependencias PHP
composer install

# Instalar dependencias JavaScript
npm install
```

### 7. Configurar la base de datos

Editar el archivo `.env` para configurar la conexión a la base de datos:

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ccoders_setup
DB_USERNAME=root
DB_PASSWORD=
```

### 8. Ejecutar migraciones y seeders

```bash
php artisan migrate --seed
```

### 9. Iniciar el servidor de desarrollo

```bash
# Terminal 1: Servidor PHP
php artisan serve

# Terminal 2: Compilación de assets
npm run dev
```

La aplicación estará disponible en [http://localhost:8000](http://localhost:8000)

## Estructura del proyecto

- `/resources/js/pages`: Páginas React de la aplicación (patrón oficial de Laravel 12 + React)
- `/resources/js/components`: Componentes React reutilizables
  - `/resources/js/components/base-index`: Componentes para tablas de datos
  - `/resources/js/components/ui`: Componentes de UI de Shadcn (integrados con el starter kit)
- `/resources/js/utils`: Utilidades y funciones auxiliares
  - `/resources/js/utils/translations`: Sistema de traducción centralizado
- `/resources/js/layouts`: Layouts para las páginas (AuthenticatedLayout, GuestLayout)
- `/resources/js/lib`: Bibliotecas y funciones auxiliares del starter kit

## Componentes principales

### DataTable

El proyecto incluye un sistema avanzado de tablas de datos construido con TanStack Table y adaptado al ecosistema de Laravel 12 + React + Shadcn UI, con las siguientes características:

- Búsqueda global con placeholder configurable
- Filtros por columna
- Ordenamiento con soporte server-side
- Paginación server-side o client-side
- Exportación (Excel, CSV, Print, Copy)
- Acciones por fila (ver, editar, eliminar) con diálogos de confirmación
- Traducciones centralizadas por módulo
- Tarjetas de estadísticas configurables por módulo
- Soporte completo para TypeScript

Para más información sobre cómo usar el componente DataTable, consulta la [documentación detallada](/resources/js/components/base-index/README.md).

## Contribución

1. Haz un fork del repositorio
2. Crea una rama para tu función (`git checkout -b feature/nueva-funcion`)
3. Haz commit de tus cambios (`git commit -am 'Añadir nueva función'`)
4. Haz push a la rama (`git push origin feature/nueva-funcion`)
5. Crea un Pull Request

## Créditos

- [Marco Vega](https://github.com/MarcoVegaR)

# 🏆 Sistema de Acreditación FVF

Sistema de acreditación para la Federación Venezolana de Fútbol desarrollado con Laravel 12 + React + Inertia.js + PostgreSQL.

## 🚀 Deployment en Producción (EC2 Amazon)

### Prerrequisitos

- Instancia EC2 Ubuntu 20.04+ con al menos 2GB RAM
- Docker y Docker Compose instalados
- Acceso SSH a la instancia
- Puertos 8094 y 5435 abiertos en el Security Group

### 📋 Paso a Paso - Instalación Completa

#### 1. Conectar a la instancia EC2

```bash
# Conectar vía SSH
ssh -i "your-key.pem" ubuntu@your-ec2-public-ip
```

#### 2. Actualizar el sistema

```bash
sudo apt update && sudo apt upgrade -y
```

#### 3. Instalar Docker

```bash
# Instalar Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Agregar usuario al grupo docker
sudo usermod -aG docker $USER

# Instalar Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/download/v2.24.0/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verificar instalación
docker --version
docker compose version

# Reiniciar sesión o usar:
newgrp docker
```

#### 4. Clonar el repositorio

```bash
# Clonar desde GitHub
git clone https://github.com/MarcoVegaR/acredita-fvf.git
cd acredita-fvf
```

#### 5. Configurar variables de entorno

```bash
# Copiar configuración de producción
cp .env.prod .env

# Editar variables según tu servidor (opcional)
nano .env
```

**Variables importantes a verificar/modificar:**

```env
# Cambiar por tu dominio real
APP_URL=https://acredita.tu-dominio.com

# Configuración de base de datos (mantener estos valores)
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=acredita-fvf
DB_USERNAME=postgres
DB_PASSWORD=postgres

# Configuración de correo para producción
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=tu-email@gmail.com
MAIL_PASSWORD=tu-app-password
MAIL_FROM_ADDRESS=noreply@tu-dominio.com
```

#### 6. Construir e iniciar contenedores

```bash
# Iniciar servicios con Docker Compose
docker compose up -d --build

# Verificar que los contenedores estén corriendo
docker ps
```

#### 7. Configuración dentro del contenedor

```bash
# Entrar al contenedor de la aplicación
docker exec -it -u root acredita_app bash

# Dentro del contenedor, ejecutar:
composer install --optimize-autoloader --no-dev
npm install
npm run build
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Configurar permisos
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Salir del contenedor
exit
```

#### 8. Verificar instalación

```bash
# Verificar logs
docker logs -f acredita_app

# Verificar health check
curl http://localhost:8094/health
```

### 🌐 URLs de acceso

- **Aplicación:** `http://your-ec2-public-ip:8094`
- **pgAdmin:** `http://your-ec2-public-ip:8095`
  - Usuario: `admin@acredita.com`
  - Contraseña: `admin123`

### 📱 Configuración de HTTPS (Producción)

Para producción con dominio propio:

1. **Configurar dominio:**
   - Apuntar tu dominio a la IP pública de EC2
   - Actualizar `APP_URL` en `.env`

2. **Configurar SSL con Certbot:**

```bash
# Instalar Certbot
sudo apt install certbot python3-certbot-nginx -y

# Obtener certificado SSL
sudo certbot --nginx -d tu-dominio.com

# Renovación automática
sudo crontab -e
# Agregar: 0 12 * * * /usr/bin/certbot renew --quiet
```

3. **Actualizar configuración de Nginx:**
   - Descomentar configuración HTTPS en `docker/nginx/default.conf`
   - Rebuildar contenedor: `docker compose up -d --build`

### 🔧 Comandos útiles de mantenimiento

```bash
# Ver logs de la aplicación
docker logs -f acredita_app

# Entrar al contenedor
docker exec -it -u root acredita_app bash

# Reiniciar servicios
docker compose restart

# Parar servicios
docker compose down

# Actualizar desde Git
git pull origin main
docker compose up -d --build

# Backup de base de datos
docker exec acredita_db pg_dump -U postgres acredita-fvf > backup.sql

# Restaurar backup
docker exec -i acredita_db psql -U postgres acredita-fvf < backup.sql
```

### 📊 Monitoreo

```bash
# Ver estado de contenedores
docker ps

# Ver uso de recursos
docker stats

# Ver logs del sistema
journalctl -u docker.service
```

### 🛡️ Seguridad

1. **Firewall (UFW):**

```bash
sudo ufw enable
sudo ufw allow ssh
sudo ufw allow 8094/tcp
sudo ufw allow 443/tcp
sudo ufw allow 80/tcp
```

2. **Actualizaciones automáticas:**

```bash
sudo apt install unattended-upgrades -y
sudo dpkg-reconfigure -plow unattended-upgrades
```

### 🚨 Troubleshooting

**Problema: Error de permisos entre www-data y appuser**

Si experimentas errores 500 en el servidor web o problemas con los workers de cola que fallan silenciosamente, es probable que sea un problema de permisos. El servidor web (Nginx/PHP-FPM) corre como `www-data` mientras que los workers de cola corren como `appuser`, y ambos necesitan acceso de escritura a ciertos directorios.

```bash
# Solución: aplicar permisos correctos después de cada despliegue
docker exec -it acredita_app bash -c "
# Establecer permisos de grupo adecuados
chown -R appuser:www-data /var/www/html/storage
chown -R appuser:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache
chmod g+s /var/www/html/storage
"
```

> ⚠️ **IMPORTANTE**: Ejecuta este comando CADA VEZ que hagas un `docker compose up -d --build` para evitar errores 500 o problemas con los jobs.

**Explicación técnica**:
- `appuser`: Usuario que ejecuta los workers de cola (necesita escribir logs y generar archivos)
- `www-data`: Usuario del servidor web (necesita escribir en vistas compiladas y caché)
- La solución establece `appuser` como propietario pero da permisos de escritura al grupo `www-data`
- El flag `chmod g+s` asegura que nuevos archivos hereden automáticamente el grupo `www-data`

**Problema: Contenedor no inicia**
```bash
docker logs acredita_app
docker logs acredita_db
```

**Problema: Error de permisos**
```bash
docker exec -it -u root acredita_app chown -R www-data:www-data /var/www/html/storage
```

**Problema: Base de datos no conecta**
```bash
docker exec -it acredita_db psql -U postgres -d acredita-fvf
```

**Problema: Assets no cargan**
```bash
docker exec -it acredita_app npm run build
docker exec -it acredita_app php artisan storage:link
```

### 📈 Performance

- **RAM recomendada:** 4GB para producción
- **CPU:** 2 vCPUs mínimo
- **Storage:** 20GB+ SSD
- **Monitoreo:** Configurar CloudWatch para métricas

### 🏗️ Arquitectura

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Nginx + PHP   │───▶│   PostgreSQL    │    │     pgAdmin     │
│   (Port 8094)   │    │   (Port 5435)   │    │   (Port 8095)   │
│   acredita_app  │    │   acredita_db   │    │  acredita_pgadmin│
└─────────────────┘    └─────────────────┘    └─────────────────┘
```

### 👥 Roles del Sistema

- **Admin:** Acceso completo al sistema
- **Area Manager:** Gestión de proveedores, empleados y solicitudes de su área
- **Provider:** Gestión de empleados y solicitudes de su propio proveedor

### 📞 Soporte

Para reportar problemas o solicitar ayuda:

- **GitHub Issues:** [https://github.com/MarcoVegaR/acredita-fvf/issues](https://github.com/MarcoVegaR/acredita-fvf/issues)
- **Email:** marco@caracoders.com.ve

---

**Desarrollado por:** [Caracoders](https://caracoders.com.ve)  
**Versión:** 1.0.0  
**Laravel:** 12.x  
**React:** 18.x  
**PostgreSQL:** 16.x
