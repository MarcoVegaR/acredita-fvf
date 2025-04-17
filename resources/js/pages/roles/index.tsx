import React from "react";
import { BaseIndexPage } from "@/components/base-index/base-index-page";
import { columns, type Role } from "./columns";
import { SharedData } from "@/types"; 

// Inline type for Stats based on BaseIndexOptions
type StatData = {
  value: number | string;
  label: string;
  icon?: string;
  color?: string;
};

// Define props structure using SharedData
interface RolesIndexProps extends SharedData { 
  roles: { 
    data: Role[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
  stats: StatData[]; 
  filters?: { 
    search?: string;
    sort?: string;
    order?: "asc" | "desc";
    page?: number;
    per_page?: number;
  };
}

// Componente principal para el índice de roles
export default function Index({ roles, stats = [], filters = {} }: RolesIndexProps) {
  // Define the export types array with explicit typing
  const allowedExportTypes: ("excel" | "csv" | "print" | "copy")[] = ["excel", "csv", "print", "copy"];

  // Configuration for the Roles Index page
  const indexOptions = {
    title: "Gestión de Roles",
    subtitle: "Administre los roles y sus permisos asociados.",
    endpoint: "/roles", 
    moduleName: "roles", 
    breadcrumbs: [
      { title: "Dashboard", href: route("dashboard") },
      { title: "Roles", href: route("roles.index") },
    ],
    stats: stats, 
    columns: columns,
    searchableColumns: ["name"],
    searchPlaceholder: "Buscar por nombre de rol...",
    filterableColumns: ["name"],
    
    // Configuración de filtros personalizados - simplificado para incluir solo módulo de permisos
    filterConfig: {
      boolean: [],
      select: [
        {
          id: "permission_module",
          label: "Módulo de permisos",
          options: [
            { value: "users", label: "Usuarios" },
            { value: "roles", label: "Roles" }
          ]
        }
      ]
    },
    filterEmptyMessage: "Sin filtros aplicados. Utilice el botón 'Filtrar' para refinar los resultados.",
    defaultSorting: [{ id: "id", desc: true }] as { id: string; desc: boolean }[], 
    exportOptions: {
      enabled: true, 
      fileName: "roles", 
      exportTypes: allowedExportTypes, 
    },
    newButton: {
      show: true, 
      label: "Nuevo Rol", 
      permission: "roles.create", 
    },
    permissions: { 
      create: "roles.create",
      view: "roles.show", 
      edit: "roles.edit",
      delete: "roles.delete",
    },
    rowActions: { 
      view: {
        enabled: true, 
        label: "Ver Detalles",
        permission: "roles.show",
      },
      edit: {
        enabled: true,
        label: "Editar Rol",
        permission: "roles.edit", 
         // Custom logic to disable edit for 'admin' role
        isDisabled: (role: Role) => role.name === 'admin',
      },
      delete: {
        enabled: true,
        label: "Eliminar Rol",
        permission: "roles.delete", 
        // Custom logic to disable delete for 'admin' role
        isDisabled: (role: Role) => role.name === 'admin',
        confirmMessage: (role: Role) => 
            `¿Está seguro que desea eliminar el rol "${role.name}"? Esta acción no se puede deshacer.`,
      },
    },
  };

  return (
    <BaseIndexPage<Role>
      data={roles}
      filters={filters}
      options={indexOptions}
    />
  );
}
