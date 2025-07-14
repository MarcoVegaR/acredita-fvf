import React from "react";
import { BaseIndexPage } from "@/components/base-index/base-index-page";
import { columns, type Area } from "./columns";

// Define the props interface para el índice de áreas
interface AreasIndexProps {
  areas: {
    data: Area[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
  stats: {
    total: number;
    active: number;
    inactive: number;
    deleted?: number;
  };
  filters?: {
    search?: string;
    active?: string;
    code?: string;
    sort?: string;
    order?: "asc" | "desc";
    page?: number;
    per_page?: number;
  };
}

export default function Index({ areas, stats, filters = {} }: AreasIndexProps) {
  // Configuración centralizada para el índice de áreas
  const indexOptions = {
    // Información principal
    title: "Gestión de Áreas",
    subtitle: "Administra las áreas y gerencias de la organización",
    endpoint: "/areas",
    
    // Configuración de filtros personalizados
    filterConfig: {
      boolean: [
        {
          id: "active",
          label: "Estado",
          trueLabel: "Activas",
          falseLabel: "Inactivas"
        }
      ],
      text: [
        {
          id: "code",
          label: "Código",
          placeholder: "Filtrar por código..."
        }
      ]
    },
    filterEmptyMessage: "Sin filtros aplicados. Utilice el botón 'Filtrar' para refinar los resultados.",
    
    // Configuración de permisos Spatie
    permissions: {
      view: "areas.show",
      create: "areas.create",
      edit: "areas.edit",
      delete: "areas.delete"
    },
    
    // Estadísticas para mostrar en las tarjetas
    stats: [
      { 
        value: stats?.total || 0, 
        label: "Total de áreas",
        icon: "building",
        color: "text-blue-500"
      },
      { 
        value: stats?.active || 0, 
        label: "Áreas activas",
        icon: "check-circle",
        color: "text-green-500"
      },
      { 
        value: stats?.inactive || 0, 
        label: "Áreas inactivas",
        icon: "x-circle",
        color: "text-red-500"
      },
      { 
        value: stats?.deleted || 0, 
        label: "Áreas eliminadas",
        icon: "trash-2",
        color: "text-gray-500"
      }
    ],
    
    // Configuración de traducciones y búsqueda
    moduleName: "areas",
    searchableColumns: ["code", "name", "description"],
    searchPlaceholder: "Buscar por código, nombre o descripción...",
    breadcrumbs: [
      {
        title: "Dashboard",
        href: "/dashboard",
      },
      {
        title: "Áreas",
        href: "/areas",
      },
    ],
    columns: columns,
    filterableColumns: ["code", "name", "active"],
    defaultSorting: [{ id: "id", desc: false }],
    exportOptions: {
      enabled: true,
      fileName: "areas",
      exportTypes: ["excel", "csv", "print", "copy"] as ("excel" | "csv" | "print" | "copy")[],
    },
    newButton: {
      show: true,
      label: "Nueva Área",
      permission: "areas.create",
    },
    rowActions: {
      view: {
        enabled: true,
        label: "Ver detalles",
        permission: "areas.show",
      },
      edit: {
        enabled: true,
        label: "Editar",
        permission: "areas.edit",
      },
      delete: {
        enabled: true,
        label: "Eliminar",
        permission: "areas.delete",
        confirmMessage: (area: Area) => `¿Está seguro que desea eliminar el área ${area.name}?`,
      },
    },
  };

  // Usar el componente base con la configuración específica
  return (
    <BaseIndexPage<Area> 
      data={areas} 
      filters={filters} 
      options={indexOptions} 
    />
  );
}
