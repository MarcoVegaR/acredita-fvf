/**
 * Translations for role-related columns and fields
 * Following the centralized translation approach
 */

export type RoleField = 
  | 'id' 
  | 'name' 
  | 'guard_name' 
  | 'permissions' 
  | 'permissions_count' 
  | 'created_at' 
  | 'updated_at';

// Labels for role fields
export const roleLabels: Record<RoleField, string> = {
  id: 'ID',
  name: 'Nombre del rol',
  guard_name: 'Guard',
  permissions: 'Permisos',
  permissions_count: 'Número de permisos',
  created_at: 'Fecha de creación',
  updated_at: 'Fecha de actualización'
};

// Descriptions for role fields
export const roleDescriptions: Partial<Record<RoleField, string>> = {
  name: 'Nombre único que identifica este rol en el sistema',
  guard_name: 'Guard de autenticación asociado al rol (generalmente "web")',
  permissions: 'Permisos asignados a este rol que definen qué acciones pueden realizar los usuarios'
};

// Function to get a field label with fallback
export function getRoleLabel(field: string, defaultLabel?: string): string {
  return roleLabels[field as RoleField] || defaultLabel || field;
}

// Function to get field description
export function getRoleDescription(field: string): string | undefined {
  return roleDescriptions[field as RoleField];
}

// Module names translation for permissions
export const moduleTranslations: Record<string, string> = {
  users: "Usuarios",
  roles: "Roles",
  permissions: "Permisos",
  dashboard: "Dashboard",
  reports: "Informes",
  settings: "Configuración",
  profile: "Perfil",
  audit: "Auditoría",
  notifications: "Notificaciones",
  files: "Archivos",
  backup: "Respaldo",
  teams: "Equipos",
  projects: "Proyectos",
  tasks: "Tareas",
  documents: 'Documentos',
  activities: 'Actividades',
  customers: 'Clientes',
  products: 'Productos',
  invoices: 'Facturas',
  categories: 'Categorías',
  tags: 'Etiquetas',
  comments: 'Comentarios',
  // Additional modules to prevent undefined values
  '': 'General',
  undefined: 'General'
};

// Traducción de acciones para permisos
export const actionTranslations: Record<string, string> = {
  index: "Listar",
  show: "Ver detalles",
  create: "Crear",
  edit: "Editar",
  update: "Actualizar",
  delete: "Eliminar",
  manage: "Administrar",
  export: "Exportar",
  import: "Importar",
};

// Get module name with fallback
export function getModuleLabel(module: string): string {
  if (!module || module === 'undefined') {
    return 'General';
  }
  return moduleTranslations[module] || module;
}

/**
 * Obtiene la descripción formateada para un permiso
 * @param permission Nombre del permiso (formato: "modulo.accion")
 * @returns Descripción formateada del permiso
 */
export function formatPermissionName(permission: string): string {
  const parts = permission.split('.');
  if (parts.length !== 2) return permission;
  
  const [module, action] = parts;
  const moduleTrans = moduleTranslations[module] || module;
  const actionTrans = actionTranslations[action] || action;
  
  return `${actionTrans} ${moduleTrans}`;
}
