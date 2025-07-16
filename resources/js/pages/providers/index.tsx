import React from "react";
import { BaseIndexPage } from "@/components/base-index/base-index-page";
import { columns, type Provider } from "./columns";
import { FileTextIcon, ImageIcon } from "lucide-react";
import { router } from "@inertiajs/react";

// Define the props interface
interface ProvidersIndexProps {
  providers: {
    data: Provider[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
  stats: {
    total: number;
    active: number;
    internal: number;
    external: number;
  };
  areas: { id: number; name: string }[];
  filters?: {
    search?: string;
    area_id?: number;
    type?: string;
    active?: boolean;
    sort?: string;
    order?: "asc" | "desc";
    page?: number;
    per_page?: number;
  };
}

export default function Index({ providers, stats, areas, filters = {} }: ProvidersIndexProps) {
  // Configuración centralizada para el índice de proveedores
  const indexOptions = {
    // Información principal
    title: "Gestión de Proveedores",
    subtitle: "Administra los proveedores internos y externos del sistema",
    endpoint: "/providers",
    
    // Configuración de filtros personalizados
    filterConfig: {
      boolean: [
        {
          id: "active",
          label: "Estado",
          trueLabel: "Solo activos",
          falseLabel: "Todos"
        }
      ],
      select: [
        {
          id: "type",
          label: "Tipo",
          options: [
            { value: "all", label: "Todos" },
            { value: "internal", label: "Interno" },
            { value: "external", label: "Externo" }
          ]
        },
        {
          id: "area_id",
          label: "Área",
          options: [
            { value: "all", label: "Todas" },
            ...areas.map(area => ({
              value: area.id.toString(),
              label: area.name
            }))
          ]
        }
      ]
    },
    filterEmptyMessage: "Sin filtros aplicados. Utilice los controles para refinar los resultados.",
    
    // Configuración de permisos Spatie
    permissions: {
      view: "provider.view",
      create: "provider.manage",
      edit: "provider.manage_own_area",
      delete: "provider.manage"
    },
    
    // Estadísticas para mostrar en las tarjetas
    stats: [
      { 
        value: stats?.total || 0, 
        label: "Total de proveedores",
        icon: "truck",
        color: "text-blue-500"
      },
      { 
        value: stats?.active || 0, 
        label: "Proveedores activos",
        icon: "activity",
        color: "text-green-500"
      },
      { 
        value: stats?.internal || 0, 
        label: "Proveedores internos",
        icon: "building",
        color: "text-indigo-500"
      },
      { 
        value: stats?.external || 0, 
        label: "Proveedores externos",
        icon: "briefcase",
        color: "text-purple-500"
      }
    ],
    
    // Configuración de traducciones y búsqueda
    moduleName: "providers",
    searchableColumns: ["name", "rif", "user.email"],
    searchPlaceholder: "Buscar por nombre, RIF o email...",
    breadcrumbs: [
      {
        title: "Dashboard",
        href: "/dashboard",
      },
      {
        title: "Proveedores",
        href: "/providers",
      },
    ],
    columns: columns,
    filterableColumns: ["name", "rif", "user.email", "area.name"],
    defaultSorting: [{ id: "created_at", desc: true }],
    exportOptions: {
      enabled: true,
      fileName: "proveedores",
      exportTypes: ["excel", "csv", "print", "copy"] as ("excel" | "csv" | "print" | "copy")[],
    },
    newButton: {
      show: true,
      label: "Nuevo Proveedor",
      permission: "provider.manage",
    },
    rowActions: {
      view: {
        enabled: true,
        label: "Ver detalles",
        permission: "provider.view",
        handler: (row: Provider) => {
          router.get(`/providers/${row.uuid}`);
        },
      },
      edit: {
        enabled: true,
        label: "Editar",
        permission: "provider.manage_own_area",
        handler: (row: Provider) => {
          router.get(`/providers/${row.uuid}/edit`);
        },
      },
      delete: {
        enabled: false, // Usamos toggle active en lugar de eliminar
        label: "Eliminar",
        permission: "provider.manage",
        confirmMessage: (provider: Provider) => `¿Está seguro que desea eliminar el proveedor ${provider.name}?`
      },
      custom: [
        {
          label: "Activar/Desactivar",
          icon: <FileTextIcon className="h-4 w-4" />,
          handler: (provider: Provider) => {
            // Usar router de Inertia para enviar la petición
            import("@inertiajs/react").then(({ router }) => {
              router.patch(`/providers/${provider.uuid}/toggle-active`, {
                active: !provider.active
              });
            });
          },
          permission: "provider.manage_own_area", // Usando el permiso menos restrictivo
          // Mostrar confirmación
          confirmMessage: (provider: Provider) => 
            provider.active 
              ? `¿Está seguro que desea desactivar el proveedor ${provider.name}?`
              : `¿Está seguro que desea activar el proveedor ${provider.name}?`,
        },
        {
          label: "Restablecer contraseña",
          icon: <ImageIcon className="h-4 w-4" />,
          handler: (provider: Provider) => {
            // Solo disponible para proveedores externos
            if (provider.type !== "external") {
              alert("Esta acción solo está disponible para proveedores externos");
              return;
            }
            
            // Usar router de Inertia para enviar la petición
            import("@inertiajs/react").then(({ router }) => {
              router.post(`/providers/${provider.uuid}/reset-password`);
            });
          },
          permission: "provider.manage_own_area", // Usando el permiso menos restrictivo
          // Solo mostrar para proveedores externos
          visible: (provider: Provider) => provider.type === "external",
          // Mostrar confirmación
          confirmMessage: (provider: Provider) => 
            `¿Está seguro que desea restablecer la contraseña del proveedor ${provider.name}? Se enviará un correo con la nueva contraseña.`,
        },
      ],
    },
  };

  // Usar el componente base con la configuración específica
  return (
    <BaseIndexPage<Provider> 
      data={providers} 
      filters={filters} 
      options={indexOptions} 
    />
  );
}
