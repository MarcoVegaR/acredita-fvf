import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, Settings, Users, FileImage, Building, Truck, UserRound, ChevronDown, TicketCheck } from 'lucide-react';
import React from 'react';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { usePermissions } from '@/hooks/use-permissions';

interface MenuItemProps {
  item: NavItem;
  isActive?: boolean;
  variant?: 'header' | 'sidebar' | 'mobile';
  className?: string;
}

/**
 * Componente MenuItem mejorado con iconos más prominentes y soporte para submenús
 */
export function MenuItem({ item, isActive = false, variant = 'header', className }: MenuItemProps) {
  const { filterByPermission } = usePermissions();
  // Unificamos el tamaño de todos los iconos para que sean consistentes
  const iconClass = 'h-5 w-5 mr-3 flex-shrink-0';
  
  const itemClass = cn(
    'flex items-center transition-colors duration-200',
    variant === 'header' && 'px-3 py-2 font-medium',
    variant === 'sidebar' && 'px-3 py-2.5 w-full text-sm',
    variant === 'mobile' && 'px-2 py-2.5 w-full',
    isActive
      ? 'bg-sidebar-accent text-sidebar-accent-foreground font-semibold'
      : 'hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground',
    className
  );

  // Renderizar el icono apropiado
  const getIcon = (title: string) => {
    // Si el item tiene un icono definido como componente React, usarlo
    if (item.icon && typeof item.icon === 'function') {
      // TypeScript: Tratar el item.icon como un componente React (Function Component o Class Component)
      const IconComponent = item.icon;
      return <IconComponent className={iconClass} />;
    }
    
    // Fallback por si no hay icono definido
    if (!title) return null;
    
    switch(title) {
      case 'Dashboard':
        return <LayoutGrid className={iconClass} />;
      case 'Usuarios':
        return <Users className={iconClass} />;
      case 'Áreas':
        return <Building className={iconClass} />;
      case 'Acreditaciones':
        return <TicketCheck className={iconClass} />;
      case 'Proveedores':
        return <Truck className={iconClass} />;
      case 'Empleados':
        return <UserRound className={iconClass} />;
      case 'Roles':
      case 'Administración':
        return <Settings className={iconClass} />;
      case 'Plantillas':
        return <FileImage className={iconClass} />;
      case 'Entidades':
        return <Building className={iconClass} />;
      case 'Personal':
        return <UserRound className={iconClass} />;
      case 'Documentación':
        return <FileImage className={iconClass} />;
      case 'Repository':
        return <Folder className={iconClass} />;
      case 'Documentation':
        return <BookOpen className={iconClass} />;
      default:
        return null;
    }
  };

  // Para elementos con submenús
  if (item.items && item.items.length > 0) {
    const filteredItems = filterByPermission(item.items);
    
    if (filteredItems.length === 0) {
      return null; // No mostrar el menú si no hay elementos con permisos
    }
    
    return (
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button 
            variant="ghost" 
            className="flex h-16 items-center hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground px-3 py-2 font-medium"
            aria-expanded={false}
          >
            <div className="flex items-center gap-2">
              {getIcon(item.title)}
              <span className="font-medium">{item.title}</span>
            </div>
            <ChevronDown className="h-5 w-5 opacity-60 ml-1" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" sideOffset={0} className="w-56 p-1 bg-popover border-popover-border">
          {filteredItems.map((subItem, index) => (
            <DropdownMenuItem key={index} asChild>
              <Link 
                href={subItem.href || '#'} 
                className="flex w-full items-center gap-3 py-2 px-2 rounded-md hover:bg-accent"
                prefetch
              >
                {getIcon(subItem.title)}
                <span className="font-medium text-base">{subItem.title}</span>
              </Link>
            </DropdownMenuItem>
          ))}
        </DropdownMenuContent>
      </DropdownMenu>
    );
  }

  // Para enlaces externos (que tengan http o https)
  if (item.href?.startsWith('http')) {
    return (
      <a href={item.href} target="_blank" rel="noopener noreferrer" className={itemClass}>
        {getIcon(item.title)}
        <span className="truncate">{item.title}</span>
      </a>
    );
  }

  // Para enlaces internos - asegurando consistencia visual con los dropdown
  return (
    <Link href={item.href || '#'} className={cn(itemClass, "h-16 px-3 py-2 font-medium")} prefetch>
      {getIcon(item.title)}
      <span className="truncate font-medium">{item.title}</span>
    </Link>
  );
}
