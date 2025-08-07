import React from "react";
import { BaseIndexPage } from "@/components/base-index/base-index-page";
import { columns, type Employee } from "./columns";
import { IdCardIcon } from "lucide-react";

// Define the props interface - adaptada para usar con BaseIndexPage
interface EmployeesIndexProps {
  employees: {
    data: Employee[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
  stats: {
    total: number;
    active: number;
    inactive: number;
  };
  filters?: {
    search?: string;
    sort?: string;
    order?: "asc" | "desc";
    page?: number;
    per_page?: number;
    provider_id?: number;
  };
  // Para los roles area_manager y provider
  currentUserRole?: string;
  isProvider?: boolean;
}

export default function Index({ employees, stats, filters = {}, currentUserRole, isProvider }: EmployeesIndexProps) {
  // Añadimos logs para diagnosticar el problema
  console.log('[EMPLOYEES INDEX] Inicializando componente index');
  console.log('[EMPLOYEES INDEX] currentUserRole:', currentUserRole);
  console.log('[EMPLOYEES INDEX] isProvider:', isProvider);
  
  // Configuración centralizada para el índice de colaboradores
  const indexOptions = {
    // Información principal
    title: isProvider ? "Mis Colaboradores" : "Colaboradores de Proveedores",
    subtitle: isProvider 
      ? "Administra los colaboradores de tu organización" 
      : currentUserRole === "area_manager" 
        ? "Administra los colaboradores de los proveedores de tu área" 
        : "Administra los colaboradores de todos los proveedores",
    endpoint: "/employees",
    
    // Configuración de filtros personalizados
    filterConfig: {
      boolean: [
        {
          id: "active",
          label: "Estado",
          trueLabel: "Activos",
          falseLabel: "Inactivos"
        }
      ],
      select: !isProvider ? [
        {
          id: "provider_id",
          label: "Proveedor",
          options: [] // Los options se cargarán dinámicamente desde el backend
        }
      ] : []
    },
    filterEmptyMessage: "Sin filtros aplicados. Utilice el botón 'Filtrar' para refinar los resultados.",
    
    // Configuración de permisos Spatie
    permissions: {
      view: "employee.view",
      create: isProvider || currentUserRole === "area_manager" ? "employee.manage_own_provider" : "employee.manage",
      edit: isProvider || currentUserRole === "area_manager" ? "employee.manage_own_provider" : "employee.manage",
      delete: isProvider || currentUserRole === "area_manager" ? "employee.manage_own_provider" : "employee.manage"
    },
    
    // Estadísticas para mostrar en las tarjetas
    stats: [
      { 
        value: stats?.total || 0, 
        label: "Total de colaboradores",
        icon: "users",
        color: "text-blue-500"
      },
      { 
        value: stats?.active || 0, 
        label: "Colaboradores activos",
        icon: "user-check",
        color: "text-green-500"
      },
      { 
        value: stats?.inactive || 0, 
        label: "Colaboradores inactivos",
        icon: "user-x",
        color: "text-red-500"
      }
    ],
    
    // Configuración de traducciones y búsqueda
    moduleName: "employees",
    searchableColumns: ["first_name", "last_name", "document_number", "function"],
    searchPlaceholder: "Buscar por nombre, apellido, documento o función...",
    breadcrumbs: [
      {
        title: "Dashboard",
        href: "/dashboard",
      },
      {
        title: isProvider ? "Mis Colaboradores" : "Colaboradores",
        href: "/employees",
      },
    ],
    columns: columns,
    filterableColumns: ["first_name", "last_name", "document_number", "function", "active", "provider_id"],
    defaultSorting: [{ id: "id", desc: true }],
    exportOptions: {
      enabled: true,
      fileName: isProvider ? "mis-colaboradores" : "colaboradores-proveedores",
      exportTypes: ["excel", "csv", "print", "copy"] as ("excel" | "csv" | "print" | "copy")[],
    },
    newButton: {
      show: true,
      label: "Nuevo Colaborador",
      permission: isProvider || currentUserRole === "area_manager" ? "employee.manage_own_provider" : "employee.manage",
    },
    rowActions: {
      view: {
        enabled: true,
        label: "Ver detalles",
        permission: "employee.view",
        handler: (employee: Employee) => {
          import("@inertiajs/react").then(({ router }) => {
            router.get(`/employees/${employee.uuid}`);
          });
        },
      },
      edit: {
        enabled: true,
        label: "Editar",
        permission: isProvider || currentUserRole === "area_manager" ? "employee.manage_own_provider" : "employee.manage",
        handler: (employee: Employee) => {
          import("@inertiajs/react").then(({ router }) => {
            router.get(`/employees/${employee.uuid}/edit`);
          });
        },
      },
      delete: {
        enabled: false,  // Usar toggle active en lugar de eliminar
        label: "Eliminar",  // Required by TypeScript even when disabled
        permission: isProvider || currentUserRole === "area_manager" ? "employee.manage_own_provider" : "employee.manage",
      },
      // Acción personalizada para activar/desactivar empleado
      custom: [
        {
          label: "Cambiar estado", // Texto estático ya que TypeScript espera un string, no una función
          icon: <IdCardIcon className="h-4 w-4" />,
          handler: (employee: Employee) => {
            // Usar router de Inertia para toggle active
            import("@inertiajs/react").then(({ router }) => {
              router.patch(`/employees/${employee.uuid}/toggle-active`, {}, {
                onSuccess: () => {
                  // El flash message se maneja automáticamente en el backend
                }
              });
            });
          },
          permission: isProvider || currentUserRole === "area_manager" ? "employee.manage_own_provider" : "employee.manage",
          // Usamos showCondition para determinar qué mostrar en lugar de funciones dinámicas para label/icon
          showCondition: () => true, // Siempre visible
        },
      ],
    },
  };

  // Usar el componente base con la configuración específica
  return (
    <BaseIndexPage<Employee> 
      data={employees} 
      filters={filters} 
      options={indexOptions} 
    />
  );
}
