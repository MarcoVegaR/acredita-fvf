import { cn } from '@/lib/utils';
import { type LucideProps } from 'lucide-react';
import { type ComponentType } from 'react';

interface IconProps extends Omit<LucideProps, 'ref'> {
    // Permitimos que iconNode sea un ComponentType o ReactNode para mayor flexibilidad
    iconNode: ComponentType<LucideProps> | React.ReactNode;
}

export function Icon({ iconNode, className, ...props }: IconProps) {
    // Verificamos el tipo de iconNode para renderizar correctamente
    if (typeof iconNode === 'function') {
        const IconComponent = iconNode as ComponentType<LucideProps>;
        return <IconComponent className={cn('h-4 w-4', className)} {...props} />;
    }
    
    // Para objetos, podemos tener problemas de renderizado, así que retornamos null
    if (typeof iconNode === 'object' && iconNode !== null) {
        // Este es un caso especial para evitar errores de "Objects are not valid as React child"
        return null;
    }
    
    // Si es un valor primitivo (string, number, etc.), lo devolvemos directamente
    return <>{iconNode}</>;
}
