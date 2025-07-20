import React from "react";
import { router } from "@inertiajs/react";
import { BaseIndexPage, BaseIndexOptions } from "@/components/base-index/base-index-page";
import { RefreshCw } from "lucide-react";
import { columns } from "./columns";
import { Template } from "./schema";
import { TableTemplate } from "./types";

// Define la interfaz de props para la vista de listado
interface TemplatesIndexProps {
  templates: {
    data: Template[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
  stats: {
    total: number;
    default: number;
  };
  events: Array<{
    id: number;
    name: string;
  }>;
  filters?: {
    search?: string;
    sort?: string;
    order?: "asc" | "desc";
    page?: number;
    per_page?: number;
    event_id?: number;
  };
}

export default function Index({ templates, stats, events, filters = {} }: TemplatesIndexProps) {
  // Configuración centralizada para el índice de plantillas
  const indexOptions: BaseIndexOptions<TableTemplate> = {
    // Información principal
    title: "Gestión de Plantillas",
    subtitle: "Administra las plantillas de credenciales para eventos",
    endpoint: "/templates",
    // Requerido por la interfaz BaseIndexOptions
    breadcrumbs: [
      { title: "Dashboard", href: "/dashboard" },
      { title: "Plantillas", href: "/templates" }
    ],
    
    // Columnas requeridas por la interfaz
    columns: columns,
    
    // Configuración de filtros personalizados
    filterConfig: {
      select: [
        {
          id: "event_id",
          label: "Evento",
          options: [
            { value: "all", label: "Todos los eventos" },
            ...events.map(event => ({
              value: event.id.toString(),
              label: event.name
            }))
          ]
        }
      ]
    },
    filterEmptyMessage: "Sin filtros aplicados. Utilice el botón 'Filtrar' para refinar los resultados.",
    
    // Configuración de permisos Spatie
    permissions: {
      view: "templates.show",
      create: "templates.create",
      edit: "templates.edit",
      delete: "templates.delete"
    },
    
    // Estadísticas para mostrar en las tarjetas
    stats: [
      { 
        value: stats?.total || 0, 
        label: "Total de plantillas",
        icon: "FileImage"
      },
      { 
        value: stats?.default || 0, 
        label: "Plantillas predeterminadas",
        icon: "Star"
      }
    ],
    
    // Texto para búsqueda
    searchPlaceholder: "Buscar por nombre de plantilla...",
    
    // Configuración de exportación
    exportOptions: {
      enabled: true,
      fileName: "plantillas-credenciales"
    },
    
    // Configuración de acciones adicionales
    newButton: {
      show: true,
      label: "Nueva plantilla"
    },
    
    // Configuración de acciones de fila
    rowActions: {
      view: {
        enabled: true,
        label: "Ver detalles",
        permission: "templates.show",
      },
      edit: {
        enabled: true,
        label: "Editar",
        permission: "templates.edit",
      },
      delete: {
        enabled: true,
        label: "Eliminar",
        permission: "templates.delete",
        confirmMessage: (template: Template) => `¿Está seguro que desea eliminar la plantilla "${template.name}"?`,
      },
      custom: [
        {
          label: "Regenerar credenciales",
          icon: <RefreshCw className="h-4 w-4" />,
          permission: "credentials.regenerate",
          showCondition: (template: TableTemplate) => !!template.event,
          confirmMessage: (template: TableTemplate) => 
            `¿Está seguro de regenerar todas las credenciales del evento "${template.event?.name}" usando esta plantilla?\n\nEsto actualizará todas las credenciales existentes con el nuevo diseño y puede tomar varios minutos.`,
          confirmTitle: "Regenerar credenciales",
          handler: (template: TableTemplate) => {
            router.post(`/templates/${template.uuid}/regenerate-credentials`, {}, {
              onSuccess: () => {
                // El mensaje de éxito se muestra automáticamente desde el backend
              }
            });
          }
        }
      ]
    }
  };

  // Asegurar que todos los templates tengan un id no nulo (requerido por Entity)
  const safeTemplates = {
    ...templates,
    data: templates.data.map(template => ({
      ...template,
      id: template.id || 0, // Asegura que id sea número
    })) as TableTemplate[]
  };

  return (
    <BaseIndexPage
      data={safeTemplates}
      options={indexOptions}
      filters={filters}
    />
  );
}
