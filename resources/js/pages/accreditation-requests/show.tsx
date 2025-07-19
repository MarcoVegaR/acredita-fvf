import React from "react";
import { BaseShowPage } from "@/components/base-show/base-show-page";
import { AccreditationRequest } from "./columns";


interface AccreditationRequestShowProps {
  request: AccreditationRequest & {
    employee: {
      id: number;
      first_name: string;
      last_name: string;
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
  };
}

export default function ShowAccreditationRequest({ request }: AccreditationRequestShowProps) {
  const statusConfig = {
    draft: { label: 'Borrador', bgColor: 'bg-gray-50', textColor: 'text-gray-700' },
    submitted: { label: 'Enviada', bgColor: 'bg-blue-50', textColor: 'text-blue-700' },
    under_review: { label: 'En revisión', bgColor: 'bg-yellow-50', textColor: 'text-yellow-700' },
    approved: { label: 'Aprobada', bgColor: 'bg-green-50', textColor: 'text-green-700' },
    rejected: { label: 'Rechazada', bgColor: 'bg-red-50', textColor: 'text-red-700' },
    cancelled: { label: 'Cancelada', bgColor: 'bg-gray-50', textColor: 'text-gray-500' },
  };

  const options = {
    title: `Solicitud de Acreditación #${request.id}`,
    subtitle: `${request.employee.first_name} ${request.employee.last_name} - ${request.event.name}`,
    breadcrumbs: [
      { title: 'Dashboard', href: '/dashboard' },
      { title: 'Solicitudes de Acreditación', href: '/accreditation-requests' },
      { title: `Solicitud #${request.id}`, href: `/accreditation-requests/${request.uuid}` },
    ],
    backUrl: '/accreditation-requests',
    entity: request,
    moduleName: 'accreditation_requests',
    sections: [
      {
        title: 'Información General',
        fields: [
          { 
            key: 'id' as keyof typeof request, 
            label: 'ID de Solicitud',
            render: (value: unknown) => `#${value}`
          },
          {
            key: 'status' as keyof typeof request,
            label: 'Estado',
            render: (value: unknown) => {
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
            render: (value: unknown) => {
              const dateValue = value as string;
              return dateValue ? new Date(dateValue).toLocaleDateString('es-CO', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
              }) : 'No enviada aún';
            }
          },
          {
            key: 'comments' as keyof typeof request,
            label: 'Comentarios',
            render: (value: unknown) => (value as string) || 'Sin comentarios'
          }
        ],
      },
      {
        title: 'Empleado',
        fields: [
          {
            key: 'employee' as keyof typeof request,
            label: 'Nombre Completo',
            render: () => `${request.employee.first_name} ${request.employee.last_name}`
          },
          {
            key: 'employee' as keyof typeof request,
            label: 'Documento',
            render: () => `${request.employee.document_type}: ${request.employee.document_number}`
          },
          {
            key: 'employee' as keyof typeof request,
            label: 'Proveedor',
            render: () => request.employee.provider?.name || 'No asignado'
          },
          {
            key: 'employee' as keyof typeof request,
            label: 'Área',
            render: () => request.employee.provider?.area || 'No asignada'
          }
        ],
      },
      {
        title: 'Evento',
        fields: [
          {
            key: 'event' as keyof typeof request,
            label: 'Nombre del Evento',
            render: () => request.event.name
          },
          {
            key: 'event' as keyof typeof request,
            label: 'Fecha del Evento',
            render: () => new Date(request.event.date).toLocaleDateString('es-CO', {
              year: 'numeric',
              month: 'long',
              day: 'numeric'
            })
          },
          {
            key: 'event' as keyof typeof request,
            label: 'Lugar',
            render: () => request.event.venue
          },
          {
            key: 'event' as keyof typeof request,
            label: 'Descripción',
            render: () => request.event.description || 'Sin descripción'
          }
        ],
      },
      {
        title: 'Zonas Solicitadas',
        fields: [
          {
            key: 'zones' as keyof typeof request,
            label: 'Zonas',
            render: () => {
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
      {
        title: 'Metadatos',
        fields: [
          {
            key: 'creator' as keyof typeof request,
            label: 'Creado por',
            render: () => request.creator?.name || 'Sistema'
          },
          {
            key: 'created_at' as keyof typeof request,
            label: 'Fecha de Creación',
            render: (value: unknown) => {
              const dateValue = value as string;
              return new Date(dateValue).toLocaleDateString('es-CO', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
              });
            }
          },
          {
            key: 'updated_at' as keyof typeof request,
            label: 'Última Modificación',
            render: (value: unknown) => {
              const dateValue = value as string;
              return new Date(dateValue).toLocaleDateString('es-CO', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
              });
            }
          }
        ],
      }
    ],
  };

  return <BaseShowPage options={options} />;
}
