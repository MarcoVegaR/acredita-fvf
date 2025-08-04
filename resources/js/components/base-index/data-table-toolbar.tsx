import * as React from "react";
import { Table } from "@tanstack/react-table";
import { X, Search, Plus } from "lucide-react";

import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { DataTableViewOptions } from "@/components/base-index/data-table-view-options";
import { DataTableExport } from "@/components/base-index/data-table-export";
import { FilterToolbar, FilterConfig } from "@/components/base-index/filter-toolbar";

interface DataTableToolbarProps<TData> {
  table: Table<TData>;
  filterableColumns?: string[];
  /**
   * Nombre del módulo al que pertenece esta tabla (ej: "users", "roles")
   * Se utiliza para obtener traducciones adecuadas para las columnas
   */
  moduleName?: string;
  /**
   * Texto placeholder para el campo de búsqueda
   */
  searchPlaceholder?: string;
  translationPrefix?: string;
  exportOptions?: {
    enabled: boolean;
    fileName?: string;
    exportTypes?: ("excel" | "csv" | "print" | "copy")[];
    customActions?: Array<{
      label: string;
      icon?: React.ReactNode;
      onClick: () => void;
      permission?: string;
      showCondition?: () => boolean;
    }>;
  };
  showNewButton?: boolean;
  newButtonProps?: {
    label?: string;
    href?: string;
    onClick?: () => void;
  };
  /**
   * Configuración de filtros personalizados
   */
  filterConfig?: FilterConfig;
  /**
   * Mensaje a mostrar cuando no hay filtros activos
   */
  filterEmptyMessage?: string;
  /**
   * Filtros aplicados actualmente
   */
  filters?: Record<string, unknown>;
  /**
   * Endpoint para redireccionar al aplicar filtros
   */
  endpoint?: string;
}

export function DataTableToolbar<TData>({
  table,
  translationPrefix = "",
  moduleName = "",
  searchPlaceholder = "Buscar...",
  exportOptions,
  showNewButton = true,
  newButtonProps = { label: "Nuevo Registro", href: "?/create" },
  filterConfig,
  filterEmptyMessage,
  filters = {},
  endpoint = "",
}: DataTableToolbarProps<TData>) {
  const [globalFilter, setGlobalFilter] = React.useState("");
  
  // Debounce search input
  React.useEffect(() => {
    const timer = setTimeout(() => {
      table.setGlobalFilter(globalFilter);
    }, 300);
    return () => clearTimeout(timer);
  }, [globalFilter, table]);
  
  return (
    <div className="flex flex-col gap-4 py-4 md:flex-row md:items-center md:justify-between">
      <div className="flex flex-1 items-center space-x-4">
        <div className="relative w-full md:w-80 ml-1">
          <Search className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground pointer-events-none" />
          <Input
            placeholder={searchPlaceholder}
            value={globalFilter}
            onChange={(e) => setGlobalFilter(e.target.value)}
            className="h-9 w-full md:w-80 pl-8 pr-10"
          />
          {globalFilter && (
            <Button
              variant="ghost"
              size="icon"
              className="absolute right-0 top-0 h-9 w-9"
              onClick={() => setGlobalFilter("")}
            >
              <X className="h-4 w-4" />
            </Button>
          )}
        </div>
        
        {/* Integración del botón de filtros */}
        {filterConfig && (
          <div className="flex items-center ml-2">
            <FilterToolbar
              filterConfig={filterConfig}
              filters={filters}
              endpoint={endpoint}
              emptyMessage={filterEmptyMessage}
              compact={true}
            />
          </div>
        )}
        
        {showNewButton && (
          <Button 
            variant="default" 
            className="ml-auto bg-primary text-primary-foreground hover:bg-primary/90 font-medium" 
            onClick={newButtonProps.onClick}
            asChild={!!newButtonProps.href}
          >
            <div className="flex items-center gap-1.5">
              <Plus className="h-4 w-4" />
              {newButtonProps.href ? (
                <a href={newButtonProps.href}>{newButtonProps.label}</a>
              ) : (
                newButtonProps.label
              )}
            </div>
          </Button>
        )}
      </div>
      <div className="flex items-center space-x-2">
        {exportOptions?.enabled && (
          <DataTableExport 
            table={table} 
            exportTypes={exportOptions.exportTypes} 
            fileName={exportOptions.fileName}
            moduleName={moduleName}
            customExportActions={exportOptions.customActions} 
          />
        )}
        <DataTableViewOptions 
          table={table} 
          translationPrefix={translationPrefix} 
          moduleName={moduleName}
        />
      </div>
    </div>
  );
}
