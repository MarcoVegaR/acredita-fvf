import React from "react";
import { BaseIndexPage } from "@/components/base-index/base-index-page";
import { columns, type AccreditationRequest } from "./columns";
import { TicketCheck, FileTextIcon, CheckCircle, XCircle, RotateCcw, RefreshCw, Users, Plus, ChevronDown } from "lucide-react";
import { router } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { usePage } from "@inertiajs/react";
import { SharedData } from "@/types";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

// Define the props interface - adaptada para usar con BaseIndexPage
interface AccreditationRequestsIndexProps {
  accreditation_requests: {
    data: AccreditationRequest[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
  stats: {
    total: number;
    draft: number;
    submitted: number;
  };
  filters?: {
    search?: string;
    sort?: string;
    order?: "asc" | "desc";
    page?: number;
    per_page?: number;
    event_id?: number;
    status?: string;
  };
}

export default function Index({ accreditation_requests, stats, filters = {} }: AccreditationRequestsIndexProps) {
  // Configuración centralizada para el índice de solicitudes de acreditación
  const indexOptions = {
    // Información principal
    title: "Solicitudes de acreditación",
    subtitle: "Gestiona las solicitudes de acreditación para eventos",
    endpoint: "/accreditation-requests",
    
    // Configuración de filtros personalizados
    filterConfig: {
      select: [
        {
          id: "status",
          label: "Estado",
          options: [
            { value: "draft", label: "Borrador" },
            { value: "submitted", label: "Enviada" }
          ]
        },
        {
          id: "event_id",
          label: "Evento",
          options: [
            // Estos serán cargados dinámicamente desde el backend
            { value: "1", label: "Venezuela vs Colombia" }
          ]
        }
      ]
    },
    filterEmptyMessage: "Sin filtros aplicados. Utilice el botón 'Filtrar' para refinar los resultados.",
    
    // Configuración de permisos Spatie
    permissions: {
      view: "accreditation_request.view",
      create: "accreditation_request.create",
      edit: "accreditation_request.update",
      delete: "accreditation_request.delete"
    },
    
    // Estadísticas para mostrar en las tarjetas
    stats: [
      { 
        value: stats?.total || 0, 
        label: "Total de solicitudes",
        icon: "ticket",
        color: "text-blue-500"
      },
      { 
        value: stats?.draft || 0, 
        label: "Borradores",
        icon: "edit",
        color: "text-amber-500"
      },
      { 
        value: stats?.submitted || 0, 
        label: "Enviadas",
        icon: "check-circle",
        color: "text-green-500"
      }
    ],
    
    // Configuración de traducciones y búsqueda
    moduleName: "accreditation_requests",
    routeKeyName: "uuid", // Usar UUID en lugar de ID para las rutas
    searchableColumns: ["employee.first_name", "employee.last_name", "event.name"],
    searchPlaceholder: "Buscar por empleado o evento...",
    breadcrumbs: [
      {
        title: "Dashboard",
        href: "/dashboard",
      },
      {
        title: "Solicitudes de acreditación",
        href: "/accreditation-requests",
      },
    ],
    columns: columns,
    filterableColumns: ["employee.first_name", "event.name", "status"],
    defaultSorting: [{ id: "id", desc: true }],
    exportOptions: {
      enabled: true,
      fileName: "solicitudes-acreditacion",
      exportTypes: ["excel", "csv", "print", "copy"] as ("excel" | "csv" | "print" | "copy")[],
    },
    newButton: {
      show: false, // Deshabilitamos el botón automático para usar nuestro dropdown personalizado
      label: "Nueva solicitud",
      permission: "accreditation_request.create",
      onClick: () => {
        router.get("/accreditation-requests/create/step-1");
      }
    },
    
    // Configuración de acciones de fila - usando UUID para las rutas
    rowActions: {
      // Acción de ver detalles - siempre visible
      view: {
        enabled: true,
        label: "Ver detalles",
        permission: "accreditation_request.view",
        handler: (row: AccreditationRequest) => {
          router.get(`/accreditation-requests/${row.uuid}`);
        },
      },
      // Acción de editar - solo para borradores
      edit: {
        enabled: true,
        label: "Editar",
        permission: "accreditation_request.update",
        showCondition: (row: AccreditationRequest) => {
          console.log('[EDIT CONDITION] Verificando condición para editar:', {
            uuid: row.uuid,
            status: row.status,
            canEdit: row.status === 'draft'
          });
          return row.status === 'draft';
        },
        handler: (row: AccreditationRequest) => {
          console.log('[EDIT ACTION] Navegando a edición:', {
            uuid: row.uuid,
            status: row.status,
            url: `/accreditation-requests/${row.uuid}/edit`
          });
          router.get(`/accreditation-requests/${row.uuid}/edit`);
        },
      },
      // Acción de eliminar - los administradores pueden eliminar cualquier estado
      delete: {
        enabled: true,
        label: "Eliminar",
        permission: "accreditation_request.delete",
        confirmMessage: (row: AccreditationRequest) => `¿Está seguro que desea eliminar la solicitud de ${row.employee.first_name} ${row.employee.last_name}?`,
        handler: (row: AccreditationRequest) => {
          router.delete(`/accreditation-requests/${row.uuid}`);
        },
      },
      // Acciones personalizadas con condiciones según el estado
      custom: [

        // Enviar solicitud - solo para borradores
        {
          label: "Enviar solicitud",
          icon: <TicketCheck className="h-4 w-4" />,
          permission: "accreditation_request.submit",
          showCondition: (request: AccreditationRequest) => request.status === 'draft',
          confirmMessage: (request: AccreditationRequest) => `¿Está seguro que desea enviar la solicitud de acreditación para ${request.employee.first_name} ${request.employee.last_name}?`,
          handler: (request: AccreditationRequest) => {
            console.log('[SUBMIT ACTION] Enviando solicitud:', request.uuid);
            router.post(`/accreditation-requests/${request.uuid}/submit`);
          }
        },
        // Aprobar solicitud - para solicitudes enviadas o en revisión
        {
          label: "Aprobar",
          icon: <CheckCircle className="h-4 w-4" />,
          permission: "accreditation_request.approve",
          showCondition: (request: AccreditationRequest) => {
            const canApprove = request.status === 'submitted' || request.status === 'under_review';
            console.log('[APPROVE CONDITION] Verificando condición para aprobar:', {
              uuid: request.uuid,
              status: request.status,
              canApprove: canApprove,
              employee: `${request.employee.first_name} ${request.employee.last_name}`
            });
            return canApprove;
          },
          confirmMessage: (request: AccreditationRequest) => 
            `¿Está seguro que desea aprobar la solicitud de acreditación para ${request.employee.first_name} ${request.employee.last_name}?`,
          confirmTitle: "Aprobar Solicitud",
          handler: (request: AccreditationRequest) => {
            console.log('[APPROVE ACTION] Iniciando aprobación:', {
              uuid: request.uuid,
              status: request.status,
              employee: `${request.employee.first_name} ${request.employee.last_name}`,
              url: `/accreditation-requests/${request.uuid}/approve`
            });
            
            router.post(`/accreditation-requests/${request.uuid}/approve`, {}, {
              preserveState: false,
              preserveScroll: true,
              onStart: () => {
                console.log('[APPROVE ACTION] Petición POST iniciada');
              },
              onSuccess: (page) => {
                console.log('[APPROVE ACTION] Éxito - Solicitud aprobada:', page);
              },
              onError: (errors) => {
                console.error('[APPROVE ACTION] Error en la petición:', errors);
                console.error('[APPROVE ACTION] Detalles del error:', {
                  uuid: request.uuid,
                  errors: errors,
                  timestamp: new Date().toISOString()
                });
              },
              onFinish: () => {
                console.log('[APPROVE ACTION] Petición finalizada');
              }
            });
          }
        },
        // Rechazar solicitud - para solicitudes enviadas o en revisión
        {
          label: "Rechazar",
          icon: <XCircle className="h-4 w-4" />,
          permission: "accreditation_request.reject",
          showCondition: (request: AccreditationRequest) => {
            const canReject = request.status === 'submitted' || request.status === 'under_review';
            console.log('[REJECT CONDITION] Verificando condición para rechazar:', {
              uuid: request.uuid,
              status: request.status,
              canReject: canReject,
              employee: `${request.employee.first_name} ${request.employee.last_name}`
            });
            return canReject;
          },
          confirmMessage: (request: AccreditationRequest) => 
            `¿Está seguro que desea rechazar la solicitud de ${request.employee.first_name} ${request.employee.last_name}?`,
          confirmTitle: "Rechazar Solicitud",
          handler: (request: AccreditationRequest) => {
            console.log('[REJECT ACTION] Iniciando rechazo:', {
              uuid: request.uuid,
              status: request.status,
              employee: `${request.employee.first_name} ${request.employee.last_name}`
            });
            
            const reason = prompt('Motivo del rechazo (opcional):');
            console.log('[REJECT ACTION] Motivo ingresado:', reason);
            
            // Continuar aunque el usuario cancele el prompt (reason es opcional)
            const finalReason = reason || ''; // Si es null, usar string vacío
            
            console.log('[REJECT ACTION] Enviando petición POST:', {
              url: `/accreditation-requests/${request.uuid}/reject`,
              data: { reason: finalReason }
            });
            
            router.post(`/accreditation-requests/${request.uuid}/reject`, 
              { reason: finalReason }, 
              {
                preserveState: false,
                preserveScroll: true,
                onStart: () => {
                  console.log('[REJECT ACTION] Petición POST iniciada');
                },
                onSuccess: (page) => {
                  console.log('[REJECT ACTION] Éxito - Solicitud rechazada:', page);
                },
                onError: (errors) => {
                  console.error('[REJECT ACTION] Error en la petición:', errors);
                  console.error('[REJECT ACTION] Detalles del error:', {
                    uuid: request.uuid,
                    errors: errors,
                    reason: finalReason,
                    timestamp: new Date().toISOString()
                  });
                },
                onFinish: () => {
                  console.log('[REJECT ACTION] Petición finalizada');
                }
              }
            );
          }
        },
        // Acción para dar visto bueno (area manager)
        {
          label: 'Dar visto bueno',
          icon: <CheckCircle className="h-4 w-4" />,
          permission: 'accreditation_request.review',
          showCondition: (request: AccreditationRequest) => request.status === 'submitted',
          confirmMessage: (request: AccreditationRequest) => 
            `¿Está seguro que desea dar visto bueno a la solicitud de ${request.employee.first_name} ${request.employee.last_name}?`,
          confirmTitle: "Dar Visto Bueno",
          handler: (request: AccreditationRequest) => {
            console.log('[REVIEW ACTION] Iniciando visto bueno:', {
              uuid: request.uuid,
              status: request.status,
              employee: `${request.employee.first_name} ${request.employee.last_name}`
            });
            
            const comments = window.prompt('Comentarios del visto bueno (opcional):');
            console.log('[REVIEW ACTION] Comentarios ingresados:', comments);
            
            // Continuar aunque el usuario cancele el prompt (comentarios son opcionales)
            const finalComments = comments || ''; // Si es null, usar string vacío
            
            console.log('[REVIEW ACTION] Enviando petición POST:', {
              url: `/accreditation-requests/${request.uuid}/review`,
              data: { comments: finalComments }
            });
            
            router.post(`/accreditation-requests/${request.uuid}/review`, { comments: finalComments }, {
              preserveState: false,
              preserveScroll: true,
              onStart: () => {
                console.log('[REVIEW ACTION] Petición POST iniciada');
              },
              onSuccess: (page) => {
                console.log('[REVIEW ACTION] Éxito - Visto bueno otorgado:', page);
              },
              onError: (errors) => {
                console.error('[REVIEW ACTION] Error en la petición:', errors);
                console.error('[REVIEW ACTION] Detalles del error:', {
                  uuid: request.uuid,
                  errors: errors,
                  comments: finalComments,
                  timestamp: new Date().toISOString()
                });
              },
              onFinish: () => {
                console.log('[REVIEW ACTION] Petición finalizada');
              }
            });
          }
        },
        // Devolver a borrador para corrección
        {
          label: "Devolver para corrección",
          icon: <RotateCcw className="h-4 w-4" />,
          permission: "accreditation_request.return",
          showCondition: (request: AccreditationRequest) => {
            const canReturn = request.status === 'submitted' || request.status === 'under_review';
            console.log('[RETURN CONDITION] Verificando condición para devolver:', {
              uuid: request.uuid,
              status: request.status,
              canReturn: canReturn,
              employee: `${request.employee.first_name} ${request.employee.last_name}`
            });
            return canReturn;
          },
          confirmMessage: (request: AccreditationRequest) => 
            `¿Está seguro que desea devolver para corrección la solicitud de ${request.employee.first_name} ${request.employee.last_name}?`,
          confirmTitle: "Devolver para Corrección",
          handler: (request: AccreditationRequest) => {
            console.log('[RETURN ACTION] Iniciando devolución:', {
              uuid: request.uuid,
              status: request.status,
              employee: `${request.employee.first_name} ${request.employee.last_name}`
            });
            
            const reason = prompt('Motivo para devolver (opcional):');
            console.log('[RETURN ACTION] Motivo ingresado:', reason);
            
            // Continuar aunque el usuario cancele el prompt (motivo es opcional)
            const finalReason = reason || ''; // Si es null, usar string vacío
            
            console.log('[RETURN ACTION] Enviando petición POST:', {
              url: `/accreditation-requests/${request.uuid}/return-to-draft`,
              data: { reason: finalReason }
            });
            
            router.post(`/accreditation-requests/${request.uuid}/return-to-draft`, 
              { reason: finalReason }, 
              {
                preserveState: false,
                preserveScroll: true,
                onStart: () => {
                  console.log('[RETURN ACTION] Petición POST iniciada');
                },
                onSuccess: (page) => {
                  console.log('[RETURN ACTION] Éxito - Solicitud devuelta a borrador:', page);
                },
                onError: (errors) => {
                  console.error('[RETURN ACTION] Error en la petición:', errors);
                  console.error('[RETURN ACTION] Detalles del error:', {
                    uuid: request.uuid,
                    errors: errors,
                    reason: finalReason,
                    timestamp: new Date().toISOString()
                  });
                },
                onFinish: () => {
                  console.log('[RETURN ACTION] Petición finalizada');
                }
              }
            );
          }
        },
        // Regenerar credencial - solo para solicitudes aprobadas con credencial
        {
          label: "Regenerar credencial",
          icon: <RefreshCw className="h-4 w-4" />,
          permission: "credentials.regenerate",
          showCondition: (request: AccreditationRequest): boolean => {
            const hasCredential = request.status === 'approved' && 
                                 request.credential != null && 
                                 request.credential.status === 'ready';
            console.log('[REGENERATE CONDITION] Verificando condición para regenerar:', {
              uuid: request.uuid,
              status: request.status,
              hasCredential: hasCredential,
              credentialStatus: request.credential?.status,
              employee: `${request.employee.first_name} ${request.employee.last_name}`
            });
            return hasCredential;
          },
          confirmMessage: (request: AccreditationRequest) => 
            `¿Está seguro de regenerar la credencial de ${request.employee.first_name} ${request.employee.last_name}?\n\nEsto actualizará la credencial con el diseño actual de la plantilla.`,
          confirmTitle: "Regenerar Credencial",
          handler: (request: AccreditationRequest) => {
            console.log('[REGENERATE ACTION] Iniciando regeneración de credencial:', {
              uuid: request.uuid,
              credentialId: request.credential?.id,
              employee: `${request.employee.first_name} ${request.employee.last_name}`
            });
            
            router.post(`/accreditation-requests/${request.uuid}/credential/regenerate`, {}, {
              preserveState: false,
              preserveScroll: true,
              onStart: () => {
                console.log('[REGENERATE ACTION] Petición POST iniciada');
              },
              onSuccess: (page) => {
                console.log('[REGENERATE ACTION] Éxito - Credencial regenerada:', page);
              },
              onError: (errors) => {
                console.error('[REGENERATE ACTION] Error en la petición:', errors);
              },
              onFinish: () => {
                console.log('[REGENERATE ACTION] Petición finalizada');
              }
            });
          }
        }
      ]
    }
  };

  // Obtener información del usuario para verificar permisos
  const { auth } = usePage<SharedData>().props;
  const canCreate = Array.isArray(auth.user?.permissions) && auth.user.permissions.includes('accreditation_request.create');

  // Usar el componente base con la configuración específica
  return (
    <>
      <BaseIndexPage<AccreditationRequest> 
        data={accreditation_requests} 
        filters={filters} 
        options={{
          ...indexOptions,
          newButton: {
            show: false, // Deshabilitamos el botón automático para usar nuestro dropdown personalizado
            label: "Nueva Solicitud"
          }
        }} 
      />
      
      {/* Botón personalizado con dropdown - se renderiza en la posición correcta */}
      {canCreate && (
        <div className="fixed top-[120px] right-6 z-10">
          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button className="flex items-center gap-2 shadow-lg">
                <Plus className="h-4 w-4" />
                Nueva Solicitud
                <ChevronDown className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-56">
              <DropdownMenuItem
                onClick={() => router.get('/accreditation-requests/create/step-1')}
                className="flex items-center gap-2 cursor-pointer"
              >
                <FileTextIcon className="h-4 w-4" />
                <div className="flex flex-col">
                  <span className="font-medium">Solicitud Individual</span>
                  <span className="text-xs text-muted-foreground">
                    Crear solicitud para un empleado
                  </span>
                </div>
              </DropdownMenuItem>
              <DropdownMenuItem
                onClick={() => router.get('/accreditation-requests/bulk/step-1')}
                className="flex items-center gap-2 cursor-pointer"
              >
                <Users className="h-4 w-4" />
                <div className="flex flex-col">
                  <span className="font-medium">Solicitud Masiva</span>
                  <span className="text-xs text-muted-foreground">
                    Crear solicitudes para múltiples empleados
                  </span>
                </div>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      )}
    </>
  );
}
