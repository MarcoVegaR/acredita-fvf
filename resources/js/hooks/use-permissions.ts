import { usePage } from '@inertiajs/react';
import { useCallback } from 'react';
import { SharedData } from '@/types';

/**
 * Hook personalizado para verificar permisos del usuario actual
 */
export function usePermissions() {
  const { auth } = usePage<SharedData>().props;
  
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
    // Asegurarnos de que permissions sea un array antes de usar includes
    const permissions = auth.user.permissions;
    if (Array.isArray(permissions)) {
      return permissions.includes(permission);
    }
    
    // Si permissions no es un array, verificar si es exactamente igual al permiso solicitado
    return permissions === permission;
  }, [auth?.user?.permissions]);

  /**
   * Filtra un array de elementos basado en los permisos requeridos
   * @param items Array de elementos con propiedad permission opcional y posibles subitems
   * @returns Array filtrado con solo los elementos a los que el usuario tiene acceso
   */
  const filterByPermission = useCallback(<T extends { permission?: string; items?: T[] }>(items: T[]): T[] => {
    return items.filter(item => {
      // Si el elemento tiene subítems, verificar si al menos uno tiene permiso
      if (item.items && item.items.length > 0) {
        const filteredSubItems = item.items.filter(subItem => hasPermission(subItem.permission));
        return filteredSubItems.length > 0;
      }
      
      // Si no tiene subitems, verificar el permiso directo del elemento
      return hasPermission(item.permission);
    });
  }, [hasPermission]);
  

  return {
    hasPermission,
    filterByPermission,
    permissions: auth?.user?.permissions || [],
  };
}
