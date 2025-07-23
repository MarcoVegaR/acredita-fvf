import React, { ReactNode } from "react";
import { BaseShowPage, TabConfig, BaseShowOptions } from "@/components/base-show/base-show-page";
import { AccreditationRequest } from "./columns";
import CredentialSection from "@/components/credential-section";
import { 
  UserIcon,
  CalendarIcon,
  MapPinIcon,
  ClockIcon,
  CreditCardIcon,
  History
} from "lucide-react";
import { DateRenderer } from "@/components/base-show/renderers/date-renderer";
import { TimelineTab } from "@/components/timeline-tab";


interface AccreditationRequestShowProps {
  request: AccreditationRequest & {
    employee: {
      id: number;
      first_name: string;
      last_name: string;
      identification: string;
      document_type: string;
      document_number: string;
      photo_path?: string;
      provider?: {
        name: string;
        area: string;
      };
    };
    event: {
      id: number;
      name: string;
      date: string;
      venue: string;
      description?: string;
    };
    zones: Array<{
      id: number;
      name: string;
      description?: string;
    }>;
    creator: {
      id: number;
      name: string;
    };
    credential?: {
      id: number;
      uuid: string;
      status: 'pending' | 'generating' | 'ready' | 'failed';
      retry_count: number;
      error_message?: string;
      generated_at?: string;
      is_ready: boolean;
    };
  };
  canDownload?: boolean;
  canRegenerate?: boolean;
  canViewCredential?: boolean;
  timeline?: Array<{
    type: string;
    timestamp: string;
    user: { id: number; name: string } | null;
    message: string;
    details: string | null;
    icon: string;
    color: string;
  }>;
}

export default function AccreditationRequestShow({ 
  request, 
  canDownload = false, 
  canRegenerate = false,
  canViewCredential = false,
  timeline = []
}: AccreditationRequestShowProps) {
  
  const statusConfig = {
    draft: { label: 'Borrador', bgColor: 'bg-gray-50', textColor: 'text-gray-700' },
    submitted: { label: 'Enviada', bgColor: 'bg-blue-50', textColor: 'text-blue-700' },
    under_review: { label: 'En revisión', bgColor: 'bg-yellow-50', textColor: 'text-yellow-700' },
    approved: { label: 'Aprobada', bgColor: 'bg-green-50', textColor: 'text-green-700' },
    rejected: { label: 'Rechazada', bgColor: 'bg-red-50', textColor: 'text-red-700' },
    cancelled: { label: 'Cancelada', bgColor: 'bg-gray-50', textColor: 'text-gray-500' },
  };

  // Verificar si puede ver credenciales (solicitud aprobada)
  // El permiso ahora viene desde el backend
  // Usar props del backend en lugar de calcular en frontend

  // Configuración de tabs
  const tabs: TabConfig[] = [
    { 
      value: "general", 
      label: "Información General", 
      icon: <ClockIcon className="h-4 w-4" /> 
    },
    { 
      value: "employee", 
      label: "Empleado", 
      icon: <UserIcon className="h-4 w-4" /> 
    },
    { 
      value: "event", 
      label: "Evento", 
      icon: <CalendarIcon className="h-4 w-4" /> 
    },
    { 
      value: "zones", 
      label: "Zonas", 
      icon: <MapPinIcon className="h-4 w-4" /> 
    },
    ...(canViewCredential ? [
      { 
        value: "credential", 
        label: "Credencial", 
        icon: <CreditCardIcon className="h-4 w-4" /> 
      }
    ] : []),
    { 
      value: "metadata", 
      label: "Metadatos", 
      icon: <ClockIcon className="h-4 w-4" /> 
    },
    { 
      value: "timeline", 
      label: "Historial", 
      icon: <History className="h-4 w-4" /> 
    },
  ];

  const showOptions: BaseShowOptions<AccreditationRequest> = {
    title: `Solicitud de Acreditación #${request.id}`,
    subtitle: `${request.employee.first_name} ${request.employee.last_name} - ${request.event.name}`,
    headerContent: (
      <div className="flex items-center space-x-4 py-3">
        <div className="flex-shrink-0">
          <div className="flex items-center justify-center h-16 w-16 rounded-full bg-primary/10 text-primary font-semibold text-xl">
            #{request.id}
          </div>
        </div>
        <div>
          <div className="flex items-center gap-2">
            <h2 className="text-xl font-bold text-foreground">{request.employee.first_name} {request.employee.last_name}</h2>
            <div className={`inline-flex items-center justify-center whitespace-nowrap rounded-md px-2.5 py-0.5 text-xs font-medium ${
              statusConfig[request.status as keyof typeof statusConfig]?.bgColor || 'bg-gray-50'
            } ${
              statusConfig[request.status as keyof typeof statusConfig]?.textColor || 'text-gray-700'
            }`}>
              {statusConfig[request.status as keyof typeof statusConfig]?.label || request.status}
            </div>
          </div>
          <p className="text-muted-foreground">{request.event.name}</p>
          <p className="text-sm text-muted-foreground">{request.employee.document_type}: {request.employee.document_number}</p>
        </div>
      </div>
    ),
    breadcrumbs: [
      { title: 'Dashboard', href: '/dashboard' },
      { title: 'Solicitudes de Acreditación', href: '/accreditation-requests' },
      { title: `Solicitud #${request.id}`, href: `/accreditation-requests/${request.uuid}` },
    ],
    entity: request,
    moduleName: 'accreditation_requests',
    
    // Configuración de tabs
    tabs,
    defaultTab: "general",
    
    sections: [
      // Tab: Información General
      {
        title: 'Estado de la Solicitud',
        tab: 'general',
        className: 'bg-card rounded-lg border shadow-sm p-6',
        fields: [
          { 
            key: 'id' as keyof typeof request, 
            label: 'ID de Solicitud',
            render: (value: unknown): ReactNode => (
              <span className="font-mono text-xs bg-muted px-2 py-1 rounded">#{value as string | number}</span>
            )
          },
          {
            key: 'status' as keyof typeof request,
            label: 'Estado',
            render: (value: unknown): ReactNode => {
              const status = value as string;
              const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.draft;
              return (
                <div className={`inline-flex items-center justify-center whitespace-nowrap rounded-md px-2.5 py-0.5 text-xs font-medium ${config.bgColor} ${config.textColor}`}>
                  {config.label}
                </div>
              );
            }
          },
          {
            key: 'requested_at' as keyof typeof request,
            label: 'Fecha de Envío',
            render: (value: unknown): ReactNode => {
              const dateValue = value as string;
              return dateValue ? <DateRenderer value={dateValue} /> : <>No enviada aún</>;
            }
          },
          {
            key: 'comments' as keyof typeof request,
            label: 'Comentarios',
            render: (value: unknown): ReactNode => <>{(value as string) || 'Sin comentarios'}</>
          }
        ],
      },
      
      // Tab: Empleado
      {
        title: 'Información Personal',
        tab: 'employee',
        className: 'bg-card rounded-lg border shadow-sm p-6',
        fields: [
          {
            key: 'employee' as keyof typeof request,
            label: 'Nombre Completo',
            render: (): ReactNode => <>{request.employee.first_name} {request.employee.last_name}</>
          },
          {
            key: 'employee' as keyof typeof request,
            label: 'Documento',
            render: (): ReactNode => <>{request.employee.document_type}: {request.employee.document_number}</>
          }
        ],
      },
      {
        title: 'Proveedor',
        tab: 'employee',
        className: 'bg-card rounded-lg border shadow-sm p-6',
        fields: [
          {
            key: 'employee' as keyof typeof request,
            label: 'Proveedor',
            render: (): ReactNode => <>{request.employee.provider?.name || 'No asignado'}</>
          },
          {
            key: 'employee' as keyof typeof request,
            label: 'Área',
            render: (): ReactNode => <>{request.employee.provider?.area || 'No asignada'}</>
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
            key: 'event' as keyof typeof request,
            label: 'Nombre del Evento',
            render: (): ReactNode => <>{request.event.name}</>
          },
          {
            key: 'event' as keyof typeof request,
            label: 'Fecha del Evento',
            render: (): ReactNode => <DateRenderer value={request.event.date} />
          },
          {
            key: 'event' as keyof typeof request,
            label: 'Lugar',
            render: (): ReactNode => <>{request.event.venue}</>
          },
          {
            key: 'event' as keyof typeof request,
            label: 'Descripción',
            render: (): ReactNode => <>{request.event.description || 'Sin descripción'}</>
          }
        ],
      },
      
      // Tab: Zonas
      {
        title: 'Zonas Solicitadas',
        tab: 'zones',
        className: 'bg-card rounded-lg border shadow-sm p-6',
        fields: [
          {
            key: 'zones' as keyof typeof request,
            label: 'Zonas',
            render: (): ReactNode => {
              if (!request.zones || request.zones.length === 0) {
                return <span className="text-gray-500 italic">Sin zonas asignadas</span>;
              }
              return (
                <div className="flex flex-wrap gap-2">
                  {request.zones.map((zone) => (
                    <span
                      key={zone.id}
                      className="inline-flex items-center justify-center whitespace-nowrap rounded-md px-2.5 py-0.5 text-xs font-medium bg-blue-50 text-blue-700"
                    >
                      {zone.name}
                    </span>
                  ))}
                </div>
              );
            }
          }
        ],
      },
      
      // Tab: Credencial (solo si está aprobada)
      ...(canViewCredential ? [
        {
          title: 'Credencial Digital',
          tab: 'credential',
          className: 'bg-card rounded-lg border shadow-sm p-6',
          fields: [
            {
              key: 'credential' as keyof typeof request,
              label: '',
              render: (): ReactNode => (
                <div className="w-full">
                  <CredentialSection 
                    request={request} 
                    canDownload={canDownload}
                    canRegenerate={canRegenerate}
                  />
                </div>
              )
            }
          ]
        }
      ] : []),
      
      // Tab: Metadatos
      {
        title: 'Información del Sistema',
        tab: 'metadata',
        className: 'bg-card rounded-lg border shadow-sm p-6',
        fields: [
          {
            key: 'creator' as keyof typeof request,
            label: 'Creado por',
            render: (): ReactNode => <>{request.creator?.name || 'Sistema'}</>
          },
          {
            key: 'created_at' as keyof typeof request,
            label: 'Fecha de Creación',
            render: (value: unknown): ReactNode => <DateRenderer value={value as string} />
          },
          {
            key: 'updated_at' as keyof typeof request,
            label: 'Última Modificación',
            render: (value: unknown): ReactNode => <DateRenderer value={value as string} />
          }
        ],
      },
      
      // Tab: Timeline/Historial
      {
        title: 'Historial de Cambios',
        tab: 'timeline',
        className: 'bg-card rounded-lg border shadow-sm p-6',
        fields: [
          {
            key: 'id' as keyof typeof request, // Campo dummy requerido
            render: (): ReactNode => <TimelineTab timeline={timeline} />
          }
        ]
      }
    ],
  };

  return <BaseShowPage options={showOptions} />;
}
