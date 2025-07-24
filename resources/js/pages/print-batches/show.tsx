import React, { ReactNode } from "react";
import { BaseShowPage, TabConfig, BaseShowOptions } from "@/components/base-show/base-show-page";
import { PrintBatch, Area, Provider } from "./types";
import { 
  CalendarIcon, 
  ClockIcon,
  InfoIcon,
  MapPinIcon,
  BarChart3Icon
} from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { DateRenderer } from "@/components/base-show/renderers/date-renderer";

interface ShowPrintBatchProps {
  batch: PrintBatch;
}

export default function ShowPrintBatch({ batch }: ShowPrintBatchProps) {
  
  // Configuración de estado
  const getStatusInfo = () => {
    switch (batch.status) {
      case 'queued':
        return {
          badge: <Badge variant="secondary" className="bg-blue-50 text-blue-700 border-blue-200">En Cola</Badge>,
          description: 'El lote está esperando ser procesado'
        };
      case 'processing':
        return {
          badge: <Badge variant="secondary" className="bg-yellow-50 text-yellow-700 border-yellow-200">Procesando</Badge>,
          description: 'Generando PDF de credenciales...'
        };
      case 'ready':
        return {
          badge: <Badge variant="secondary" className="bg-green-50 text-green-700 border-green-200">Listo</Badge>,
          description: 'PDF generado exitosamente'
        };
      case 'failed':
        return {
          badge: <Badge variant="destructive">Error</Badge>,
          description: 'Error al generar el PDF'
        };
      default:
        return {
          badge: <Badge variant="secondary">Desconocido</Badge>,
          description: 'Estado no reconocido'
        };
    }
  };

  const statusInfo = getStatusInfo();

  // Configuración de tabs
  const tabs: TabConfig[] = [
    { 
      value: "general", 
      label: "Información General", 
      icon: <InfoIcon className="h-4 w-4" /> 
    },
    { 
      value: "event", 
      label: "Evento", 
      icon: <CalendarIcon className="h-4 w-4" /> 
    },
    { 
      value: "filters", 
      label: "Filtros", 
      icon: <MapPinIcon className="h-4 w-4" /> 
    },
    { 
      value: "statistics", 
      label: "Estadísticas", 
      icon: <BarChart3Icon className="h-4 w-4" /> 
    },
    { 
      value: "metadata", 
      label: "Información del Sistema", 
      icon: <ClockIcon className="h-4 w-4" /> 
    },
  ];

  // Opciones de configuración para BaseShowPage
  const showOptions: BaseShowOptions<PrintBatch> = {
    title: `Lote ${batch.uuid.substring(0, 8)}`,
    subtitle: "Detalles del lote de impresión",
    breadcrumbs: [
      { title: "Dashboard", href: "/dashboard" },
      { title: "Lotes de Impresión", href: "/print-batches" },
      { title: `Lote ${batch.uuid.substring(0, 8)}`, href: "#" }
    ],
    entity: batch,
    moduleName: "print-batches",
    tabs,
    
    // Secciones organizadas por tabs
    sections: [
      
      // Tab: Información General
      {
        title: 'Estado del Lote',
        tab: 'general',
        className: 'bg-card rounded-lg border shadow-sm p-6',
        fields: [
          {
            key: 'uuid' as keyof PrintBatch,
            label: 'UUID Completo',
            render: (): ReactNode => <span className="font-mono text-sm">{batch.uuid}</span>
          },
          {
            key: 'status' as keyof PrintBatch,
            label: 'Estado',
            render: (): ReactNode => (
              <div className="flex flex-col gap-2">
                {statusInfo.badge}
                <span className="text-sm text-gray-600">{statusInfo.description}</span>
              </div>
            )
          },
          {
            key: 'retry_count' as keyof PrintBatch,
            label: 'Intentos de Reintento',
            render: (): ReactNode => (
              <span className={`font-medium ${
                batch.retry_count > 0 ? 'text-orange-600' : 'text-gray-600'
              }`}>
                {batch.retry_count}
              </span>
            )
          }
        ],
      },
      
      // Tab: Evento
      {
        title: 'Detalles del Evento',
        tab: 'event',
        className: 'bg-card rounded-lg border shadow-sm p-6',
        fields: [
          {
            key: 'event' as keyof PrintBatch,
            label: 'Nombre del Evento',
            render: (): ReactNode => <>{batch.event?.name || 'N/A'}</>
          },
          {
            key: 'event' as keyof PrintBatch,
            label: 'Fecha del Evento',
            render: (): ReactNode => (
              batch.event?.date ? <DateRenderer value={batch.event.date} /> : <>N/A</>
            )
          },
          {
            key: 'event' as keyof PrintBatch,
            label: 'Lugar',
            render: (): ReactNode => <>{batch.event?.venue || 'N/A'}</>
          },
          {
            key: 'event' as keyof PrintBatch,
            label: 'Descripción',
            render: (): ReactNode => <>{batch.event?.description || 'Sin descripción'}</>
          }
        ],
      },
      
      // Tab: Filtros Aplicados
      {
        title: 'Configuración del Lote',
        tab: 'filters',
        className: 'bg-card rounded-lg border shadow-sm p-6',
        fields: [
          {
            key: 'area_id' as keyof PrintBatch,
            label: 'Áreas',
            render: (): ReactNode => {
              const areas = (batch.area ? [batch.area] : []) as Area[];
              if (areas.length === 0) {
                return <span className="text-gray-500 italic">Todas las áreas</span>;
              }
              return (
                <div className="flex flex-wrap gap-2">
                  {areas.map((area: Area, index: number) => (
                    <Badge key={index} variant="outline" className="bg-blue-50">
                      {area.name}
                    </Badge>
                  ))}
                </div>
              );
            }
          },
          {
            key: 'provider_id' as keyof PrintBatch,
            label: 'Proveedores',
            render: (): ReactNode => {
              const providers = (batch.provider ? [batch.provider] : []) as Provider[];
              if (providers.length === 0) {
                return <span className="text-gray-500 italic">Todos los proveedores</span>;
              }
              return (
                <div className="flex flex-wrap gap-2">
                  {providers.map((provider: Provider, index: number) => (
                    <Badge key={index} variant="outline" className="bg-green-50">
                      {provider.name}
                    </Badge>
                  ))}
                </div>
              );
            }
          },
          {
            key: 'template_type' as keyof PrintBatch,
            label: 'Tipo de Impresión',
            render: (): ReactNode => (
              <Badge variant="secondary">
                {batch.filters_snapshot?.only_unprinted ? 'Solo no impresas' : 'Todas'}
              </Badge>
            )
          }
        ],
      },
      
      // Tab: Estadísticas
      {
        title: 'Estadísticas del Lote',
        tab: 'statistics',
        className: 'bg-card rounded-lg border shadow-sm p-6',
        fields: [
          {
            key: 'total_credentials' as keyof PrintBatch,
            label: 'Total de Credenciales',
            render: (): ReactNode => (
              <span className="text-2xl font-bold text-blue-600">
                {batch.total_credentials || 0}
              </span>
            )
          },
          {
            key: 'processed_credentials' as keyof PrintBatch,
            label: 'Credenciales Procesadas',
            render: (): ReactNode => (
              <span className="text-2xl font-bold text-green-600">
                {batch.processed_credentials || 0}
              </span>
            )
          },
          {
            key: 'processing_progress' as keyof PrintBatch,
            label: 'Progreso',
            render: (): ReactNode => {
              const progress = batch.total_credentials > 0 
                ? Math.round((batch.processed_credentials / batch.total_credentials) * 100)
                : 0;
              return (
                <div className="flex items-center gap-3">
                  <div className="flex-1 bg-gray-200 rounded-full h-2">
                    <div 
                      className="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                      style={{ width: `${progress}%` }}
                    ></div>
                  </div>
                  <span className="text-sm font-medium">{progress}%</span>
                </div>
              );
            }
          }
        ],
      },
      
      // Tab: Metadatos
      {
        title: 'Información del Sistema',
        tab: 'metadata',
        className: 'bg-card rounded-lg border shadow-sm p-6',
        fields: [
          {
            key: 'generatedBy' as keyof PrintBatch,
            label: 'Generado por',
            render: (): ReactNode => <>{batch.generated_by_user?.name || 'Sistema'}</>
          },
          {
            key: 'created_at' as keyof PrintBatch,
            label: 'Fecha de Creación',
            render: (value: unknown): ReactNode => <DateRenderer value={value as string} />
          },
          {
            key: 'started_at' as keyof PrintBatch,
            label: 'Iniciado',
            render: (value: unknown): ReactNode => (
              value ? <DateRenderer value={value as string} /> : <>No iniciado</>
            )
          },
          {
            key: 'finished_at' as keyof PrintBatch,
            label: 'Finalizado',
            render: (value: unknown): ReactNode => (
              value ? <DateRenderer value={value as string} /> : <>No finalizado</>
            )
          },
          {
            key: 'updated_at' as keyof PrintBatch,
            label: 'Última Modificación',
            render: (value: unknown): ReactNode => <DateRenderer value={value as string} />
          }
        ],
      }
    ]
  };

  return <BaseShowPage options={showOptions} />;
}
