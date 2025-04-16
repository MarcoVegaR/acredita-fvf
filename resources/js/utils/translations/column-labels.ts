/**
 * Sistema centralizado de traducciones para etiquetas de columnas
 * 
 * Este archivo contiene todas las traducciones de columnas organizadas por módulo.
 * Permite mantener las traducciones en un solo lugar y acceder a ellas de manera
 * consistente en toda la aplicación.
 */
import * as React from "react";

// Tipos para las traducciones
export type ModuleTranslations = Record<string, string>;
export type TranslationsMap = Record<string, ModuleTranslations>;

// Almacén centralizado de traducciones por módulo
const columnTranslations: TranslationsMap = {
  // Módulo de usuarios
  users: {
    id: "ID",
    name: "Nombre",
    email: "Correo Electrónico",
    email_verified_at: "Verificación",
    created_at: "Fecha de Creación",
    updated_at: "Última Actualización",
    roles: "Roles",
    permissions: "Permisos",
  },
  
  // Módulo de roles
  roles: {
    id: "ID",
    name: "Nombre",
    slug: "Identificador",
    description: "Descripción",
    created_at: "Fecha de Creación",
    updated_at: "Última Actualización",
    permissions: "Permisos",
  },
  
  // Puedes añadir más módulos según sea necesario
};

/**
 * Obtiene todas las traducciones para un módulo específico
 * @param moduleName Nombre del módulo (ej: "users", "roles")
 * @returns Objeto con las traducciones del módulo o un objeto vacío si no existe
 */
export function getColumnLabels(moduleName: string): ModuleTranslations {
  if (!moduleName || !columnTranslations[moduleName]) {
    return {};
  }
  
  return columnTranslations[moduleName];
}

/**
 * Obtiene la traducción para una columna específica
 * @param moduleName Nombre del módulo (ej: "users", "roles")
 * @param columnId Identificador de la columna (ej: "name", "email")
 * @param defaultLabel Etiqueta por defecto si no se encuentra traducción
 * @returns La traducción de la columna o la etiqueta por defecto
 */
export function getColumnLabel(
  moduleName: string,
  columnId: string,
  defaultLabel?: string
): string {
  // Si no hay módulo o no existe, usar el valor por defecto o el ID
  if (!moduleName || !columnTranslations[moduleName]) {
    return defaultLabel || columnId;
  }
  
  // Buscar la traducción en el módulo
  const moduleTranslations = columnTranslations[moduleName];
  
  // Retornar la traducción o el valor por defecto
  return moduleTranslations[columnId] || defaultLabel || columnId;
}

/**
 * Formatea un valor para exportación
 * @param value Valor a formatear
 * @returns Valor formateado como string
 */
export function formatExportValue(value: unknown): string {
  // Si el valor es null o undefined, devolver cadena vacía
  if (value === null || value === undefined) {
    return "";
  }
  
  // Para booleanos, devolver "Sí" o "No"
  if (typeof value === "boolean") {
    return value ? "Sí" : "No";
  }
  
  // Para fechas, formatear a local
  if (value instanceof Date) {
    return value.toLocaleString();
  }
  
  // Para arrays y objetos, intentar serializar
  if (typeof value === "object") {
    try {
      // Si es un elemento React, devolver texto indicativo
      if (React.isValidElement(value)) {
        return "[Componente React]";
      }
      return JSON.stringify(value);
    } catch {
      return "[Objeto complejo]";
    }
  }
  
  // Para valores primitivos, convertir a string
  return String(value);
}
