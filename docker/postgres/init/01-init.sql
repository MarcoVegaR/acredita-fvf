-- Script de inicialización para PostgreSQL
-- Se ejecuta automáticamente cuando se crea el contenedor por primera vez

-- Crear extensiones útiles
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- Configurar la zona horaria
SET timezone = 'America/Caracas';

-- Crear índices adicionales para mejorar performance (se pueden agregar después de las migraciones)
-- Estos se ejecutarán después de que Laravel cree las tablas

-- Mensaje de confirmación
SELECT 'Base de datos inicializada correctamente para el sistema de acreditación FVF' as mensaje;
