import React, { useState, useMemo, useEffect } from "react";
import { Link } from "@inertiajs/react";
import { BaseShowPage, TabConfig, Entity } from "@/components/base-show/base-show-page";
import { Role } from "./schema";
import { ShieldIcon, UserIcon, ClockIcon, KeyIcon, SearchIcon, ChevronDown, ChevronRight, CheckIcon } from "lucide-react";
import { showPageLabels } from "@/utils/translations/column-labels";
import { getRoleLabel, formatPermissionName, getModuleLabel } from "@/utils/translations/role-labels";
import { isProtectedRole } from "./utils";

// Importar los componentes de renderizado reutilizables
import { DateRenderer } from "@/components/base-show/renderers/date-renderer";
import { StatusRenderer } from "@/components/base-show/renderers/status-renderer";
import { Badge } from "@/components/ui/badge";
import { Input } from "@/components/ui/input";
import { ScrollArea } from "@/components/ui/scroll-area";
import { Card } from "@/components/ui/card";
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from "@/components/ui/accordion";
// Para la paginación usamos componentes de shadcn/ui
import { 
  Pagination, 
  PaginationContent, 
  PaginationEllipsis, 
  PaginationItem,
  PaginationLink, 
  PaginationNext, 
  PaginationPrevious 
} from "@/components/ui/pagination";

// Tipo para la entidad extendida de role con propiedades adicionales
interface ExtendedRole extends Entity {
  name: string;
  guard_name: string;
  permissions?: string[];
  permissions_count?: number;
  created_at?: string;
  updated_at?: string;
  protected: boolean;
  users_data: {
    id: number;
    name: string;
    email: string;
  }[];
  users_count: number;
}

interface RoleDetailsProps {
  role: Role;
  rolePermissions: string[];
  usersWithRole?: { 
    id: number;
    name: string;
    email: string;
  }[];
}

// Función para agrupar permisos en formato string por categoría
function groupPermissionStringsByModule(permissions: string[]): Record<string, string[]> {
  const result: Record<string, string[]> = {};
  
  permissions.forEach(permission => {
    const parts = permission.split('.');
    const module = parts[0]; // Extraer el módulo (primera parte del permiso)
    
    if (!result[module]) {
      result[module] = [];
    }
    
    result[module].push(permission);
  });
  
  return result;
}

// Componente que muestra los permisos con acordeón y búsqueda
function RolePermissionsPanel({ permissions }: { permissions: string[] }) {
  const [searchQuery, setSearchQuery] = useState("");
  const [expandedCategories, setExpandedCategories] = useState<string[]>([]);

  // Agrupar permisos por módulo
  const groupedPermissions = useMemo(() => {
    return groupPermissionStringsByModule(permissions);
  }, [permissions]);

  // Filtrar permisos por búsqueda
  const filteredModulesWithPermissions = useMemo(() => {
    if (!searchQuery.trim()) {
      return Object.entries(groupedPermissions);
    }

    const query = searchQuery.toLowerCase();
    const filtered: [string, string[]][] = [];

    Object.entries(groupedPermissions).forEach(([module, modulePermissions]) => {
      const matchingPermissions = modulePermissions.filter((permission: string) => {
        const parts = permission.split('.');
        const permModule = parts[0];
        const action = parts.length > 1 ? parts.slice(1).join('.') : permission;
        const formattedPermission = formatPermissionName(permission);
        
        return (
          permModule.toLowerCase().includes(query) || 
          action.toLowerCase().includes(query) ||
          formattedPermission.toLowerCase().includes(query)
        );
      });

      if (matchingPermissions.length > 0) {
        filtered.push([module, matchingPermissions]);
      }
    });

    return filtered;
  }, [groupedPermissions, searchQuery]);

  // Expandir todos los módulos cuando hay búsqueda
  useEffect(() => {
    if (searchQuery.trim()) {
      const modulesToExpand = filteredModulesWithPermissions.map(([module]) => module);
      setExpandedCategories(modulesToExpand);
    }
  }, [searchQuery, filteredModulesWithPermissions]);

  // Controlar el colapso/expansión del acordeón
  const handleAccordionChange = (categoryId: string) => {
    setExpandedCategories(prev => 
      prev.includes(categoryId) 
        ? prev.filter(id => id !== categoryId)
        : [...prev, categoryId]
    );
  };

  if (permissions.length === 0) {
    return (
      <div className="text-center py-6 bg-muted/20 rounded-md mt-4">
        <p className="text-muted-foreground">Este rol no tiene permisos asignados.</p>
      </div>
    );
  }

  return (
    <div className="space-y-4 mt-4">
      <div className="relative">
        <SearchIcon className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input
          type="search"
          placeholder="Buscar permisos..."
          className="pl-9 w-full bg-background"
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
        />
      </div>

      <ScrollArea className="h-[460px] pr-4">
        <Accordion 
          type="multiple" 
          value={expandedCategories}
          className="space-y-3"
        >
          {filteredModulesWithPermissions.map(([moduleId, modulePermissions]) => {
            // Obtener el nombre traducido del módulo
            const moduleName = getModuleLabel(moduleId);
            const isExpanded = expandedCategories.includes(moduleId);
            
            // Obtener un ícono apropiado para el módulo
            let moduleIcon = <ShieldIcon className="h-4 w-4" />;
            if (moduleId === 'users') moduleIcon = <UserIcon className="h-4 w-4" />;
            if (moduleId === 'roles') moduleIcon = <ShieldIcon className="h-4 w-4" />;

            return (
              <Card key={moduleId} className="border border-border/60">
                <AccordionItem value={moduleId} className="border-0">
                  <AccordionTrigger 
                    onClick={() => handleAccordionChange(moduleId)}
                    className="px-4 py-3 hover:no-underline hover:bg-muted/20 rounded-t-md group"
                  >
                    <div className="flex items-center gap-2 text-left">
                      <div className="flex-shrink-0 text-primary w-5 h-5">
                        {moduleIcon}
                      </div>
                      <div className="flex-1">
                        <div className="font-medium capitalize">{moduleName}</div>
                        <p className="text-xs text-muted-foreground">Módulo: {moduleId}</p>
                      </div>
                      <Badge variant="outline" className="ml-2">
                        {modulePermissions.length}
                      </Badge>
                      {isExpanded ? (
                        <ChevronDown className="h-4 w-4 text-muted-foreground transition-transform group-data-[state=open]:rotate-180" />
                      ) : (
                        <ChevronRight className="h-4 w-4 text-muted-foreground transition-transform" />
                      )}
                    </div>
                  </AccordionTrigger>
                  <AccordionContent className="pt-0 px-0">
                    <div className="border-t border-border/30 px-4 pt-2 pb-2">
                      <ul className="space-y-2">
                        {modulePermissions.map((permission: string) => {
                          // Usamos formatPermissionName directamente sin dividir el permiso
                          const formattedPermission = formatPermissionName(permission);

                          return (
                            <li key={permission} className="flex items-center py-1.5 px-1.5 rounded-md hover:bg-muted/30">
                              <div className="flex-shrink-0 mr-2 text-primary">
                                <CheckIcon className="h-4 w-4" />
                              </div>
                              <div className="flex flex-col">
                                <span className="text-sm">{formattedPermission}</span>
                                <span className="text-xs text-muted-foreground capitalize">
                                  {permission}
                                </span>
                              </div>
                            </li>
                          );
                        })}
                      </ul>
                    </div>
                  </AccordionContent>
                </AccordionItem>
              </Card>
            );
          })}
        </Accordion>
      </ScrollArea>
    </div>
  );
}

// Componente para mostrar usuarios con paginación y búsqueda
function RoleUsersList({ users }: { users: Array<{ id: number; name: string; email: string }> }) {
  const [searchQuery, setSearchQuery] = useState("");
  const [currentPage, setCurrentPage] = useState(1);
  const itemsPerPage = 5;

  // Filtrar usuarios por búsqueda
  const filteredUsers = useMemo(() => {
    if (!searchQuery.trim()) return users;
    
    const query = searchQuery.toLowerCase();
    return users.filter(user => 
      user.name.toLowerCase().includes(query) || 
      user.email.toLowerCase().includes(query)
    );
  }, [users, searchQuery]);

  // Calcular la paginación
  const totalPages = Math.max(1, Math.ceil(filteredUsers.length / itemsPerPage));
  const currentUsers = useMemo(() => {
    const startIndex = (currentPage - 1) * itemsPerPage;
    return filteredUsers.slice(startIndex, startIndex + itemsPerPage);
  }, [filteredUsers, currentPage]);

  // Manejar cambio de página
  const handlePageChange = (page: number) => {
    setCurrentPage(page);
  };

  if (users.length === 0) {
    return (
      <div className="text-center py-6 bg-muted/20 rounded-md mt-4">
        <p className="text-muted-foreground">No hay usuarios con este rol asignado.</p>
      </div>
    );
  }

  return (
    <div className="space-y-4 mt-4">
      <div className="relative">
        <SearchIcon className="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
        <Input
          type="search"
          placeholder="Buscar usuario..."
          className="pl-9 w-full bg-background"
          value={searchQuery}
          onChange={(e) => setSearchQuery(e.target.value)}
        />
      </div>

      <div className="relative overflow-x-auto rounded-md border border-border/70">
        <table className="w-full text-sm text-left">
          <thead className="text-xs uppercase bg-muted/40 border-b border-border/70">
            <tr>
              <th scope="col" className="px-4 py-3 font-medium">Nombre</th>
              <th scope="col" className="px-4 py-3 font-medium">Email</th>
              <th scope="col" className="px-4 py-3 font-medium">Acciones</th>
            </tr>
          </thead>
          <tbody className="divide-y">
            {currentUsers.length === 0 ? (
              <tr>
                <td colSpan={3} className="px-4 py-4 text-center text-muted-foreground">
                  No se encontraron usuarios que coincidan con la búsqueda
                </td>
              </tr>
            ) : (
              currentUsers.map((user) => (
                <tr key={user.id} className="bg-card hover:bg-muted/10 border-b border-border/30 last:border-0">
                  <td className="px-4 py-2.5 font-medium">{user.name}</td>
                  <td className="px-4 py-2.5 text-muted-foreground">{user.email}</td>
                  <td className="px-4 py-2.5">
                    <Link 
                      href={`/users/${user.id}`} 
                      className="inline-flex items-center gap-1 text-primary hover:underline"
                    >
                      <span>Ver usuario</span>
                      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
                        <path d="M7 7h10v10"></path>
                        <path d="M7 17 17 7"></path>
                      </svg>
                    </Link>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>

      {totalPages > 1 && (
        <div className="flex justify-center mt-4">
          <Pagination>
            <PaginationContent>
              <PaginationItem>
                <PaginationPrevious 
                  onClick={() => handlePageChange(Math.max(1, currentPage - 1))}
                  className={currentPage === 1 ? "pointer-events-none opacity-50" : "cursor-pointer"}
                />
              </PaginationItem>
              
              {Array.from({ length: totalPages }).map((_, index) => {
                const page = index + 1;
                // Mostrar primero, último, actual y adyacentes
                if (
                  page === 1 ||
                  page === totalPages ||
                  (page >= currentPage - 1 && page <= currentPage + 1)
                ) {
                  return (
                    <PaginationItem key={page}>
                      <PaginationLink 
                        isActive={page === currentPage}
                        size="icon"
                        onClick={() => handlePageChange(page)}
                      >
                        {page}
                      </PaginationLink>
                    </PaginationItem>
                  );
                }
                // Mostrar elipsis para páginas omitidas
                if (
                  (page === 2 && currentPage > 3) ||
                  (page === totalPages - 1 && currentPage < totalPages - 2)
                ) {
                  return (
                    <PaginationItem key={page}>
                      <PaginationEllipsis />
                    </PaginationItem>
                  );
                }
                return null;
              })}
              
              <PaginationItem>
                <PaginationNext 
                  onClick={() => handlePageChange(Math.min(totalPages, currentPage + 1))}
                  className={currentPage === totalPages ? "pointer-events-none opacity-50" : "cursor-pointer"}
                />
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </div>
      )}
    </div>
  );
}

export default function ShowRole({ role, rolePermissions = [], usersWithRole = [] }: RoleDetailsProps) {
  // Crear la entidad extendida con las propiedades adicionales para BaseShowPage
  const extendedRole: ExtendedRole = {
    id: role.id || 0,
    name: role.name,
    guard_name: role.guard_name,
    permissions: rolePermissions,
    permissions_count: rolePermissions.length,
    created_at: role.created_at,
    updated_at: role.updated_at,
    protected: isProtectedRole(role.name),
    users_data: usersWithRole || [], // Asegurar que siempre sea un array
    users_count: (usersWithRole || []).length
  };
  const isProtected = isProtectedRole(role.name);
  
  // Función para renderizar el estado del rol (protegido/normal)
  const renderRoleStatus = () => {
    if (isProtected) {
      return (
        <div className="flex items-center gap-2">
          <Badge variant="secondary" className="text-amber-700 bg-amber-100">
            Rol protegido del sistema
          </Badge>
          <span className="text-sm text-muted-foreground">
            Los roles protegidos no pueden ser modificados
          </span>
        </div>
      );
    }
    return (
      <StatusRenderer 
        value={true} 
        positiveLabel="Activo" 
        type="badge"
      />
    );
  };

  // Configuración de tabs con iconos descriptivos y contadores
  const tabs: TabConfig[] = [
    { 
      value: "general", 
      label: "Información General", 
      icon: <KeyIcon className="h-4 w-4" /> 
    },
    { 
      value: "permissions", 
      label: rolePermissions.length > 0 ? 
        `Permisos (${rolePermissions.length})` : 
        "Permisos", 
      icon: <ShieldIcon className="h-4 w-4" /> 
    },
    { 
      value: "users", 
      label: usersWithRole && usersWithRole.length > 0 ? 
        `Usuarios (${usersWithRole.length})` : 
        "Usuarios", 
      icon: <UserIcon className="h-4 w-4" /> 
    },
    { 
      value: "metadata", 
      label: "Metadatos", 
      icon: <ClockIcon className="h-4 w-4" /> 
    },
  ];

  // Configuración de la página de detalle
  const showOptions = {
    title: role.name,
    subtitle: "Detalle del rol",
    headerContent: (
      <div className="flex items-center gap-6 py-4 px-1">
        <div className="flex-shrink-0">
          <div className="flex items-center justify-center h-16 w-16 rounded-full bg-gradient-to-br from-primary/20 to-primary/30 text-primary shadow-sm border border-primary/10">
            <ShieldIcon className="h-8 w-8" />
          </div>
        </div>
        <div className="flex-1">
          <div className="flex items-center gap-3 mb-1">
            <h2 className="text-2xl font-bold tracking-tight text-foreground">{role.name}</h2>
            {renderRoleStatus()}
          </div>
          <p className="text-muted-foreground flex items-center gap-1.5">
            <span className="inline-flex items-center justify-center rounded-full bg-muted w-5 h-5">
              <KeyIcon className="h-3 w-3 text-muted-foreground" />
            </span>
            <span>Guard: <span className="font-mono text-xs bg-muted px-1.5 py-0.5 rounded">{role.guard_name}</span></span>
          </p>
          <div className="mt-2 flex gap-2 items-center">
            <Badge variant="outline" className="py-1 px-2.5 flex items-center gap-1.5">
              <span className="inline-block h-2 w-2 rounded-full bg-primary/70"></span>
              <span>{rolePermissions.length} permisos asignados</span>
            </Badge>
            {usersWithRole && usersWithRole.length > 0 && (
              <Badge variant="secondary" className="py-1 px-2.5 flex items-center gap-1.5">
                <UserIcon className="h-3 w-3" />
                <span>{usersWithRole.length} usuarios</span>
              </Badge>
            )}
          </div>
        </div>
      </div>
    ),
    breadcrumbs: [
      { title: "Inicio", href: "/" },
      { title: "Roles", href: "/roles" },
      { title: role.name, href: `/roles/${role.id}` },
    ],
    entity: extendedRole,
    moduleName: "roles",
    
    // Configuración de tabs
    tabs,
    defaultTab: "general",
    
    sections: [
      // Tab: Información General
      {
        title: "Información del rol",
        tab: "general",
        className: "bg-card rounded-lg border border-border/80 shadow-sm p-6",
        fields: [
          {
            key: "id",
            label: getRoleLabel("id"),
            render: (value: unknown) => (
              <span className="font-mono text-xs bg-muted px-2 py-1 rounded">{value as number}</span>
            )
          },
          "name",
          "guard_name",
          {
            key: "protected",
            label: "Estado del rol",
            render: () => renderRoleStatus()
          },
        ],
      },
      
      // Tab: Permisos
      {
        title: "Permisos asignados",
        tab: "permissions",
        className: "bg-card rounded-lg border border-border/80 shadow-sm p-6",
        fields: [
          {
            key: "permissions_count",
            label: getRoleLabel("permissions_count"),
            render: (value: unknown) => (
              <Badge variant="outline" className="px-2 py-1">
                {value as number} permisos
              </Badge>
            )
          },
          {
            key: "permissions",
            label: getRoleLabel("permissions"),
            render: (value: unknown) => {
              const perms = value as string[];
              return (
                <RolePermissionsPanel permissions={perms} />
              );
            }
          },
        ],
      },
      
      // Tab: Usuarios
      {
        title: "Usuarios con este rol",
        tab: "users",
        className: "bg-card rounded-lg border border-border/80 shadow-sm p-6",
        fields: [
          {
            key: "users_count",
            label: "Cantidad de usuarios",
            render: (value: unknown) => (
              <Badge variant="outline" className="px-2 py-1">
                {value as number} usuarios
              </Badge>
            )
          },
          {
            key: "users_data",
            label: "Listado de usuarios",
            render: (value: unknown) => {
              const users = value as typeof usersWithRole;
              return <RoleUsersList users={users || []} />;
            }
          },
        ],
      },
      
      // Tab: Metadatos
      {
        title: showPageLabels.sectionTitles.metadata,
        tab: "metadata",
        className: "bg-card rounded-lg border border-border/80 shadow-sm p-6",
        fields: [
          {
            key: "created_at",
            label: getRoleLabel("created_at"),
            render: (value: unknown) => <DateRenderer value={value as string} />
          },
          {
            key: "updated_at",
            label: getRoleLabel("updated_at"),
            render: (value: unknown) => <DateRenderer value={value as string} />
          },
        ],
      },
    ]
  };

  return <BaseShowPage options={showOptions} />;
}
