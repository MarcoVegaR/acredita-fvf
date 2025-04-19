import React from "react";
import { Head } from "@inertiajs/react";
import { DataTable } from "@/components/base-index";
import { DataTableRowActions } from "@/components/base-index/data-table-row-actions";
import { FilterConfig } from "@/components/base-index/filter-toolbar";
import { router } from "@inertiajs/react";
import { SortingState } from "@tanstack/react-table";
import AppLayout from "@/layouts/app-layout";
import { ColumnDef } from "@tanstack/react-table";
import { type BreadcrumbItem } from "@/types";
// El hook useToast está marcado como obsoleto según el sistema de notificaciones centralizado
// Se recomienda usar directamente el toast importado de sonner cuando sea necesario
import { usePage } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { SharedData } from '@/types';

// Tipo genérico para la entidad
export interface Entity {
  id: number;
  // Usamos unknown en lugar de any para mejorar la seguridad de tipos
  // pero manteniendo la flexibilidad para propiedades dinámicas
  [key: string]: unknown;
}

// Opciones para cada tipo de índice
// Interfaz para definir los permisos requeridos para acciones
export interface PermissionRequirements {
  // Permisos para operaciones principales
  show?: string;    // Para ver detalles (antes 'view')
  create?: string;  // Para crear nuevos registros
  edit?: string;    // Para editar registros existentes
  delete?: string;  // Para eliminar registros
  
  // Alias para compatibilidad (view = show)
  view?: string;
  
  // Más genérico para casos especiales
  [key: string]: string | undefined;
}

export interface BaseIndexOptions<T extends Entity> {
  // Configuración básica
  title: string;
  subtitle?: string;
  endpoint: string;
  breadcrumbs: BreadcrumbItem[];
  
  // Permisos requeridos para las acciones
  permissions?: PermissionRequirements;
  
  // Estadísticas para mostrar en tarjetas (una o múltiples)
  stats?: Array<{
    value: number | string;
    label: string;
    icon?: string; // nombre del icono (optional)
    color?: string; // color del icono (optional)
  }>;
  
  /**
   * Nombre del módulo al que pertenece esta tabla (ej: "users", "roles")
   * Se utiliza para obtener traducciones adecuadas para las columnas
   */
  moduleName?: string;
  
  /**
   * Texto personalizado para el placeholder del campo de búsqueda
   */
  searchPlaceholder?: string;
  
  /**
   * Columnas que se incluirán en la búsqueda global
   */
  searchableColumns?: string[];
  
  /**
   * Configuración de filtros personalizados
   */
  filterConfig?: FilterConfig;
  
  /**
   * Mensaje a mostrar cuando no hay filtros activos
   */
  filterEmptyMessage?: string;
  
  // Columnas y configuración de la tabla
  columns: ColumnDef<T>[];
  filterableColumns?: string[];
  defaultSorting?: { id: string; desc: boolean }[];
  
  // Configuración de exportación
  exportOptions?: {
    enabled: boolean;
    fileName?: string;
    exportTypes?: ("excel" | "csv" | "print" | "copy")[];
  };
  
  // Configuración del botón nuevo
  newButton?: {
    show: boolean;
    label: string;
    onClick?: () => void;
    permission?: string; // Permiso requerido para el botón nuevo
  };
  
  // Acciones de fila personalizables
  rowActions?: {
    view?: {
      enabled: boolean;
      label: string;
      permission?: string; // Permiso requerido para ver
      handler?: (row: T) => void;
    };
    edit?: {
      enabled: boolean;
      label: string;
      permission?: string; // Permiso requerido para editar
      handler?: (row: T) => void;
    };
    delete?: {
      enabled: boolean;
      label: string;
      permission?: string; // Permiso requerido para eliminar
      confirmMessage?: (row: T) => string;
      handler?: (row: T) => void;
    };
    custom?: Array<{
      label: string;
      handler: (row: T) => void;
      icon?: React.ReactNode;
      permission?: string | string[];
    }>;
  };
}

// Props para el componente BaseIndexPage
interface BaseIndexPageProps<T extends Entity> {
  data: {
    data: T[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
  filters?: {
    search?: string;
    sort?: string;
    order?: "asc" | "desc";
    page?: number;
    per_page?: number;
  };
  options: BaseIndexOptions<T>;
}

// Componente base para páginas de índice
export function BaseIndexPage<T extends Entity>({ 
  data, 
  filters = {}, 
  options 
}: BaseIndexPageProps<T>) {
  // Ya no necesitamos usar el hook useToast aquí porque usamos el sistema centralizado de FlashMessages
  const { auth } = usePage<SharedData>().props;
  
  // Función para verificar si el usuario tiene un permiso específico
  const hasPermission = React.useCallback((permission?: string): boolean => {
    if (!permission) return true; // Si no se requiere permiso, permitir
    if (!auth?.user?.permissions) return false; // Si no hay permisos disponibles, denegar
    
    return auth.user.permissions.includes(permission);
  }, [auth?.user?.permissions]);
  
  // Manejador genérico para cambios de paginación
  const handlePaginationChange = React.useCallback(({ pageIndex, pageSize }: { pageIndex: number; pageSize: number }) => {
    // Verificar si realmente hay un cambio antes de disparar una navegación
    const newPage = pageIndex + 1;
    const newPerPage = pageSize;
    
    // Solo disparar la navegación si hay un cambio real en la página o tamaño
    if (newPage !== data.current_page || newPerPage !== data.per_page) {
      // Usar router.get() en lugar de router.visit() con preserveState: true
      router.get(
        options.endpoint, 
        {
          ...filters,
          page: newPage,
          per_page: newPerPage,
        }, 
        {
          // Preservar el estado para evitar recrear completamente el componente
          preserveState: true,
          // Mantener la posición de scroll para mejor experiencia de usuario
          preserveScroll: true,
          // No crear una nueva entrada en el historial
          replace: true,
          // Verificar antes de continuar con la visita
          onBefore: () => {
            // Evitar bucles de navegación
            const currentUrl = window.location.href;
            const targetUrl = new URL(options.endpoint, window.location.origin);
            const searchParams = new URLSearchParams();
            
            // Agregar todos los filtros convirtiéndolos a string
            Object.entries(filters).forEach(([key, value]) => {
              if (value !== undefined && value !== null) {
                searchParams.append(key, String(value));
              }
            });
            
            // Agregar los parámetros de paginación
            searchParams.set("page", String(newPage));
            searchParams.set("per_page", String(newPerPage));
            
            targetUrl.search = searchParams.toString();
            
            // Si estamos intentando navegar a la misma URL, cancelar la navegación
            return currentUrl !== targetUrl.href;
          }
        }
      );
    }
    // El linter sugiere quitar router del array de dependencias, ya que es un valor externo,
    // pero necesitamos mantenerlo para acceder a los métodos más recientes
     
  }, [options.endpoint, filters, data.current_page, data.per_page]);

  // Manejador genérico para cambios de ordenamiento
  const handleSortingChange = React.useCallback((sorting: SortingState) => {
    const newSort = sorting[0]?.id || "id";
    const newOrder = sorting[0]?.desc ? "desc" : "asc";
    
    // Solo disparar la navegación si hay un cambio real en el ordenamiento
    if (newSort !== filters.sort || newOrder !== filters.order) {
      router.get(
        options.endpoint, 
        {
          ...filters,
          sort: newSort,
          order: newOrder,
        }, 
        {
          // Preservar el estado para evitar recrear completamente el componente
          preserveState: true,
          // Mantener la posición de scroll para mejor experiencia de usuario
          preserveScroll: true,
          // No crear una nueva entrada en el historial para ordenamiento
          replace: true,
          // Verificar antes de continuar con la visita
          onBefore: () => {
            // Evitar bucles de navegación
            const currentUrl = window.location.href;
            const targetUrl = new URL(options.endpoint, window.location.origin);
            const searchParams = new URLSearchParams();
            
            // Agregar todos los filtros convirtiéndolos a string
            Object.entries(filters).forEach(([key, value]) => {
              if (value !== undefined && value !== null) {
                searchParams.append(key, String(value));
              }
            });
            
            // Agregar los parámetros de ordenamiento
            searchParams.set("sort", newSort);
            searchParams.set("order", newOrder);
            
            targetUrl.search = searchParams.toString();
            
            // Si estamos intentando navegar a la misma URL, cancelar la navegación
            return currentUrl !== targetUrl.href;
          }
        }
      );
    }
     
  }, [options.endpoint, filters]);

  // Manejador genérico para cambios de filtro global/búsqueda
  const handleGlobalFilterChange = React.useCallback((filter: string) => {
    // Solo disparar la navegación si hay un cambio real en la búsqueda
    if (filter !== filters.search) {
      router.get(
        options.endpoint, 
        {
          ...filters,
          search: filter,
          page: 1, // Reset to first page when searching
        }, 
        {
          // Preservar el estado para evitar recrear completamente el componente
          preserveState: true,
          // Mantener la posición de scroll para mejor experiencia de usuario
          preserveScroll: true,
          // Reemplazar en el historial para no llenar el historial con búsquedas
          replace: true,
          // Verificar antes de continuar con la visita
          onBefore: () => {
            // Evitar bucles de navegación
            const currentUrl = window.location.href;
            const targetUrl = new URL(options.endpoint, window.location.origin);
            const searchParams = new URLSearchParams();
            
            // Agregar todos los filtros convirtiéndolos a string
            Object.entries(filters).forEach(([key, value]) => {
              if (value !== undefined && value !== null) {
                searchParams.append(key, String(value));
              }
            });
            
            // Agregar los parámetros de búsqueda
            searchParams.set("search", filter);
            searchParams.set("page", "1"); // Reset to first page when searching
            
            targetUrl.search = searchParams.toString();
            
            // Si estamos intentando navegar a la misma URL, cancelar la navegación
            return currentUrl !== targetUrl.href;
          }
        }
      );
    }
     
  }, [options.endpoint, filters]);
  
  // Renderiza las acciones de fila basadas en la configuración y permisos
  const renderRowActions = (row: T) => {
    // Si no hay acciones configuradas, no renderizar nada
    if (!options.rowActions) return null;
    
    // Verificar permisos para cada acción
    const canView = hasPermission(
      options.rowActions.view?.permission || 
      options.permissions?.show || // Primero buscar 'show'
      options.permissions?.view || // Por compatibilidad, buscar 'view'
      `${options.moduleName}.show`
    );
    
    const canEdit = hasPermission(
      options.rowActions.edit?.permission || 
      options.permissions?.edit || 
      `${options.moduleName}.edit`
    );
    
    const canDelete = hasPermission(
      options.rowActions.delete?.permission || 
      options.permissions?.delete || 
      `${options.moduleName}.delete`
    );
    
    // Procesar las acciones personalizadas y verificar sus permisos
    const customActions = options.rowActions.custom ? options.rowActions.custom
      .filter(action => {
        // Si no tiene permiso definido, se permite
        if (!action.permission) return true;
        
        // Si es un array de permisos, verificar si tiene al menos uno
        if (Array.isArray(action.permission)) {
          return action.permission.some((perm: string) => hasPermission(perm));
        }
        
        // Si es un string, verificar el permiso directamente
        return hasPermission(action.permission);
      })
      .map(action => ({
        ...action,
        // Asegurarnos que el handler esté definido
        handler: action.handler || (() => {})
      })) : undefined;
    
    return (
      <DataTableRowActions
        row={row}
        actions={{
          // Acción de ver detalles
          view: options.rowActions.view && canView ? {
            enabled: options.rowActions.view.enabled,
            label: options.rowActions.view.label,
            handler: (row) => {
              if (options.rowActions?.view?.handler) {
                options.rowActions.view.handler(row);
              } else {
                router.get(`${options.endpoint}/${row.id}`);
              }
            },
          } : undefined,
          
          // Acción de editar
          edit: options.rowActions.edit && canEdit ? {
            enabled: options.rowActions.edit.enabled,
            label: options.rowActions.edit.label,
            handler: (row) => {
              if (options.rowActions?.edit?.handler) {
                options.rowActions.edit.handler(row);
              } else {
                router.get(`${options.endpoint}/${row.id}/edit`);
              }
            },
          } : undefined,
          
          // Acción de eliminar
          delete: options.rowActions.delete && canDelete ? {
            enabled: options.rowActions.delete.enabled,
            label: options.rowActions.delete.label,
            confirmMessage: options.rowActions.delete.confirmMessage?.(row),
            handler: (row) => {
              if (options.rowActions?.delete?.handler) {
                options.rowActions.delete.handler(row);
              } else {
                router.delete(
                  `${options.endpoint}/${row.id}`,
                  {
                    // Eliminar el callback onSuccess redundante
                    // Confiamos en el sistema centralizado de notificaciones (FlashMessages)
                    // para mostrar el mensaje de éxito enviado desde el backend
                    preserveState: false,
                    preserveScroll: true
                  }
                );
              }
            },
          } : undefined,
          
          // Incluir acciones personalizadas
          custom: customActions
        }}
      />
    );
  };
  
  // Manejador para el botón de nuevo
  const handleNewClick = React.useCallback(() => {
    if (options.newButton?.onClick) {
      options.newButton.onClick();
    } else {
      router.get(`${options.endpoint}/create`);
    }
  }, [options.endpoint, options.newButton]);
  
  // Comprobar si debe mostrar el botón de nuevo (basado en permisos)
  const showNewButton = React.useMemo(() => {
    if (!options.newButton?.show) return false;
    
    // Verificar permisos para el botón nuevo
    const requiredPermission = options.newButton?.permission || 
                              options.permissions?.create || 
                              `${options.moduleName}.create`;
                              
    return hasPermission(requiredPermission);
  }, [options.newButton, options.permissions, options.moduleName, hasPermission]);
  
  // Verificar permisos cuando cambian los datos relacionados
  React.useEffect(() => {
    // Validar permisos cuando cambia el módulo o la configuración de permisos
  }, [options.moduleName, options.permissions, auth?.user?.permissions]);

  // Ya no necesitamos una key para forzar el remontaje porque estamos usando preserveState: true
  // Esto permite que el componente mantenga su estado interno mientras los datos se actualizan
  // Sin embargo, todavía podemos usar una key simple basada en el ID de la entidad si es necesario
  const tableKey = `${options.endpoint}-table`;

  return (
    <AppLayout breadcrumbs={options.breadcrumbs}>
      <Head title={options.title} />
      <div className="flex h-full flex-1 flex-col gap-5 p-5 pb-8">
        <div className="flex flex-col space-y-3 mb-5">
          <div className="flex justify-between items-center">
            <div className="flex flex-col">
              <h1 className="text-2xl font-bold tracking-tight">{options.title}</h1>
              {options.subtitle && (
                <p className="text-sm text-muted-foreground mt-1.5">
                  {options.subtitle}
                </p>
              )}
            </div>
            {showNewButton && (
              <Button onClick={handleNewClick} className="ml-4">
                <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4 mr-2" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"><path d="M12 5v14M5 12h14" /></svg>
                {options.newButton!.label}
              </Button>
            )}
          </div>
          
          {/* Componente separador decorativo */}
          <div className="w-full mt-1 mb-4 flex items-center">
            <div className="h-1 w-16 bg-primary rounded-full"></div>
            <div className="h-px flex-1 bg-border ml-2"></div>
          </div>
          
          {/* Tarjetas de estadísticas - flexibles */}
          {options.stats && options.stats.length > 0 && (
            <div className="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-4">
              {options.stats.map((stat, index) => {
                // Determinar el icono basado en el módulo o el valor proporcionado
                let iconJSX = null;
                if (stat.icon === 'users' || (options.moduleName === 'users' && !stat.icon)) {
                  iconJSX = (
                    <svg xmlns="http://www.w3.org/2000/svg" className={`h-6 w-6 ${stat.color || 'text-primary'}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                      <circle cx="9" cy="7" r="4"></circle>
                    </svg>
                  );
                } else if (stat.icon === 'roles' || options.moduleName === 'roles') {
                  iconJSX = (
                    <svg xmlns="http://www.w3.org/2000/svg" className={`h-6 w-6 ${stat.color || 'text-primary'}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                  );
                } else if (stat.icon === 'activity') {
                  iconJSX = (
                    <svg xmlns="http://www.w3.org/2000/svg" className={`h-6 w-6 ${stat.color || 'text-emerald-500'}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                    </svg>
                  );
                } else if (stat.icon === 'calendar') {
                  iconJSX = (
                    <svg xmlns="http://www.w3.org/2000/svg" className={`h-6 w-6 ${stat.color || 'text-amber-500'}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                      <line x1="16" y1="2" x2="16" y2="6"></line>
                      <line x1="8" y1="2" x2="8" y2="6"></line>
                      <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                  );
                } else {
                  // Icono predeterminado
                  iconJSX = (
                    <svg xmlns="http://www.w3.org/2000/svg" className={`h-6 w-6 ${stat.color || 'text-blue-500'}`} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                      <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    </svg>
                  );
                }

                return (
                  <div key={index} className="bg-white dark:bg-neutral-800 p-4 rounded-lg shadow-sm border border-border flex items-center gap-4">
                    {iconJSX}
                    <div>
                      <p className="text-sm font-medium text-muted-foreground">{stat.label}</p>
                      <p className="text-2xl font-bold">{typeof stat.value === 'number' ? stat.value.toLocaleString() : stat.value}</p>
                    </div>
                  </div>
                );
              })}
            </div>
          )}
          
          {/* Ya no mostramos FilterToolbar aquí, ahora está integrado en la barra de herramientas */}
        </div>
        
        <div className="bg-card dark:bg-card shadow-md rounded-lg overflow-hidden border border-border">
          {/* Usamos key para forzar el remontaje cuando cambian los datos */}
          <DataTable
            key={tableKey}
            data={data.data}
            columns={options.columns}
            moduleName={options.moduleName}
            searchPlaceholder={options.searchPlaceholder}
            searchableColumns={options.searchableColumns}
            filterableColumns={options.filterableColumns}
            defaultSorting={options.defaultSorting || [{ id: "id", desc: true }]}
            renderRowActions={renderRowActions}
            exportOptions={options.exportOptions || { enabled: false }}
            toolbarProps={{
              showNewButton: false,
              filterConfig: options.filterConfig,
              filterEmptyMessage: options.filterEmptyMessage,
              filters: filters,
              endpoint: options.endpoint
            }}
            serverSide={{
              totalRecords: data.total,
              pageCount: data.last_page,
              currentPage: data.current_page,
              perPage: data.per_page,
              onPaginationChange: handlePaginationChange,
              onSortingChange: handleSortingChange,
              onGlobalFilterChange: handleGlobalFilterChange,
            }}
          />
        </div>
      </div>
    </AppLayout>
  );
}
