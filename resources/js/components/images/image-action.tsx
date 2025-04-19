import React from 'react';
import { router } from '@inertiajs/react';
import { ImageIcon } from 'lucide-react';

/**
 * Componente auxiliar para crear una acción de imágenes que puede ser incluida
 * en el menú de acciones de una tabla de datos.
 * 
 * Uso:
 * ```tsx
 * import { createImageAction } from '@/components/images/image-action';
 * 
 * const actions = {
 *   // Otras acciones...
 *   custom: [
 *     createImageAction('users', permissions.includes('images.view.users'))
 *   ]
 * };
 * ```
 */
export const createImageAction = (
  module: string,
  enabled: boolean = true,
  customLabel?: string
) => {
  return {
    label: customLabel || 'Imágenes',
    icon: <ImageIcon className="h-4 w-4" />,
    enabled: enabled,
    handler: (row: Record<string, unknown>) => {
      // Asegurarnos de que la fila tiene un ID antes de navegar
      if (row && typeof row.id === 'number') {
        router.visit(`/${module}/${row.id}/images`);
      }
    }
  };
};

/**
 * Helper para crear una acción de imágenes directamente integrable en el componente DataTableRowActions
 */
export const createImageRowAction = (
  module: string,
  enabled: boolean = true,
  customLabel?: string
) => {
  const action = createImageAction(module, enabled, customLabel);
  
  return {
    custom: [action]
  };
};
