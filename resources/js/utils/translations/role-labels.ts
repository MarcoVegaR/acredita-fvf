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
  // Sistema y administración
  users: "Usuarios",
  roles: "Roles",
  permissions: "Permisos",
  areas: "Áreas",
  
  // Gestión de proveedores
  provider: "Proveedor",
  providers: "Proveedores",
  
  // Gestión de colaboradores
  employee: "Colaborador",
  employees: "Colaboradores",
  
  // Eventos y zonas
  events: "Eventos",
  zones: "Zonas",
  
  // Plantillas de credenciales
  templates: "Plantillas",
  
  // Solicitudes de acreditación
  accreditation_request: "Solicitud de acreditación",
  accreditation_requests: "Solicitudes de acreditación",
  
  // Credenciales y QR
  credential: "Credencial",
  credentials: "Credenciales",
  
  // Gestión de archivos
  documents: "Documentos",
  images: "Imágenes",
  
  // Otros módulos generales
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
  activities: "Actividades",
  customers: "Clientes",
  products: "Productos",
  invoices: "Facturas",
  categories: "Categorías",
  tags: "Etiquetas",
  comments: "Comentarios",
  
  // Additional modules to prevent undefined values
  '': "General",
  undefined: "General"
};

// Traducción de acciones para permisos
export const actionTranslations: Record<string, string> = {
  // Acciones básicas CRUD
  index: "Listar",
  show: "Ver detalles",
  create: "Crear",
  edit: "Editar",
  update: "Actualizar",
  delete: "Eliminar",
  
  // Acciones de gestión
  manage: "Administrar",
  manage_own_area: "Administrar (propia área)",
  manage_own_provider: "Administrar (propio proveedor)",
  
  // Acciones específicas para solicitudes
  submit: "Enviar",
  review: "Revisar",
  return: "Devolver",
  
  // Acciones específicas para credenciales
  view: "Ver",
  download: "Descargar",
  preview: "Previsualizar",
  regenerate: "Regenerar",
  
  // Otras acciones comunes
  export: "Exportar",
  import: "Importar",
  approve: "Aprobar",
  reject: "Rechazar",
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
  
  // Casos especiales para formateo más natural en español
  
  // 1. Permisos de gestión por área/proveedor
  if (action === 'manage_own_area') {
    if (module === 'provider') return 'Administrar proveedores de su área';
    if (module === 'employee') return 'Administrar colaboradores de su área';
    if (module === 'accreditation_request') return 'Administrar solicitudes de su área';
    return `Administrar ${moduleTrans} de su área`;
  }
  
  if (action === 'manage_own_provider') {
    if (module === 'employee') return 'Administrar colaboradores de su proveedor';
    if (module === 'accreditation_request') return 'Administrar solicitudes de su proveedor';
    return `Administrar ${moduleTrans} de su proveedor`;
  }
  
  // 2. Permisos de tipo CRUD
  if (action === 'index') {
    return `Listar ${moduleTrans}`;
  }
  
  if (action === 'show') {
    return `Ver detalles de ${moduleTrans}`;
  }
  
  if (action === 'create') {
    return `Crear ${moduleTrans}`;
  }
  
  if (action === 'edit' || action === 'update') {
    return `Editar ${moduleTrans}`;
  }
  
  if (action === 'delete') {
    return `Eliminar ${moduleTrans}`;
  }
  
  // 3. Permisos de credenciales
  if (module === 'credential' || module === 'credentials') {
    if (action === 'view') return 'Ver credenciales';
    if (action === 'download') return 'Descargar credenciales';
    if (action === 'preview') return 'Previsualizar credenciales';
  }
  
  // 4. Permisos de plantillas
  if (module === 'templates' && action === 'regenerate') {
    return 'Regenerar plantillas';
  }
  
  // 5. Acciones especiales para solicitudes
  if (module === 'accreditation_request') {
    if (action === 'submit') return 'Enviar solicitud de acreditación';
    if (action === 'review') return 'Revisar solicitud de acreditación';
    if (action === 'return') return 'Devolver solicitud de acreditación';
  }
  
  // Para el resto de los casos, seguimos el formato estándar
  return `${actionTrans} ${moduleTrans}`;
}
