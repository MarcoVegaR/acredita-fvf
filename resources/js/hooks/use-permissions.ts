import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';

/**
 * Hook personalizado para verificar permisos del usuario actual
 */
export function usePermissions() {
  const { auth } = usePage().props as any;
  
  /**
   * Verifica si el usuario tiene un permiso específico
   * @param permission Nombre del permiso a verificar
   * @returns Boolean que indica si el usuario tiene acceso
   */
  const hasPermission = useCallback((permission?: string): boolean => {
    // Si no se requiere permiso específico, permitir acceso
    if (!permission) return true;
    
    // Si no hay información de permisos disponible, denegar acceso
    if (!auth?.user?.permissions) return false;
    
    // Verificar si el usuario tiene el permiso específico
    return auth.user.permissions.includes(permission);
  }, [auth?.user?.permissions]);

  /**
   * Filtra un array de elementos basado en los permisos requeridos
   * @param items Array de elementos con propiedad permission opcional
   * @returns Array filtrado con solo los elementos a los que el usuario tiene acceso
   */
  const filterByPermission = useCallback(<T extends { permission?: string }>(items: T[]): T[] => {
    return items.filter(item => hasPermission(item.permission));
  }, [hasPermission]);

  return {
    hasPermission,
    filterByPermission,
    permissions: auth?.user?.permissions || [],
  };
}
