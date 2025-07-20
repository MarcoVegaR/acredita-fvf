import React, { useState, useMemo } from "react";
import { 
  Search, ChevronDown, ChevronRight, Shield, CheckIcon, CheckSquare, 
  BoxSelect, X, Users, FileText, MoreHorizontal, Building2, Calendar,
  FileImage, FileCheck, CreditCard
} from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Checkbox } from "@/components/ui/checkbox";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
import { getModuleLabel, formatPermissionName } from "@/utils/translations/role-labels";
import { toast } from "sonner";
import { permissionCategories, groupPermissionsByCategory } from "./permission-categories";

interface Permission {
  name: string;
  module: string;
  description?: string;
}

interface PermissionSelectorProps {
  permissions: Permission[];
  selectedPermissions: string[];
  isReadOnly?: boolean;
  onChange: (permissions: string[]) => void;
}

/**
 * Modern permissions selector component for the role form
 * Features: 
 * - Search functionality
 * - Collapsible module groups
 * - Batch selection per module
 * - Visual indicators for selection state
 */
export const PermissionsSelector: React.FC<PermissionSelectorProps> = ({
  permissions,
  selectedPermissions,
  isReadOnly = false,
  onChange
}) => {
  const [searchQuery, setSearchQuery] = useState("");
  const [openCategories, setOpenCategories] = useState<string[]>([]);
  const [openModules, setOpenModules] = useState<string[]>([]);
  
  // Group permissions by module and category
  const permissionsByCategory = useMemo(() => {
    return groupPermissionsByCategory(permissions);
  }, [permissions]);
  
  // For backward compatibility and search functionality, also maintain module-level grouping
  const permissionsByModule = useMemo(() => {
    const groups: Record<string, Permission[]> = {};
    
    permissions.forEach(permission => {
      // Extraer el módulo desde el nombre del permiso (primera parte antes del punto)
      // Por ejemplo: 'users.view' -> módulo = 'users'
      const moduleParts = permission.name.split('.');
      const moduleKey = moduleParts.length > 1 ? moduleParts[0] : (permission.module || 'general');
      
      if (!groups[moduleKey]) {
        groups[moduleKey] = [];
      }
      groups[moduleKey].push(permission);
    });
    
    return groups;
  }, [permissions]);
  
  // Filter permissions based on search query
  const filteredPermissionsByModule = useMemo(() => {
    if (!searchQuery.trim()) {
      return permissionsByModule;
    }
    
    const filtered: Record<string, Permission[]> = {};
    const query = searchQuery.toLowerCase();
    
    Object.entries(permissionsByModule).forEach(([module, modulePermissions]) => {
      const filteredPermissions = modulePermissions.filter(permission => 
        permission.name.toLowerCase().includes(query) || 
        (permission.description && permission.description.toLowerCase().includes(query)) ||
        getModuleLabel(module).toLowerCase().includes(query)
      );
      
      if (filteredPermissions.length > 0) {
        filtered[module] = filteredPermissions;
      }
    });
    
    return filtered;
  }, [permissionsByModule, searchQuery]);
  
  // Filter categories based on search query
  const filteredPermissionsByCategory = useMemo(() => {
    if (!searchQuery.trim()) {
      return permissionsByCategory;
    }
    
    const query = searchQuery.toLowerCase();
    
    return permissionsByCategory.map(category => {
      const filteredModuleGroups: Record<string, Permission[]> = {};
      
      Object.entries(category.moduleGroups).forEach(([module, modulePermissions]) => {
        const filteredPermissions = (modulePermissions as Permission[]).filter(permission => 
          permission.name.toLowerCase().includes(query) || 
          (permission.description && permission.description.toLowerCase().includes(query)) ||
          getModuleLabel(module).toLowerCase().includes(query) ||
          category.label.toLowerCase().includes(query)
        );
        
        if (filteredPermissions.length > 0) {
          filteredModuleGroups[module] = filteredPermissions;
        }
      });
      
      return {
        ...category,
        moduleGroups: filteredModuleGroups
      };
    }).filter(category => Object.keys(category.moduleGroups).length > 0);
  }, [permissionsByCategory, searchQuery]);
  
  // Auto-expand modules and categories when searching
  React.useEffect(() => {
    if (searchQuery) {
      // Expandir las categorías con resultados
      const categoriesToOpen = filteredPermissionsByCategory.map(category => category.id);
      setOpenCategories(categoriesToOpen);
      
      // Expandir los módulos con resultados
      const modulesToOpen = Object.keys(filteredPermissionsByModule);
      setOpenModules(modulesToOpen);
    }
  }, [searchQuery, filteredPermissionsByModule, filteredPermissionsByCategory]);
  
  // Toggle module expansion using the proper shadcn/ui Accordion approach
  const toggleModule = (moduleId: string, value: boolean) => {
    if (value) {
      setOpenModules(prev => [...prev, moduleId]);
    } else {
      setOpenModules(prev => prev.filter(id => id !== moduleId));
    }
  };
  
  // Toggle a single permission
  const togglePermission = (permissionName: string) => {
    if (isReadOnly) return;
    
    const newSelectedPermissions = [...selectedPermissions];
    const index = newSelectedPermissions.indexOf(permissionName);
    
    if (index === -1) {
      newSelectedPermissions.push(permissionName);
    } else {
      newSelectedPermissions.splice(index, 1);
    }
    
    onChange(newSelectedPermissions);
  };
  
  // Select or deselect all permissions in a module
  const toggleModulePermissions = (module: string, permissionsInModule: Permission[]) => {
    if (isReadOnly) return;
    
    const permissionNames = permissionsInModule.map(p => p.name);
    
    // Check if all permissions in this module are already selected
    const allSelected = permissionNames.every(name => selectedPermissions.includes(name));
    
    let newSelectedPermissions;
    
    if (allSelected) {
      // If all selected, deselect all permissions in this module
      newSelectedPermissions = selectedPermissions.filter(p => !permissionNames.includes(p));
    } else {
      // Otherwise, select all permissions in this module
      const currentSelected = new Set(selectedPermissions);
      permissionNames.forEach(name => currentSelected.add(name));
      newSelectedPermissions = Array.from(currentSelected);
    }
    
    onChange(newSelectedPermissions);
  };
  
  // Get number of selected permissions per module
  const getSelectedCount = (module: string, permissionsInModule: Permission[]) => {
    return permissionsInModule.filter(p => selectedPermissions.includes(p.name)).length;
  };
  
  // Clear search query
  const clearSearch = () => setSearchQuery("");
  
  // Expand all categories and modules
  const expandAll = (e: React.MouseEvent) => {
    // Prevent form submission
    e.preventDefault();
    e.stopPropagation();
    
    // Expandir todas las categorías
    const allCategoryIds = permissionCategories.map(category => category.id);
    setOpenCategories(allCategoryIds);
    
    // Expandir todos los módulos
    const allModuleIds = Object.keys(permissionsByModule);
    setOpenModules(allModuleIds);
  };
  
  // Collapse all categories and modules
  const collapseAll = (e: React.MouseEvent) => {
    // Prevent form submission
    e.preventDefault();
    e.stopPropagation();
    
    // Colapsar todas las categorías y módulos
    setOpenCategories([]);
    setOpenModules([]);
  };
  
  // Format permission name for display
  const formatPermissionName = (permissionName: string): string => {
    const parts = permissionName.split('.');
    if (parts.length !== 2) return permissionName;
    
    const [module, action] = parts;
    const actionMap: Record<string, string> = {
      create: 'Crear',
      edit: 'Editar',
      delete: 'Eliminar',
      view: 'Ver',
      show: 'Ver detalles',
      list: 'Listar',
      import: 'Importar',
      export: 'Exportar',
      manage: 'Administrar'
    };
    
    return `${actionMap[action] || action} ${getModuleLabel(module) || module}`;
  };
  
  // Get icon component for category
  const getCategoryIcon = (icon: string | React.ReactNode): React.ReactNode => {
    // Si ya es un ReactNode, simplemente devolverlo
    if (React.isValidElement(icon)) {
      return icon;
    }
    
    // Si es un string, buscar en el mapa de íconos
    if (typeof icon === 'string') {
      const iconMap: Record<string, React.ReactNode> = {
        Shield: <Shield className="h-4 w-4" />,
        Building2: <Building2 className="h-4 w-4" />,
        Users: <Users className="h-4 w-4" />,
        Calendar: <Calendar className="h-4 w-4" />,
        FileImage: <FileImage className="h-4 w-4" />,
        FileCheck: <FileCheck className="h-4 w-4" />,
        CreditCard: <CreditCard className="h-4 w-4" />,
        FileText: <FileText className="h-4 w-4" />,
        MoreHorizontal: <MoreHorizontal className="h-4 w-4" />
      };
      
      return iconMap[icon] || <Shield className="h-4 w-4" />;
    }
    
    // Por defecto
    return <Shield className="h-4 w-4" />;
  };
  
  // Highlight matching text in search results
  const highlightText = (text: string, query: string): React.ReactNode => {
    if (!query.trim()) {
      return text;
    }
    
    const regex = new RegExp(`(${query.trim()})`, 'gi');
    const parts = text.split(regex);
    
    return parts.map((part, i) => 
      regex.test(part) ? <mark key={i} className="bg-yellow-200 px-0.5 rounded">{part}</mark> : part
    );
  };
  
  return (
    <div className="space-y-4">
      {/* Header section */}
      <div className="flex items-center justify-between mb-2">
        <div className="flex items-center space-x-2">
          <Shield className="h-4 w-4 text-primary" />
          <h3 className="text-sm font-medium">Gestión de permisos</h3>
        </div>
        
        <div className="flex space-x-2">
          <Button 
            type="button"
            variant="outline" 
            size="sm"
            className="h-7 px-2 text-xs"
            onClick={(e) => expandAll(e)}
          >
            <ChevronDown className="h-3.5 w-3.5 mr-1" />
            Expandir todos
          </Button>
          <Button 
            type="button"
            variant="outline" 
            size="sm"
            className="h-7 px-2 text-xs"
            onClick={(e) => collapseAll(e)}
          >
            <ChevronRight className="h-3.5 w-3.5 mr-1" />
            Colapsar todos
          </Button>
        </div>
      </div>
      
      {/* Search box */}
      <div className="relative">
        <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
          <Search className="w-4 h-4 text-muted-foreground" />
        </div>
        <input
          type="search"
          placeholder="Buscar permisos..."
          className="w-full pl-10 pr-4 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
          disabled={isReadOnly}
        />
        {searchQuery && (
          <button 
            type="button"
            className="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground"
            onClick={clearSearch}
            aria-label="Limpiar búsqueda"
          >
            <X className="h-4 w-4" />
          </button>
        )}
      </div>
      
      {/* Category and Module groups */}
      {(filteredPermissionsByCategory.length === 0) ? (
        <div className="text-center py-6 bg-muted/20 rounded-md border">
          <p className="text-muted-foreground">No se encontraron permisos que coincidan con la búsqueda.</p>
        </div>
      ) : (
        <ScrollArea className="h-[420px] rounded-md border">
          <div className="space-y-4 p-3">
            <Accordion 
              type="multiple" 
              className="space-y-3" 
              value={openCategories}
              onValueChange={setOpenCategories}
            >
              {filteredPermissionsByCategory.map((category) => {
                // Calculate total permissions in this category
                const totalPermissionsInCategory = Object.values(category.moduleGroups).reduce(
                  (sum: number, perms) => sum + (perms as Permission[]).length, 0
                );
                
                // Calculate selected permissions in this category
                const selectedPermissionsInCategory = Object.values(category.moduleGroups).reduce(
                  (sum: number, perms) => sum + (perms as Permission[]).filter(
                    p => selectedPermissions.includes(p.name)
                  ).length, 0
                );
                
                return (
                  <AccordionItem 
                    key={category.id} 
                    value={category.id}
                    className="border rounded-md overflow-hidden shadow-sm bg-card"
                  >
                    <AccordionTrigger className="px-4 py-3 hover:bg-muted/10 data-[state=open]:bg-muted/5">
                      <div className="flex items-center gap-3">
                        <div className="flex h-7 w-7 items-center justify-center rounded-full bg-primary/10">
                          {getCategoryIcon(category.icon)}
                        </div>
                        <div className="flex flex-col items-start">
                          <div className="flex items-center gap-2">
                            <h3 className="font-medium text-sm">{category.label}</h3>
                            <Badge variant={selectedPermissionsInCategory === totalPermissionsInCategory && totalPermissionsInCategory > 0 ? "default" : "outline"} className="text-xs">
                              {String(selectedPermissionsInCategory)} / {String(totalPermissionsInCategory)}
                            </Badge>
                          </div>
                          <p className="text-xs text-muted-foreground">
                            {Object.keys(category.moduleGroups).length} módulos
                          </p>
                        </div>
                      </div>
                    </AccordionTrigger>
                    <AccordionContent className="pb-0">
                      <div className="space-y-1">
                        {Object.entries(category.moduleGroups).map(([module, modulePermissions]) => {
                          const permissions = modulePermissions as Permission[];
                          const selectedCount = getSelectedCount(module, permissions);
                          const allSelected = selectedCount === permissions.length;
                          const someSelected = selectedCount > 0 && selectedCount < permissions.length;
                          
                          return (
                            <div key={module} className="rounded-md overflow-hidden border mb-3 bg-background">
                              {/* Module header */}
                              <div className="flex justify-between items-center p-2 shadow-sm bg-muted/10">
                                <div className="flex items-center space-x-2">
                                  <button 
                                    type="button"
                                    onClick={() => toggleModule(module, !openModules.includes(module))}
                                    className="hover:bg-muted rounded p-1"
                                    disabled={isReadOnly}
                                  >
                                    {openModules.includes(module) ? 
                                      <ChevronDown className="h-4 w-4 text-muted-foreground" /> : 
                                      <ChevronRight className="h-4 w-4 text-muted-foreground" />
                                    }
                                  </button>
                                  
                                  <div className="flex items-center gap-2">
                                    <h4 className="font-medium text-sm">{getModuleLabel(module) || module}</h4>
                                    
                                    <Badge variant={allSelected ? "default" : "outline"} className="text-xs">
                                      {selectedCount} / {permissions.length}
                                    </Badge>
                                  </div>
                                </div>
                                
                                {!isReadOnly && (
                                  <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="h-7 text-xs font-normal"
                                    onClick={() => toggleModulePermissions(module, permissions)}
                                  >
                                    {allSelected ? (
                                      <>
                                        <CheckSquare className="h-3.5 w-3.5 mr-1 text-primary" />
                                        Deseleccionar todos
                                      </>
                                    ) : (
                                      <>
                                        <BoxSelect className="h-3.5 w-3.5 mr-1" />
                                        {someSelected ? 'Seleccionar resto' : 'Seleccionar todos'}
                                      </>
                                    )}
                                  </Button>
                                )}
                              </div>
                              
                              {/* Permission list */}
                              {openModules.includes(module) && (
                                <div className="p-1">
                                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-1">
                                    {permissions.map((permission) => (
                                      <div 
                                        key={permission.name}
                                        className={`flex items-start space-x-2 rounded-md border p-2.5 ${selectedPermissions.includes(permission.name) ? 'bg-primary/5 border-primary/20' : 'bg-card hover:bg-muted/10'}`}
                                      >
                                        <Checkbox
                                          checked={selectedPermissions.includes(permission.name)}
                                          onCheckedChange={() => togglePermission(permission.name)}
                                          disabled={isReadOnly}
                                          className="mt-0.5"
                                        />
                                        <div className="space-y-1">
                                          <p className="text-sm font-medium">
                                            {searchQuery ? 
                                              highlightText(formatPermissionName(permission.name), searchQuery) : 
                                              formatPermissionName(permission.name)
                                            }
                                          </p>
                                          {permission.description && (
                                            <p className="text-xs text-muted-foreground">
                                              {searchQuery ? 
                                                highlightText(permission.description, searchQuery) : 
                                                permission.description
                                              }
                                            </p>
                                          )}
                                        </div>
                                      </div>
                                    ))}
                                  </div>
                                </div>
                              )}
                            </div>
                          );
                        })}
                      </div>
                    </AccordionContent>
                  </AccordionItem>
                );
              })}
            </Accordion>
          </div>
        </ScrollArea>
      )}
      
      {/* Summary */}
      <div className="flex justify-between items-center p-3 bg-muted/10 rounded-md border">
        <div className="flex items-center gap-2">
          <CheckIcon className={`h-4 w-4 ${selectedPermissions.length > 0 ? 'text-primary' : 'text-muted-foreground'}`} />
          <span className="text-sm">
            {selectedPermissions.length} de {permissions.length} permisos seleccionados
          </span>
        </div>
        
        {!isReadOnly && (
          <Button
            type="button"
            variant="outline"
            size="sm"
            className="h-7 text-xs"
            onClick={() => {
              onChange([]);
              toast.info("Todos los permisos han sido deseleccionados");
            }}
            disabled={selectedPermissions.length === 0}
          >
            <X className="h-3.5 w-3.5 mr-1" />
            Deseleccionar todos
          </Button>
        )}
      </div>
    </div>
  );
};
