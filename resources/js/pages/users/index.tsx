import React from "react";
import { BaseIndexPage } from "@/components/base-index/base-index-page";
import { columns, type User } from "./columns";

// Define the props interface - adaptada para usar con BaseIndexPage
interface UsersIndexProps {
  users: {
    data: User[];
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
}

export default function Index({ users, filters = {} }: UsersIndexProps) {
  // Configuración centralizada para el índice de usuarios
  const indexOptions = {
    // Información principal
    title: "Gestión de Usuarios",
    subtitle: "Administra los usuarios y permisos del sistema",
    endpoint: "/users",
    
    // Configuración de permisos Spatie
    permissions: {
      view: "users.show",
      create: "users.create",
      edit: "users.edit",
      delete: "users.delete"
    },
    
    // Estadísticas para mostrar en las tarjetas (array con múltiples tarjetas)
    stats: [
      { 
        value: users?.total || 0, 
        label: "Total de usuarios",
        icon: "users",
        color: "text-blue-500"
      },
      { 
        value: Math.floor((users?.total || 0) * 0.8), 
        label: "Usuarios activos",
        icon: "activity",
        color: "text-green-500"
      },
      { 
        value: Math.floor((users?.total || 0) * 0.15), 
        label: "Últimos 7 días",
        icon: "calendar",
        color: "text-amber-500"
      }
    ],
    
    // Configuración de traducciones y búsqueda
    moduleName: "users",
    searchableColumns: ["name", "email"],
    searchPlaceholder: "Buscar por nombre o correo...",
    breadcrumbs: [
      {
        title: "Dashboard",
        href: "/dashboard",
      },
      {
        title: "Usuarios",
        href: "/users",
      },
    ],
    columns: columns,
    filterableColumns: ["name", "email"],
    defaultSorting: [{ id: "id", desc: true }],
    exportOptions: {
      enabled: true,
      fileName: "usuarios",
      exportTypes: ["excel", "csv", "print", "copy"] as ("excel" | "csv" | "print" | "copy")[],
    },
    newButton: {
      show: true,
      label: "Nuevo Usuario",
      permission: "users.create",  // Permiso específico para este botón
    },
    rowActions: {
      view: {
        enabled: true,
        label: "Ver detalles",
        permission: "users.show",  // Permiso específico para esta acción
      },
      edit: {
        enabled: true,
        label: "Editar",
        permission: "users.edit",  // Permiso específico para esta acción
      },
      delete: {
        enabled: true,
        label: "Eliminar",
        permission: "users.delete",  // Permiso específico para esta acción
        confirmMessage: (user: User) => `¿Está seguro que desea eliminar al usuario ${user.name}?`,
      },
    },
  };

  // Usar el componente base con la configuración específica
  // Especificamos explícitamente el tipo genérico User
  return (
    <BaseIndexPage<User> 
      data={users} 
      filters={filters} 
      options={indexOptions} 
    />
  );
}
