import * as React from "react";
import { router } from "@inertiajs/react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator
} from "@/components/ui/dropdown-menu";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";
import { 
  Separator 
} from "@/components/ui/separator";
import { X, Filter } from "lucide-react";

// Types for different filter options
export type FilterOption = {
  value: string;
  label: string;
};

export type FilterConfig = {
  // Types of filters
  select?: {
    id: string;
    label: string;
    options: FilterOption[];
    defaultValue?: string;
  }[];
  boolean?: {
    id: string;
    label: string;
    trueLabel: string;
    falseLabel: string;
    defaultValue?: boolean | string;
  }[];
  // Removed date filters since calendar component is not available
};

export interface FilterToolbarProps {
  /**
   * Configuration for the filters to display
   */
  filterConfig: FilterConfig;
  /**
   * Current filter values from route/URL
   */
  filters?: Record<string, unknown>;
  /**
   * Base endpoint to submit filters to
   */
  endpoint: string;
  /**
   * Mensaje a mostrar cuando no hay filtros activos
   */
  emptyMessage?: string;
  /**
   * Modo compacto para mostrar solo el botón en la barra de herramientas
   */
  compact?: boolean;
}

export function FilterToolbar({
  filterConfig,
  filters = {},
  endpoint,
  emptyMessage = "Sin filtros activos",
  compact = false,
}: FilterToolbarProps) {
  // Using router directly from @inertiajs/react
  const [activeFilters, setActiveFilters] = React.useState<Record<string, unknown>>({});
  const [tempFilters, setTempFilters] = React.useState<Record<string, unknown>>(
    Object.fromEntries(
      Object.entries(filters).filter(([key]) => {
        return (
          (filterConfig.select && filterConfig.select.some(f => f.id === key)) ||
          (filterConfig.boolean && filterConfig.boolean.some(f => f.id === key))
        );
      })
    )
  );
  const [isFilterMenuOpen, setIsFilterMenuOpen] = React.useState<boolean>(false);

  // Set active filters on component mount
  React.useEffect(() => {
    const initialActiveFilters: Record<string, unknown> = {};
    
    // Process select filters
    filterConfig.select?.forEach((filter) => {
      if (filters[filter.id]) {
        initialActiveFilters[filter.id] = filters[filter.id];
      }
    });
    
    // Process boolean filters
    filterConfig.boolean?.forEach((filter) => {
      if (filters[filter.id] !== undefined) {
        // Convert string "true"/"false" to boolean if needed
        const value = filters[filter.id];
        initialActiveFilters[filter.id] = typeof value === 'string' 
          ? value === 'true' 
          : value;
      }
    });
    
    setActiveFilters(initialActiveFilters);
    setTempFilters(initialActiveFilters);
  }, [filters, filterConfig, endpoint]);

  // Function to clear all filters
  const clearAllFilters = React.useCallback(() => {
    setActiveFilters({});
    setTempFilters({});
    
    // Only keep non-filter parameters
    const nonFilterParams: Record<string, unknown> = {};
    Object.entries(filters || {}).forEach(([key, value]) => {
      if (key === 'page' || key === 'sort' || key === 'order' || key === 'per_page' || key === 'search') {
        nonFilterParams[key] = value;
      }
    });
    
    // Navigate to the URL without filters
    router.get(endpoint, nonFilterParams as Record<string, string>);
    setIsFilterMenuOpen(false);
  }, [filters, endpoint]);

  // Function to apply filters
  const applyFilters = React.useCallback(() => {
    // Actualizar active filters para display incluso si están vacíos
    setActiveFilters(tempFilters);
    
    // Construir parámetros preservando los existentes
    const baseParams = Object.fromEntries(
      // Mantener solo los parámetros de paginación y búsqueda del filtro actual
      Object.entries(filters || {}).filter(([key]) => 
        ['page', 'per_page', 'search', 'sort', 'order'].includes(key)
      )
    );
    
    // Filtrar tempFilters válidos
    const validTempFilters = Object.fromEntries(
      Object.entries(tempFilters).filter(
        ([, value]) => value !== undefined && value !== ''
      )
    );
    
    const params: Record<string, unknown> = { 
      ...baseParams,
      ...validTempFilters
    };
    
    // Preservar el scroll y estado para una experiencia fluida
    router.get(endpoint, params as Record<string, string>, {
      preserveState: true,
      preserveScroll: true,
      replace: true
    });
    
    setIsFilterMenuOpen(false);
  }, [tempFilters, endpoint, filters]);

  // Function to remove a specific filter
  const removeFilter = React.useCallback((key: string) => {
    console.log('[DEBUG] removeFilter - Eliminando filtro:', key);
    
    // Create a copy of filters without the removed one
    const newFilters: Record<string, unknown> = { ...filters || {} };
    delete newFilters[key];
    
    // Remove from active filters for display
    setActiveFilters(prev => {
      const newState = { ...prev };
      delete newState[key];
      return newState;
    });
    
    // Remove from temp filters
    setTempFilters(prev => {
      const newState = { ...prev };
      delete newState[key];
      return newState;
    });
    
    // Navigate to the updated URL
    router.get(endpoint, newFilters as Record<string, string>);
  }, [filters, endpoint]);

  // Get label for active filter value
  const getFilterValueLabel = React.useCallback((filterId: string, value: unknown): string => {
    // For select filters, find the matching option
    const selectFilter = filterConfig.select?.find(filter => filter.id === filterId);
    if (selectFilter) {
      const option = selectFilter.options.find(option => option.value === value);
      return option?.label || String(value);
    }
    
    // For boolean filters
    const booleanFilter = filterConfig.boolean?.find(filter => filter.id === filterId);
    if (booleanFilter) {
      return value === true || value === 'true' 
        ? booleanFilter.trueLabel 
        : booleanFilter.falseLabel;
    }
    
    return String(value);
  }, [filterConfig]);

  // Get the filter name for display
  const getFilterLabel = React.useCallback((filterId: string): string => {
    // First check select filters
    const selectFilter = filterConfig.select?.find(filter => filter.id === filterId);
    if (selectFilter) return selectFilter.label;
    
    // Then check boolean filters
    const booleanFilter = filterConfig.boolean?.find(filter => filter.id === filterId);
    if (booleanFilter) return booleanFilter.label;
    
    return filterId;
  }, [filterConfig]);

  // Count active filters
  const activeFilterCount = Object.keys(activeFilters).length;
  const hasActiveFilters = activeFilterCount > 0;

  const renderFilterControls = () => {
    return (
      <div className="space-y-4">
        {/* Select filters */}
        {filterConfig.select && filterConfig.select.length > 0 && (
          <div className="space-y-3">
            {filterConfig.select.map((filter) => (
              <div key={filter.id} className="space-y-1">
                <label htmlFor={filter.id} className="text-xs font-medium">
                  {filter.label}
                </label>
                <Select
                  // No usar 'all' como valor predeterminado, usar el valor real del filtro
                  value={typeof tempFilters[filter.id] === 'string' ? tempFilters[filter.id] as string : filter.defaultValue?.toString()}
                  onValueChange={(value) => {
                    // Siempre establecer el valor seleccionado, sin logica especial para 'all'
                    setTempFilters(prev => ({ ...prev, [filter.id]: value }));
                  }}
                >
                  <SelectTrigger id={filter.id}>
                    <SelectValue placeholder="Seleccionar..." />
                  </SelectTrigger>
                  <SelectContent>
                    {/* No incluir opción 'all' automáticamente - usar las opciones definidas */}
                    {filter.options.map((option) => (
                      <SelectItem key={option.value} value={option.value}>
                        {option.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            ))}
          </div>
        )}
        
        {/* Boolean filters */}
        {filterConfig.boolean && filterConfig.boolean.length > 0 && (
          <div className="space-y-3">
            {filterConfig.boolean.map((filter) => (
              <div key={filter.id} className="space-y-1">
                <label htmlFor={filter.id} className="text-xs font-medium">
                  {filter.label}
                </label>
                <Select
                  defaultValue={
                    tempFilters[filter.id] !== undefined 
                      ? String(tempFilters[filter.id]) 
                      : (filter.defaultValue !== undefined ? String(filter.defaultValue) : 'all')
                  }
                  onValueChange={(value) => {
                    if (value === 'all') {
                      setTempFilters(prev => {
                        const newFilters = { ...prev };
                        delete newFilters[filter.id];
                        return newFilters;
                      });
                    } else {
                      setTempFilters(prev => ({ 
                        ...prev, 
                        [filter.id]: value === 'true' 
                      }));
                    }
                  }}
                >
                  <SelectTrigger id={filter.id}>
                    <SelectValue placeholder="Seleccionar..." />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Todos</SelectItem>
                    <SelectItem value="true">{filter.trueLabel}</SelectItem>
                    <SelectItem value="false">{filter.falseLabel}</SelectItem>
                  </SelectContent>
                </Select>
              </div>
            ))}
          </div>
        )}
      </div>
    );
  };

  if (compact) {
    return (
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="outline" size="sm" className="h-9 px-2 lg:px-3 flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
              <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
            </svg>
            <span className="ml-1">Filtrar</span>
            {hasActiveFilters && (
              <Badge variant="secondary" className="ml-1 px-1 py-0 h-5 min-w-5 flex items-center justify-center">
                {activeFilterCount}
              </Badge>
            )}
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" className="w-56">
          <DropdownMenuLabel>Filtros</DropdownMenuLabel>
          <DropdownMenuSeparator />
          {renderFilterControls()}
          {hasActiveFilters && (
            <DropdownMenuItem onClick={clearAllFilters} className="justify-center text-destructive">
              Limpiar todos los filtros
            </DropdownMenuItem>
          )}
          <DropdownMenuSeparator />
          <DropdownMenuItem onClick={applyFilters} className="justify-center">
            Aplicar filtros
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    );
  }

  // Versión completa para mostrar en una sección dedicada
  return (
    <div className="flex flex-col gap-2">
      {/* Filter button and active filters display */}
      <div className="flex items-center gap-2 flex-wrap">
        <DropdownMenu open={isFilterMenuOpen} onOpenChange={setIsFilterMenuOpen}>
          <DropdownMenuTrigger asChild>
            <Button 
              variant="outline" 
              size="sm" 
              className="h-8 gap-1 px-3"
            >
              <Filter className="h-3.5 w-3.5" />
              <span>Filtrar</span>
              {hasActiveFilters && (
                <Badge variant="secondary" className="ml-1 h-5 px-1.5 rounded-full">
                  {activeFilterCount}
                </Badge>
              )}
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent className="w-72 p-3" align="start">
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h4 className="font-medium">Filtros</h4>
                <Button 
                  variant="ghost" 
                  size="sm" 
                  onClick={clearAllFilters}
                  className="h-7 text-xs"
                >
                  Limpiar todos
                </Button>
              </div>
              <Separator />
              
              {/* Select filters */}
              {filterConfig.select && filterConfig.select.length > 0 && (
                <div className="space-y-3">
                  {filterConfig.select.map((filter) => (
                    <div key={filter.id} className="space-y-1">
                      <label htmlFor={filter.id} className="text-xs font-medium">
                        {filter.label}
                      </label>
                      <Select
                        value={typeof tempFilters[filter.id] === 'string' ? tempFilters[filter.id] as string : filter.defaultValue?.toString()}
                        onValueChange={(value) => {
                          // Siempre establecer el valor seleccionado directamente
                          setTempFilters(prev => ({ ...prev, [filter.id]: value }));
                        }}
                      >
                        <SelectTrigger id={filter.id}>
                          <SelectValue placeholder="Seleccionar..." />
                        </SelectTrigger>
                        <SelectContent>
                          {filter.options.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                              {option.label}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </div>
                  ))}
                </div>
              )}
              
              {/* Boolean filters */}
              {filterConfig.boolean && filterConfig.boolean.length > 0 && (
                <div className="space-y-3">
                  {filterConfig.boolean.map((filter) => (
                    <div key={filter.id} className="space-y-1">
                      <label htmlFor={filter.id} className="text-xs font-medium">
                        {filter.label}
                      </label>
                      <Select
                        defaultValue={
                          tempFilters[filter.id] !== undefined 
                            ? String(tempFilters[filter.id]) 
                            : (filter.defaultValue !== undefined ? String(filter.defaultValue) : 'all')
                        }
                        onValueChange={(value) => {
                          if (value === 'all') {
                            setTempFilters(prev => {
                              const newFilters = { ...prev };
                              delete newFilters[filter.id];
                              return newFilters;
                            });
                          } else {
                            setTempFilters(prev => ({ 
                              ...prev, 
                              [filter.id]: value === 'true' 
                            }));
                          }
                        }}
                      >
                        <SelectTrigger id={filter.id}>
                          <SelectValue placeholder="Seleccionar..." />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="all">Todos</SelectItem>
                          <SelectItem value="true">{filter.trueLabel}</SelectItem>
                          <SelectItem value="false">{filter.falseLabel}</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                  ))}
                </div>
              )}
              
              {/* Removed date filters since calendar component is not available */}
              
              <div className="flex justify-end gap-2 pt-2">
                <Button 
                  variant="outline" 
                  size="sm" 
                  onClick={() => setIsFilterMenuOpen(false)}
                >
                  Cancelar
                </Button>
                <Button 
                  variant="default" 
                  size="sm"
                  onClick={applyFilters}
                >
                  Aplicar
                </Button>
              </div>
            </div>
          </DropdownMenuContent>
        </DropdownMenu>
        
        {/* Active filter badges */}
        {activeFilterCount > 0 ? (
          <div className="flex flex-wrap gap-1.5">
            {Object.entries(activeFilters).map(([filterId, value]) => (
              <Badge 
                key={filterId} 
                variant="secondary"
                className="pl-2 h-8 gap-1 pr-1 text-xs font-normal"
              >
                <span><strong>{getFilterLabel(filterId)}:</strong> {getFilterValueLabel(filterId, value)}</span>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => removeFilter(filterId)}
                  className="h-6 w-6 p-0 ml-1 hover:bg-muted rounded-full"
                >
                  <X className="h-3 w-3" />
                </Button>
              </Badge>
            ))}
            
            {activeFilterCount > 1 && (
              <Button
                variant="ghost"
                size="sm"
                onClick={clearAllFilters}
                className="h-8 px-2 text-xs"
              >
                Limpiar todos
              </Button>
            )}
          </div>
        ) : (
          <span className="text-muted-foreground text-sm">
            {emptyMessage}
          </span>
        )}
      </div>
    </div>
  );
}
