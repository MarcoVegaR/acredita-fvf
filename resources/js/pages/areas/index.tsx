import React, { useState } from "react";
import { BaseIndexPage } from "@/components/base-index/base-index-page";
import { columns, type Area } from "./columns";
import { AssignManagerDialog } from "./assign-manager-dialog";
import { RemoveManagerAlert } from "./remove-manager-alert";
import { CircleUser as CircleUserIcon, UserMinus } from "lucide-react";
import { router } from "@inertiajs/react";

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
  // Estado para los diálogos de gestión de gerente
  const [showAssignManagerDialog, setShowAssignManagerDialog] = useState<boolean>(false);
  const [showRemoveManagerDialog, setShowRemoveManagerDialog] = useState<boolean>(false);
  const [currentArea, setCurrentArea] = useState<Area | null>(null);
  
  // Ya no necesitamos referencias para gestionar el foco
  // La gestión de foco ahora se maneja en los componentes de diálogo
  
  // Funciones para abrir los diálogos
  const openAssignManagerDialog = (area: Area) => {
    console.log('[Index] Abriendo diálogo de asignación de gerente');
    // Ya no guardamos el elemento enfocado, la gestión del foco la realizan los diálogos
    
    setCurrentArea(area);
    setShowAssignManagerDialog(true);
  };
  
  const openRemoveManagerDialog = (area: Area) => {
    console.log('[Index] Abriendo diálogo de remoción de gerente', { area });
    // Ya no guardamos el elemento enfocado, la gestión del foco la realizan los diálogos
    
    setCurrentArea(area);
    setShowRemoveManagerDialog(true);
  };
  
  // Funciones para cerrar los diálogos
  const handleAssignDialogClose = () => {
    console.log('[Index] Cerrando diálogo de asignación');
    setShowAssignManagerDialog(false);
  };
  
  const handleRemoveDialogClose = () => {
    console.log('[Index] Cerrando diálogo de remoción');
    setShowRemoveManagerDialog(false);
  };
  
  // Manejadores de éxito de los diálogos
  const handleAssignSuccess = () => {
    console.log('[Index] Éxito en asignación de gerente');
    setShowAssignManagerDialog(false);
    
    // Recargar los datos
    const routeParams = new URLSearchParams(window.location.search).toString();
    const currentPath = window.location.pathname;
    const fullPath = routeParams ? `${currentPath}?${routeParams}` : currentPath;
    
    console.log('[Index] Recargando datos después de asignación exitosa');
    router.visit(fullPath, { preserveState: false });
  };
  
  const handleRemoveSuccess = () => {
    console.log('[Index] Éxito en remoción de gerente');
    setShowRemoveManagerDialog(false);
    
    // Recargar los datos
    const routeParams = new URLSearchParams(window.location.search).toString();
    const currentPath = window.location.pathname;
    const fullPath = routeParams ? `${currentPath}?${routeParams}` : currentPath;
    
    console.log('[Index] Recargando datos después de remoción exitosa');
    router.visit(fullPath, { preserveState: false });
  };
  
  // Eliminamos el efecto para restaurar el foco cuando los diálogos se cierran
  // Ahora la gestión del foco se realiza completamente dentro de los componentes de diálogo
  
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
      custom: [
        {
          label: "Asignar gerente",
          icon: <CircleUserIcon className="h-4 w-4" />,
          handler: (area: Area) => openAssignManagerDialog(area),
          permission: "areas.edit",
          showCondition: (area: Area) => !area.manager_user_id
        },
        {
          label: "Quitar gerente",
          icon: <UserMinus className="h-4 w-4" />,
          handler: (area: Area) => openRemoveManagerDialog(area),
          permission: "areas.edit",
          showCondition: (area: Area) => !!area.manager_user_id
        }
      ],
    },
  };

  // Usar el componente base con la configuración específica
  return (
    <>
      <BaseIndexPage<Area> 
        data={areas} 
        filters={filters} 
        options={indexOptions} 
      />
      
      {/* Diálogo para asignar gerente */}
      {showAssignManagerDialog && currentArea && (
        <AssignManagerDialog 
          area={currentArea} 
          isOpen={showAssignManagerDialog}
          onClose={handleAssignDialogClose}
          onSuccess={handleAssignSuccess}
        />
      )}

      {/* Diálogo para quitar gerente */}
      {showRemoveManagerDialog && currentArea && (
        <RemoveManagerAlert
          area={currentArea}
          isOpen={showRemoveManagerDialog}
          onClose={handleRemoveDialogClose}
          onSuccess={handleRemoveSuccess}
        />
      )}
    </>
  );
}
