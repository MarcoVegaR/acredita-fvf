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
