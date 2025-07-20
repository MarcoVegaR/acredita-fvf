/**
 * Permission Categories
 * Group related modules into logical categories for better organization in the UI
 */

// Define categories and their modules
export const permissionCategories = [
  {
    id: 'system',
    label: 'Sistema y Administración',
    icon: 'Shield',
    description: 'Permisos para administración del sistema y configuración general',
    modules: ['users', 'roles', 'areas']
  },
  {
    id: 'providers',
    label: 'Gestión de Proveedores',
    icon: 'Building2',
    description: 'Permisos para gestionar proveedores internos y externos',
    modules: ['provider']
  },
  {
    id: 'employees',
    label: 'Gestión de Empleados',
    icon: 'Users',
    description: 'Permisos para gestionar empleados de proveedores',
    modules: ['employee']
  },
  {
    id: 'events',
    label: 'Eventos y Zonas',
    icon: 'Calendar',
    description: 'Permisos para gestionar eventos deportivos y sus zonas',
    modules: ['events', 'zones']
  },
  {
    id: 'templates',
    label: 'Plantillas de Credenciales',
    icon: 'FileImage',
    description: 'Permisos para gestionar plantillas y diseños de credenciales',
    modules: ['templates']
  },
  {
    id: 'accreditation',
    label: 'Solicitudes de Acreditación',
    icon: 'FileCheck',
    description: 'Permisos para gestionar el flujo completo de acreditación',
    modules: ['accreditation_request']
  },
  {
    id: 'credentials',
    label: 'Credenciales y QR',
    icon: 'CreditCard',
    description: 'Permisos para visualizar, generar y gestionar credenciales',
    modules: ['credential', 'credentials']
  },
  {
    id: 'files',
    label: 'Gestión de Archivos',
    icon: 'FileText',
    description: 'Permisos para manejar documentos e imágenes del sistema',
    modules: ['documents', 'images']
  },
  {
    id: 'other',
    label: 'Otros Permisos',
    icon: 'MoreHorizontal',
    description: 'Permisos que no encajan en otras categorías',
    modules: []
  }
];

/**
 * Get the category for a module
 * @param module The module name to find category for
 * @returns The category object or undefined
 */
// Definición de tipo para la categoría de permisos
export type PermissionCategory = {
  id: string;
  label: string;
  icon: string | React.ReactNode;
  modules: string[];
  description?: string;
};

export function getCategoryForModule(module: string): PermissionCategory | undefined {
  if (!module) {
    return permissionCategories.find(c => c.id === 'other');
  }
  
  // Normalize module name - remove any potential prefix/suffix
  // This handles cases where module might be extracted from permission name (e.g., 'users.view')
  const normalizedModule = module.split('.')[0].toLowerCase();
  
  // First check for direct module match
  for (const category of permissionCategories) {
    if (category.modules.includes(normalizedModule)) {
      return category;
    }
  }
  
  // If no match, return the "Other" category
  return permissionCategories.find(c => c.id === 'other');
}

/**
 * Group permissions by category and module
 * @param permissions Array of permissions to organize
 * @returns Object with permissions organized by category and module
 */
// Tipo para permisos
export interface Permission {
  name: string;
  module?: string;
  description?: string;
  nameshow?: string;
}

// Tipo para grupo de módulos
export interface ModuleGroup {
  [module: string]: Permission[];
}

// Tipo para categoría con grupos de módulos
export interface CategoryWithModuleGroups extends PermissionCategory {
  moduleGroups: ModuleGroup;
}

export function groupPermissionsByCategory(permissions: Permission[]): CategoryWithModuleGroups[] {
  const permissionsByCategory: Record<string, CategoryWithModuleGroups> = {};
  
  // Initialize categories
  permissionCategories.forEach(category => {
    permissionsByCategory[category.id] = {
      ...category,
      moduleGroups: {} as ModuleGroup
    };
  });
  
  // Group permissions by module first
  const permissionsByModule: Record<string, Permission[]> = {};
  permissions.forEach(permission => {
    // Extraer el módulo desde el nombre del permiso (primera parte antes del punto)
    // Por ejemplo: 'users.view' -> módulo = 'users'
    const moduleParts = permission.name.split('.');
    const module = moduleParts.length > 1 ? moduleParts[0] : (permission.module || 'general');
    
    if (!permissionsByModule[module]) {
      permissionsByModule[module] = [];
    }
    permissionsByModule[module].push(permission);
  });
  
  // Assign modules to categories
  Object.entries(permissionsByModule).forEach(([module, modulePermissions]) => {
    const category = getCategoryForModule(module);
    // Verificar que la categoría existe antes de acceder a sus propiedades
    if (category && category.id) {
      permissionsByCategory[category.id].moduleGroups[module] = modulePermissions;
    } else {
      // Si no se encuentra una categoría, asignar a 'other'
      if (permissionsByCategory['other']) {
        permissionsByCategory['other'].moduleGroups[module] = modulePermissions;
      }
    }
  });
  
  // Filter out empty categories
  return Object.values(permissionsByCategory).filter(
    category => Object.keys(category.moduleGroups).length > 0
  );
}
