import React, { useState, useMemo } from "react";
import { Search, ChevronDown, ChevronRight, Layers, Shield } from "lucide-react";

interface Permission {
  name: string;
  nameshow: string;
}

interface RolePermissionsRendererProps {
  roles: string[];
  rolePermissions: Record<string, Permission[]>;
}

/**
 * Componente reutilizable para mostrar roles y sus permisos asociados
 * con capacidades avanzadas de agrupación, búsqueda y visualización
 */
export const RolePermissionsRenderer: React.FC<RolePermissionsRendererProps> = ({ 
  roles,
  rolePermissions
}) => {
  const [searchQuery, setSearchQuery] = useState("");
  const [expandedRoles, setExpandedRoles] = useState<Record<string, boolean>>({}); 
  const [expandedModules, setExpandedModules] = useState<Record<string, Record<string, boolean>>>({});
  
  // Inicializar los estados de expansión por defecto (todos cerrados)
  React.useEffect(() => {
    const initialRoleState: Record<string, boolean> = {};
    const initialModuleState: Record<string, Record<string, boolean>> = {};
    
    roles.forEach(role => {
      initialRoleState[role] = false; // Roles colapsados por defecto
      initialModuleState[role] = {};
      
      // Agrupar permisos por módulo
      const moduleGroups = groupPermissionsByModule(rolePermissions[role] || []);
      
      // Establecer todos los módulos como colapsados
      Object.keys(moduleGroups).forEach(module => {
        initialModuleState[role][module] = false;
      });
    });
    
    setExpandedRoles(initialRoleState);
    setExpandedModules(initialModuleState);
  }, [roles, rolePermissions]);
  
  // Agrupa permisos por módulo (primera parte del nombre, antes del punto)
  const groupPermissionsByModule = (permissions: Permission[]) => {
    return permissions.reduce<Record<string, Permission[]>>((modules, permission) => {
      const [module] = permission.name.split('.');
      if (!modules[module]) {
        modules[module] = [];
      }
      modules[module].push(permission);
      return modules;
    }, {});
  };
  
  // Obtener la cantidad total de permisos por rol
  const getPermissionCount = (role: string) => {
    return rolePermissions[role]?.length || 0;
  };
  
  // Filtrar y agrupar los permisos según la búsqueda
  const filteredAndGroupedPermissions = useMemo(() => {
    const result: Record<string, Record<string, Permission[]>> = {};
    
    roles.forEach(role => {
      const permissions = rolePermissions[role] || [];
      
      // Filtrar por búsqueda si hay una
      const filtered = searchQuery 
        ? permissions.filter(p => 
            p.nameshow.toLowerCase().includes(searchQuery.toLowerCase()) ||
            p.name.toLowerCase().includes(searchQuery.toLowerCase())
          )
        : permissions;
      
      // Agrupar por módulo
      result[role] = groupPermissionsByModule(filtered);
    });
    
    return result;
  }, [roles, rolePermissions, searchQuery]);
  
  // Expandir automáticamente los resultados de búsqueda
  React.useEffect(() => {
    if (searchQuery) {
      // Si hay una búsqueda, expandir todos los roles que tengan resultados
      const newExpandedRoles = {...expandedRoles};
      const newExpandedModules = {...expandedModules};
      
      roles.forEach(role => {
        const modules = filteredAndGroupedPermissions[role] || {};
        const hasResults = Object.values(modules).some(perms => perms.length > 0);
        
        if (hasResults) {
          // Expandir el rol si tiene resultados
          newExpandedRoles[role] = true;
          
          // Expandir todos los módulos de este rol que tengan resultados
          if (!newExpandedModules[role]) {
            newExpandedModules[role] = {};
          }
          
          Object.entries(modules).forEach(([module, perms]) => {
            if (perms.length > 0) {
              newExpandedModules[role][module] = true;
            }
          });
        }
      });
      
      setExpandedRoles(newExpandedRoles);
      setExpandedModules(newExpandedModules);
    }
  }, [searchQuery, filteredAndGroupedPermissions, roles, expandedRoles, expandedModules]);
  
  // Alternar estado de expansión de un rol
  const toggleRoleExpansion = (role: string) => {
    setExpandedRoles(prev => ({
      ...prev,
      [role]: !prev[role]
    }));
  };
  
  // Alternar estado de expansión de un módulo
  const toggleModuleExpansion = (role: string, module: string) => {
    setExpandedModules(prev => ({
      ...prev,
      [role]: {
        ...(prev[role] || {}),
        [module]: !(prev[role]?.[module] ?? false)
      }
    }));
  };
  
  return (
    <div className="space-y-4">
      <div className="p-4 border rounded-md bg-muted/10">
        <div className="flex items-center gap-2 mb-4">
          <Shield className="w-4 h-4 text-primary" />
          <p className="text-sm font-medium text-muted-foreground">
            Los permisos son heredados de los roles asignados. Los usuarios reciben todos los permisos de sus roles.
          </p>
        </div>
        
        {/* Barra de búsqueda */}
        <div className="relative mb-4">
          <div className="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
            <Search className="w-4 h-4 text-muted-foreground" />
          </div>
          <input
            type="search"
            placeholder="Buscar permisos..."
            className="w-full pl-10 pr-4 py-2 border rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary"
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
          {searchQuery && (
            <button 
              className="absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground hover:text-foreground"
              onClick={() => setSearchQuery('')}
              aria-label="Limpiar búsqueda"
            >
              <span className="text-xs bg-muted rounded-full px-1.5 py-0.5">ESC</span>
            </button>
          )}
        </div>
        
        {roles && roles.length > 0 ? (
          <div className="space-y-4">
            {roles.map((role, roleIndex) => {
              const permissionCount = getPermissionCount(role);
              const hasFilteredPermissions = Object.values(filteredAndGroupedPermissions[role] || {}).flat().length > 0;
              const modules = filteredAndGroupedPermissions[role] || {};
              
              if (searchQuery && !hasFilteredPermissions) return null;
              
              return (
                <div key={roleIndex} className="border rounded-md overflow-hidden">
                  {/* Encabezado del rol (expandible) */}
                  <button 
                    onClick={() => toggleRoleExpansion(role)}
                    className="w-full flex items-center justify-between p-3 bg-card hover:bg-muted/20 text-left transition-colors"
                  >
                    <div className="flex items-center gap-2">
                      <span className="inline-flex items-center justify-center size-6 rounded-full bg-primary/10 text-primary font-semibold">
                        {roleIndex + 1}
                      </span>
                      <span className="font-medium">{role}</span>
                      <span className="text-xs px-2 py-0.5 rounded-full bg-primary/5 text-primary ml-2">
                        {permissionCount} permisos
                      </span>
                    </div>
                    {expandedRoles[role] ? 
                      <ChevronDown className="h-5 w-5 flex-shrink-0 text-muted-foreground" /> : 
                      <ChevronRight className="h-5 w-5 flex-shrink-0 text-muted-foreground" />
                    }
                  </button>
                  
                  {/* Contenido de permisos por módulo */}
                  {expandedRoles[role] && (
                    <div className="p-3 border-t">
                      {Object.keys(modules).length > 0 ? (
                        <div className="space-y-3">
                          {Object.entries(modules).map(([module, modulePermissions]) => (
                            <div key={module} className="border border-muted rounded-md overflow-hidden">
                              {/* Encabezado de módulo (expandible) */}
                              <button 
                                onClick={() => toggleModuleExpansion(role, module)}
                                className="w-full flex items-center justify-between p-2 hover:bg-muted/10 text-left transition-colors"
                              >
                                <div className="flex items-center gap-2">
                                  <Layers className="h-4 w-4 text-muted-foreground" />
                                  <span className="text-sm font-medium capitalize">{module}</span>
                                  <span className="text-xs px-1.5 py-0.5 rounded-full bg-muted text-muted-foreground">
                                    {modulePermissions.length}
                                  </span>
                                </div>
                                {expandedModules[role]?.[module] ? 
                                  <ChevronDown className="h-4 w-4 flex-shrink-0 text-muted-foreground" /> : 
                                  <ChevronRight className="h-4 w-4 flex-shrink-0 text-muted-foreground" />
                                }
                              </button>
                              
                              {/* Lista de permisos del módulo */}
                              {expandedModules[role]?.[module] && (
                                <div className="p-2 border-t bg-muted/5">
                                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">
                                    {modulePermissions.map((permission, pIndex) => {
                                      // Resaltar texto de búsqueda si hay una búsqueda activa
                                      const displayText = searchQuery 
                                        ? highlightSearchText(permission.nameshow, searchQuery)
                                        : permission.nameshow;
                                        
                                      return (
                                        <div 
                                          key={pIndex} 
                                          className="flex items-center px-2 py-1.5 text-xs border border-gray-200 rounded-md bg-background hover:bg-muted/10 transition-colors"
                                          title={permission.name}
                                        >
                                          <div className="truncate">{displayText}</div>
                                        </div>
                                      );
                                    })}
                                  </div>
                                </div>
                              )}
                            </div>
                          ))}
                        </div>
                      ) : (
                        <p className="text-center text-sm text-muted-foreground py-2">
                          {searchQuery ? 'No se encontraron permisos' : 'Este rol no tiene permisos'}
                        </p>
                      )}
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        ) : (
          <div className="text-center p-6 bg-muted/5 border rounded-md">
            <Shield className="w-10 h-10 text-muted-foreground mx-auto mb-2 opacity-40" />
            <p className="text-muted-foreground">No hay roles asignados a este usuario</p>
          </div>
        )}
      </div>
    </div>
  );
};

// Función para resaltar texto de búsqueda
function highlightSearchText(text: string, query: string): React.ReactNode {
  if (!query.trim()) return text;
  
  const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
  const parts = text.split(regex);
  
  return (
    <>
      {parts.map((part, i) => 
        regex.test(part) ? 
          <span key={i} className="bg-yellow-100 text-yellow-800">{part}</span> : 
          part
      )}
    </>
  );
}
