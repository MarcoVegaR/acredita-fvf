import { usePage } from "@inertiajs/react";

// Extendemos para incluir la firma de índice requerida por Inertia
interface PageProps {
  auth: {
    user: {
      permissions: string[];
      roles: string[];
    };
  };
  [key: string]: unknown; // Esta es la firma de índice necesaria para satisfacer la restricción de Inertia
}

/**
 * Hook para gestionar los permisos del usuario actual
 * 
 * Permite verificar si el usuario tiene permisos específicos,
 * filtrar acciones por permisos y verificar roles asignados
 */
export function usePermissions() {
  const { auth } = usePage<PageProps>().props;
  
  /**
   * Verifica si el usuario tiene un permiso específico
   * 
   * @param permission Nombre del permiso a verificar
   * @returns Verdadero si el usuario tiene el permiso
   */
  const can = (permission: string): boolean => {
    if (!permission) return true; // Si no se especifica permiso, permitir
    if (!auth?.user?.permissions) return false;
    
    return auth.user.permissions.includes(permission);
  };
  
  /**
   * Verifica si el usuario tiene un rol específico
   * 
   * @param role Nombre del rol a verificar
   * @returns Verdadero si el usuario tiene el rol
   */
  const hasRole = (role: string): boolean => {
    if (!auth?.user?.roles) return false;
    
    return auth.user.roles.includes(role);
  };
  
  /**
   * Verifica si el usuario tiene al menos uno de los permisos especificados
   * 
   * @param permissions Lista de permisos a verificar
   * @returns Verdadero si el usuario tiene al menos uno de los permisos
   */
  const canAny = (permissions: string[]): boolean => {
    if (!permissions.length) return true;
    if (!auth?.user?.permissions) return false;
    
    return permissions.some(permission => auth.user.permissions.includes(permission));
  };
  
  /**
   * Verifica si el usuario tiene todos los permisos especificados
   * 
   * @param permissions Lista de permisos a verificar
   * @returns Verdadero si el usuario tiene todos los permisos
   */
  const canAll = (permissions: string[]): boolean => {
    if (!permissions.length) return true;
    if (!auth?.user?.permissions) return false;
    
    return permissions.every(permission => auth.user.permissions.includes(permission));
  };
  
  /**
   * Filtra un objeto de acciones basado en los permisos requeridos
   * 
   * @param actions Objeto con acciones y sus permisos asociados
   * @returns Objeto filtrado que solo contiene acciones permitidas
   */
  const filterActions = <T extends Record<string, { permission?: string }>>(
    actions: T
  ): Partial<T> => {
    const result: Partial<T> = {};
    
    Object.entries(actions).forEach(([key, value]) => {
      if (!value.permission || can(value.permission)) {
        result[key as keyof T] = value as T[keyof T];
      }
    });
    
    return result;
  };
  
  return {
    can,
    hasRole,
    canAny,
    canAll,
    filterActions,
    permissions: auth?.user?.permissions || [],
    roles: auth?.user?.roles || [],
  };
}
