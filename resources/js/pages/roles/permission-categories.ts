/**
 * Permission Categories
 * Group related modules into logical categories for better organization in the UI
 */

// Define categories and their modules
export const permissionCategories = [
  {
    id: 'core',
    label: 'Sistema y Administración',
    icon: 'Shield', // Indicates icon to use
    modules: ['system', 'dashboard', 'settings', 'audit']
  },
  {
    id: 'users',
    label: 'Usuarios y Seguridad',
    icon: 'Users',
    modules: ['users', 'roles', 'permissions', 'profile']
  },
  {
    id: 'content',
    label: 'Contenido y Archivos',
    icon: 'FileText',
    modules: ['files', 'documents', 'categories', 'tags', 'comments']
  },
  {
    id: 'business',
    label: 'Gestión de Negocios',
    icon: 'Briefcase',
    modules: ['customers', 'products', 'invoices', 'reports']
  },
  {
    id: 'communications',
    label: 'Comunicaciones',
    icon: 'MessageCircle',
    modules: ['notifications', 'activities']
  },
  {
    id: 'other',
    label: 'Otros Módulos',
    icon: 'MoreHorizontal',
    modules: [] // Will capture modules not in other categories
  }
];

/**
 * Get the category for a module
 * @param module The module name to find category for
 * @returns The category object or undefined
 */
export function getCategoryForModule(module: string): any {
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
export function groupPermissionsByCategory(permissions: any[]) {
  const permissionsByCategory: Record<string, any> = {};
  
  // Initialize categories
  permissionCategories.forEach(category => {
    permissionsByCategory[category.id] = {
      ...category,
      moduleGroups: {} as Record<string, any[]>
    };
  });
  
  // Group permissions by module first
  const permissionsByModule: Record<string, any[]> = {};
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
    permissionsByCategory[category.id].moduleGroups[module] = modulePermissions;
  });
  
  // Filter out empty categories
  return Object.values(permissionsByCategory).filter(
    category => Object.keys(category.moduleGroups).length > 0
  );
}
