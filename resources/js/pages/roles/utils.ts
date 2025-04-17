import { Role } from "./schema";

// Utility functions for handling roles

/**
 * Groups permissions by their module (first part of the permission name)
 * @param permissions List of permissions to group
 * @returns Object with permissions grouped by module
 */
export function groupPermissionsByModule(permissions: string[]): Record<string, string[]> {
  return permissions.reduce((acc, permission) => {
    const [module] = permission.split('.');
    if (!acc[module]) {
      acc[module] = [];
    }
    acc[module].push(permission);
    return acc;
  }, {} as Record<string, string[]>);
}

/**
 * Checks if a role is protected (built-in roles that cannot be modified)
 * @param role Role to check
 * @returns Boolean indicating if the role is protected
 */
export function isProtectedRole(role: Role | string): boolean {
  const roleName = typeof role === 'string' ? role : role.name;
  return ['admin', 'super-admin'].includes(roleName);
}

/**
 * Gets a human-readable description for a role
 * @param roleName Name of the role
 * @returns Human-readable description
 */
export function getRoleDescription(roleName: string): string {
  const descriptions: Record<string, string> = {
    admin: 'Acceso completo a todas las funciones del sistema.',
    editor: 'Puede editar contenido pero no administrar usuarios o roles.',
    viewer: 'Solo puede ver información sin realizar cambios.',
    manager: 'Administra equipos y proyectos, pero no configuraciones del sistema.',
  };
  
  return descriptions[roleName] || 'Rol personalizado con permisos específicos.';
}

/**
 * Gets translated text for permission actions
 * @param action The permission action (create, edit, etc.)
 * @returns Translated action text
 */
export function getPermissionActionText(action: string): string {
  const actionMap: Record<string, string> = {
    create: 'Crear',
    edit: 'Editar',
    delete: 'Eliminar',
    view: 'Ver',
    show: 'Ver detalles',
    list: 'Listar',
    import: 'Importar',
    export: 'Exportar',
    manage: 'Administrar'
  };
  
  return actionMap[action] || action;
}

/**
 * Gets a formatted description for a permission
 * @param permission Permission name (format: "module.action")
 * @returns Formatted description
 */
export function formatPermissionDescription(permission: string): string {
  const parts = permission.split('.');
  if (parts.length !== 2) return permission;
  
  const [module, action] = parts;
  const actionText = getPermissionActionText(action);
  
  return `${actionText} ${module}`;
}
