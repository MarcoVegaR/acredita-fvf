import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, Settings, Users, FileImage, Building } from 'lucide-react';
import React from 'react';

interface MenuItemProps {
  item: NavItem;
  isActive?: boolean;
  variant?: 'header' | 'sidebar' | 'mobile';
  className?: string;
}

/**
 * Componente MenuItem mejorado con iconos más prominentes
 */
export function MenuItem({ item, isActive = false, variant = 'header', className }: MenuItemProps) {
  const iconClass = variant === 'header' 
    ? 'h-6 w-6 mr-3 flex-shrink-0'
    : 'h-5 w-5 mr-2.5 flex-shrink-0';
  
  const itemClass = cn(
    'flex items-center rounded-md transition-colors duration-200',
    variant === 'header' && 'px-4 py-2 font-medium',
    variant === 'sidebar' && 'px-3 py-2.5 w-full text-sm',
    variant === 'mobile' && 'px-2 py-2.5 w-full',
    isActive
      ? 'bg-sidebar-accent text-sidebar-accent-foreground font-semibold'
      : 'hover:bg-sidebar-accent/50 hover:text-sidebar-accent-foreground',
    className
  );

  // Renderizar el icono apropiado
  const getIcon = () => {
    // Si el item tiene un icono definido como componente React, usarlo
    if (item.icon && typeof item.icon === 'function') {
      // TypeScript: Tratar el item.icon como un componente React (Function Component o Class Component)
      const IconComponent = item.icon;
      return <IconComponent className={iconClass} />;
    }
    
    // Fallback por si no hay icono definido
    if (!item.title) return null;
    
    switch(item.title) {
      case 'Dashboard':
        return <LayoutGrid className={iconClass} />;
      case 'Usuarios':
        return <Users className={iconClass} />;
      case 'Áreas':
        return <Building className={iconClass} />;
      case 'Roles':
        return <Settings className={iconClass} />;
      case 'Plantillas':
        return <FileImage className={iconClass} />;
      case 'Repository':
        return <Folder className={iconClass} />;
      case 'Documentation':
        return <BookOpen className={iconClass} />;
      default:
        return null;
    }
  };

  // Para enlaces externos (que tengan http o https)
  if (item.href?.startsWith('http')) {
    return (
      <a href={item.href} target="_blank" rel="noopener noreferrer" className={itemClass}>
        {getIcon()}
        <span className="truncate">{item.title}</span>
      </a>
    );
  }

  // Para enlaces internos
  return (
    <Link href={item.href || '#'} className={itemClass} prefetch>
      {getIcon()}
      <span className="truncate">{item.title}</span>
    </Link>
  );
}
