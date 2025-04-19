import React from 'react';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { ImageIcon } from 'lucide-react';
import { getColumnLabel } from '@/utils/translations/column-labels';

interface ImagesLinkProps {
  module: string;
  entityId: number;
  count?: number;
  className?: string;
  variant?: 'default' | 'destructive' | 'outline' | 'secondary' | 'ghost' | 'link';
  size?: 'default' | 'sm' | 'lg' | 'icon';
}

/**
 * Componente que renderiza un botón/enlace para acceder a las imágenes de una entidad.
 * Útil para añadir en secciones de detalles o páginas de visualización.
 */
export default function ImagesLink({
  module,
  entityId,
  count = 0,
  className = '',
  variant = 'outline',
  size = 'default'
}: ImagesLinkProps) {
  return (
    <Button
      variant={variant}
      size={size}
      className={className}
      asChild
    >
      <Link href={`/${module}/${entityId}/images`}>
        <ImageIcon className="mr-2 h-4 w-4" />
        {getColumnLabel('images', 'section_title')}
        {count > 0 && (
          <span className="ml-2 rounded-full bg-primary px-2 py-0.5 text-xs text-primary-foreground">
            {count}
          </span>
        )}
      </Link>
    </Button>
  );
}
