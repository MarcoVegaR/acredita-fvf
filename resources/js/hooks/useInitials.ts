/**
 * Hook para generar iniciales de un nombre
 */
export function useInitials() {
  return (name: string | null | undefined): string => {
    if (!name) return '??';
    
    return name
      .split(' ')
      .map(word => word.charAt(0).toUpperCase())
      .slice(0, 2) // Solo las primeras 2 iniciales
      .join('');
  };
}
