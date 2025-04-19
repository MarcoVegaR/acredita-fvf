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

// Traducciones para textos comunes en páginas de detalle (ShowPage)
export const showPageLabels = {
  backToList: "Volver al listado",
  noData: "No disponible",
  booleanTrue: "Sí",
  booleanFalse: "No",
  sectionTitles: {
    basicInfo: "Datos básicos",
    metadata: "Metadatos",
    relationships: "Relaciones",
    permissions: "Permisos",
    history: "Historial",
  }
};

// Almacén centralizado de traducciones por módulo
const columnTranslations: TranslationsMap = {
  // Módulo de documentos
  documents: {
    id: "ID",
    name: "Nombre del documento",
    type: "Tipo",
    original_filename: "Nombre original",
    file_size: "Tamaño",
    created_at: "Fecha de subida",
    updated_at: "Última actualización",
    uploaded_by: "Subido por",
    is_validated: "Validado",
    section_title: "Documentos",
    no_documents: "No hay documentos",
    upload_document: "Subir documento",
    delete_document: "Eliminar documento",
    download_document: "Descargar documento",
    view_document: "Ver documento",
    document_type: "Tipo de documento",
    select_type: "Seleccione un tipo",
    upload_instructions: "Seleccione un archivo para subir",
    filename: "Nombre del archivo",
  },
  
  // Módulo de usuarios
  users: {
    id: "ID",
    name: "Nombre",
    email: "Correo Electrónico",
    email_verified_at: "Verificación",
    active: "Estado",
    created_at: "Fecha de Creación",
    updated_at: "Última Actualización",
    roles: "Roles",
    role_names: "Roles",
    permissions: "Permisos",
    backToList: "Volver al listado de usuarios",
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
    backToList: "Volver al listado de roles",
  },

  // Módulos adicionales que puedes añadir según sea necesario
  
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

  // Si es boolean, convertir a "Sí" o "No"
  if (typeof value === "boolean") {
    return value ? "Sí" : "No";
  }

  // Detectar fechas en formato ISO string y formatearlas
  if (typeof value === "string" && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/.test(value)) {
    try {
      const date = new Date(value);
      // Verificar que es una fecha válida
      if (!isNaN(date.getTime())) {
        // Formato: DD/MM/YYYY HH:MM
        return date.toLocaleDateString('es-ES', {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
          hour: '2-digit',
          minute: '2-digit'
        });
      }
    } catch {
      // Si hay error al parsear la fecha, devolverla como estaba
      return value;
    }
  }

  // Si es una fecha, formatearla
  if (value instanceof Date) {
    return value.toLocaleDateString('es-ES', {
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit'
    });
  }
  
  // Para arrays y objetos, usar lógica especial
  if (typeof value === "object") {
    try {
      // Si es un elemento React, devolver texto indicativo
      if (React.isValidElement(value)) {
        return "[Componente React]";
      }
      
      // Si es un array, intentar formatear cada elemento
      if (Array.isArray(value)) {
        // Caso especial para arreglos de permisos (tienen nameshow o name)
        if (value.length > 0 && (
          typeof value[0] === 'object' && value[0] !== null && 
          ('nameshow' in value[0] || 'name' in value[0])
        )) {
          return value.map(item => {
            if (typeof item === 'object' && item !== null) {
              return 'nameshow' in item ? String(item.nameshow) : 'name' in item ? String(item.name) : '';
            }
            return '';
          }).filter(Boolean).join(', ') || 'Sin elementos';
        }
        
        // Para otros arrays, intentar formatear cada elemento
        return value.map(formatExportValue).join(', ') || 'Sin elementos';
      }
      
      // Si el objeto tiene una propiedad nameshow o name, usarla directamente
      if (typeof value === 'object' && value !== null) {
        if ('nameshow' in value) return String(value.nameshow);
        if ('name' in value) return String(value.name);
      }
      
      // Si nada de lo anterior funciona, serializarlo como JSON
      return JSON.stringify(value);
    } catch {
      return "[Objeto complejo]";
    }
  }
  
  // Para valores primitivos, convertir a string
  return String(value);
}
