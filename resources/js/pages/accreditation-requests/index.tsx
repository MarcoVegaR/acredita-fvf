import React, { useState } from "react";
import { BaseIndexPage } from "@/components/base-index/base-index-page";
import { columns, type AccreditationRequest } from "./columns";
import { 
  Plus, 
  RotateCcw, 
  CheckCircle,
  XCircle,
  Ban as BanIcon,
  FileText as FileTextIcon,
  TicketCheck,
  RefreshCw,
  Users,
  ChevronDown,
  FileSpreadsheet
} from 'lucide-react';
import { toast } from 'sonner';
import { SuspensionDialog } from "@/components/suspension-dialog";
import { ActionDialog } from "@/components/action-dialog";
import { usePage, router } from "@inertiajs/react";
import { SharedData } from "@/types";
import { usePermissions } from "@/hooks/usePermissions";
import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

// Interfaces para manejar los diálogos
interface SuspensionDialogState {
  isOpen: boolean;
  requestData: AccreditationRequest | null;
}

interface ActionDialogState {
  isOpen: boolean;
  requestData: AccreditationRequest | null;
  action: 'reject' | 'return' | null;
}

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
    under_review: number;
    approved: number;
    rejected: number;
    cancelled: number;
    suspended: number;
  };
  filters?: {
    search?: string;
    sort?: string;
    order?: "asc" | "desc";
    page?: number;
    per_page?: number;
    event_id?: number;
    area_id?: number;
    provider_id?: number;
    zone_id?: number;
    status?: string;
  };
  // Datos para los selectores de filtros
  areas: Array<{ id: number; name: string }>;
  providers: Array<{ id: number; name: string }>;
  zones: Array<{ id: number; name: string }>;
  events: Array<{ id: number; name: string }>;
}

export default function Index(props: AccreditationRequestsIndexProps) {
  // Obtener datos de autenticación para verificar roles
  const { auth } = usePage<SharedData>().props;
  
  // Estado para manejar opciones de proveedores dinámicamente
  // Inicia vacío hasta que se seleccione un área (comportamiento dependiente)
  const [providerOptions, setProviderOptions] = useState<Array<{ value: string; label: string }>>([]);
  
  // Función para cargar proveedores por área
  const loadProvidersByArea = React.useCallback(async (areaId: string) => {
    try {
      const response = await fetch(`/accreditation-requests?area_id=${areaId}`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'Content-Type': 'application/json'
        }
      });
      
      if (response.ok) {
        const data = await response.json();
        
        if (data.providers) {
          const newProviderOptions = data.providers.map((provider: { id: number; name: string }) => ({
            value: String(provider.id),
            label: provider.name
          }));
          
          setProviderOptions(newProviderOptions);
        }
      }
    } catch {
      // Error silencioso - solo limpiar las opciones
      setProviderOptions([]);
    }
  }, []);
  
  // useEffect para cargar proveedores cuando cambia el área seleccionada
  React.useEffect(() => {
    const areaId = props.filters?.area_id;
    
    if (areaId && String(areaId) !== '' && String(areaId) !== 'all') {
      loadProvidersByArea(String(areaId));
    } else {
      setProviderOptions([]);
    }
  }, [props.filters?.area_id, loadProvidersByArea]);
  
  // Estado para controlar el diálogo de suspensión
  const [suspensionDialog, setSuspensionDialog] = useState<SuspensionDialogState>({
    isOpen: false,
    requestData: null
  });

  // Estado para controlar los diálogos de acción (rechazar o devolver)
  const [actionDialog, setActionDialog] = useState<ActionDialogState>({
    isOpen: false,
    requestData: null,
    action: null
  });

  // Manejador para la acción de suspensión desde el diálogo
  const handleSuspendConfirm = (reason: string) => {
    const request = suspensionDialog.requestData;
    
    if (!request) {
      console.error('[SUSPEND ACTION] No hay datos de solicitud');
      return;
    }
    
    console.log('[SUSPEND ACTION] Enviando petición POST:', {
      url: `/accreditation-requests/${request.uuid}/suspend`,
      data: { reason: reason }
    });
    
    router.post(`/accreditation-requests/${request.uuid}/suspend`, 
      // Asegurarse de que reason nunca sea null
      { reason: reason || '' }, 
      {
        preserveState: false,
        preserveScroll: true,
        onStart: () => {
          console.log('[SUSPEND ACTION] Petición POST iniciada');
        },
        onSuccess: (page) => {
          console.log('[SUSPEND ACTION] Éxito - Credencial suspendida:', page);
          toast.success('La credencial ha sido suspendida exitosamente');
        },
        onError: (errors) => {
          console.error('[SUSPEND ACTION] Error en la petición:', errors);
          console.error('[SUSPEND ACTION] Detalles del error:', {
            uuid: request.uuid,
            errors: errors,
            reason: reason,
            timestamp: new Date().toISOString()
          });
          toast.error('Error al suspender la credencial');
        },
        onFinish: () => {
          console.log('[SUSPEND ACTION] Petición finalizada');
        }
      }
    );
  };

  // Manejador para las acciones de rechazo y devolución desde el diálogo
  const handleActionConfirm = (reason: string) => {
    const request = actionDialog.requestData;
    const action = actionDialog.action;
    
    if (!request || !action) {
      console.error(`[${action?.toUpperCase()} ACTION] No hay datos de solicitud o acción`);
      return;
    }
    
    const urlEndpoint = action === 'reject' ? 'reject' : 'return-to-draft';
    const actionLabel = action === 'reject' ? 'rechazada' : 'devuelta para corrección';
    const logPrefix = action === 'reject' ? 'REJECT' : 'RETURN';
    
    console.log(`[${logPrefix} ACTION] Enviando petición POST:`, {
      url: `/accreditation-requests/${request.uuid}/${urlEndpoint}`,
      data: { reason: reason }
    });
    
    router.post(`/accreditation-requests/${request.uuid}/${urlEndpoint}`, 
      // Asegurarse de que reason nunca sea null
      { reason: reason || '' }, 
      {
        preserveState: false,
        preserveScroll: true,
        onStart: () => {
          console.log(`[${logPrefix} ACTION] Petición POST iniciada`);
        },
        onSuccess: (page) => {
          console.log(`[${logPrefix} ACTION] Éxito - Solicitud ${actionLabel}:`, page);
          toast.success(`La solicitud ha sido ${actionLabel} exitosamente`);
        },
        onError: (errors) => {
          console.error(`[${logPrefix} ACTION] Error en la petición:`, errors);
          console.error(`[${logPrefix} ACTION] Detalles del error:`, {
            uuid: request.uuid,
            errors: errors,
            reason: reason,
            timestamp: new Date().toISOString()
          });
          toast.error(`Error al procesar la solicitud`);
        },
        onFinish: () => {
          console.log(`[${logPrefix} ACTION] Petición finalizada`);
        }
      }
    );
  };

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
            { value: "submitted", label: "Enviada" },
            { value: "under_review", label: "En revisión" },
            { value: "approved", label: "Aprobada" },
            { value: "rejected", label: "Rechazada" },
            { value: "cancelled", label: "Cancelada" },
            { value: "suspended", label: "Suspendida" }
          ]
        },
        {
          id: "area_id",
          label: "Área",
          options: props.areas.map(area => ({
            value: String(area.id),
            label: area.name
          }))
        },
        {
          id: "provider_id",
          label: "Proveedor",
          options: providerOptions // Usar estado dinámico en lugar de props estáticas
        },
        {
          id: "zone_id",
          label: "Zona",
          options: props.zones.map(zone => ({
            value: String(zone.id),
            label: zone.name
          }))
        },
        {
          id: "event_id",
          label: "Evento",
          options: props.events.map(event => ({
            value: String(event.id),
            label: event.name
          }))
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
        value: props.stats?.total || 0, 
        label: "Total de solicitudes",
        icon: "ticket",
        color: "text-blue-500"
      },
      { 
        value: props.stats?.draft || 0, 
        label: "Borradores",
        icon: "edit",
        color: "text-amber-500"
      },
      { 
        value: props.stats?.submitted || 0, 
        label: "Enviadas",
        icon: "send",
        color: "text-indigo-500"
      },
      { 
        value: props.stats?.under_review || 0, 
        label: "En revisión",
        icon: "search",
        color: "text-purple-500"
      },
      { 
        value: props.stats?.approved || 0, 
        label: "Aprobadas",
        icon: "check-circle",
        color: "text-green-500"
      },
      { 
        value: props.stats?.rejected || 0, 
        label: "Rechazadas",
        icon: "x-circle",
        color: "text-red-500"
      },
      { 
        value: props.stats?.cancelled || 0, 
        label: "Canceladas",
        icon: "ban",
        color: "text-gray-500"
      },
      { 
        value: props.stats?.suspended || 0, 
        label: "Suspendidas",
        icon: "pause-circle",
        color: "text-orange-500"
      }
    ],
    
    // Configuración de traducciones y búsqueda
    moduleName: "accreditation_requests",
    routeKeyName: "uuid", // Usar UUID en lugar de ID para las rutas
    searchableColumns: ["employee.first_name", "employee.last_name", "employee.document_number", "event.name"],
    searchPlaceholder: "Buscar por nombre, apellido, cédula o evento...",
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
    filterableColumns: ["employee.first_name", "employee.last_name", "employee.document_number", "event.name", "status"],
    defaultSorting: [{ id: "id", desc: true }],
    exportOptions: {
      enabled: true,
      fileName: "solicitudes-acreditacion",
      exportTypes: ["excel", "csv", "print", "copy"] as ("excel" | "csv" | "print" | "copy")[],
      customActions: [
        {
          label: "Reporte Completo (Admin)",
          icon: <FileSpreadsheet className="h-4 w-4" />,
          onClick: () => {
            // Descargar reporte detallado completo - sin filtros
            window.open('/accreditation-requests/detailed-export', '_blank');
          },
          showCondition: () => {
            // Solo mostrar para admin y security_manager
            const { hasRole } = usePermissions();
            return hasRole('admin') || hasRole('security_manager');
          }
        }
      ],
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
          // Admin y security_manager pueden editar en cualquier estado
          const isPrivilegedUser = auth.user?.roles?.includes('admin') || auth.user?.roles?.includes('security_manager');
          const canEdit = isPrivilegedUser || row.status === 'draft';
          
          console.log('[EDIT CONDITION] Verificando condición para editar:', {
            uuid: row.uuid,
            status: row.status,
            userRoles: auth.user?.roles,
            isPrivilegedUser,
            canEdit
          });
          
          return canEdit;
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
            return canApprove;
          },
          confirmMessage: (request: AccreditationRequest) => 
            `¿Está seguro que desea aprobar la solicitud de acreditación para ${request.employee.first_name} ${request.employee.last_name}?`,
          confirmTitle: "Aprobar Solicitud",
          handler: (request: AccreditationRequest) => {
            router.post(`/accreditation-requests/${request.uuid}/approve`, {}, {
              preserveState: false,
              preserveScroll: true,
              onSuccess: () => {
              },
              onError: (errors) => {
                console.error('[APPROVE ACTION] Error en la petición:', errors);
              },
              onFinish: () => {
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
            return canReject;
          },
          handler: (request: AccreditationRequest) => {
            setActionDialog({
              isOpen: true,
              requestData: request,
              action: 'reject'
            });
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
            const comments = window.prompt('Comentarios del visto bueno (opcional):');
            const finalComments = comments || ''; // Si es null, usar string vacío
            
            router.post(`/accreditation-requests/${request.uuid}/review`, { comments: finalComments }, {
              preserveState: false,
              preserveScroll: true,
              onSuccess: () => {
              },
              onError: (errors) => {
                console.error('[REVIEW ACTION] Error en la petición:', errors);
              },
              onFinish: () => {
              }
            });
          }
        },
        // Suspender/Anular una credencial aprobada
        {
          label: "Suspender",
          icon: <BanIcon className="h-4 w-4" />,
          permission: "accreditation_request.approve", // Mismo permiso que para aprobar
          showCondition: (request: AccreditationRequest) => {
            return request.status === 'approved';
          },
          handler: (request: AccreditationRequest) => {
            setSuspensionDialog({
              isOpen: true,
              requestData: request
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
            return canReturn;
          },
          handler: (request: AccreditationRequest) => {
            setActionDialog({
              isOpen: true,
              requestData: request,
              action: 'return'
            });
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
            return hasCredential;
          },
          confirmMessage: (request: AccreditationRequest) => 
            `¿Está seguro de regenerar la credencial de ${request.employee.first_name} ${request.employee.last_name}?\n\nEsto actualizará la credencial con el diseño actual de la plantilla.`,
          confirmTitle: "Regenerar Credencial",
          handler: (request: AccreditationRequest) => {
            router.post(`/accreditation-requests/${request.uuid}/credential/regenerate`, {}, {
              preserveState: false,
              preserveScroll: true,
              onSuccess: () => {
              },
              onError: (errors) => {
                console.error('[REGENERATE ACTION] Error en la petición:', errors);
              },
              onFinish: () => {
              }
            });
          }
        }
      ]
    },
    
    // Configuración de acciones masivas
    bulkActions: [
      // Enviar solicitudes masivo
      {
        label: "Enviar solicitudes",
        icon: <TicketCheck className="h-4 w-4" />,
        permission: "accreditation_request.submit",
        showCondition: (selectedRows: AccreditationRequest[]) => {
          return selectedRows.some(r => r.status === 'draft');
        },
        confirmMessage: (selectedRows: AccreditationRequest[]) => {
          const validRequests = selectedRows.filter(r => r.status === 'draft');
          const totalSelected = selectedRows.length;
          const validCount = validRequests.length;
          const skippedCount = totalSelected - validCount;
          
          let message = `¿Está seguro que desea enviar ${validCount} solicitud${validCount !== 1 ? 'es' : ''} de acreditación?`;
          
          if (skippedCount > 0) {
            message += ` Se omitirán ${skippedCount} solicitud${skippedCount !== 1 ? 'es' : ''} que no están en borrador.`;
          }
          
          return message;
        },
        confirmTitle: "Enviar Solicitudes",
        handler: async (selectedRows: AccreditationRequest[]) => {
          // Filtrar solo las solicitudes que pueden enviarse (solo draft)
          const validRequests = selectedRows.filter(r => r.status === 'draft');
          
          if (validRequests.length === 0) {
            // No hay solicitudes válidas para enviar
            return;
          }
          
          const uuids = validRequests.map(r => r.uuid);
          router.post('/accreditation-requests/bulk/submit', { uuids }, {
            preserveState: false,
            preserveScroll: true,
            onError: (errors) => {
              console.error('[BULK SUBMIT] Error:', errors);
            }
          });
        }
      },
      // Dar visto bueno masivo
      {
        label: "Dar visto bueno masivo",
        icon: <CheckCircle className="h-4 w-4" />,
        permission: "accreditation_request.review",
        showCondition: (selectedRows: AccreditationRequest[]) => {
          return selectedRows.some(r => r.status === 'submitted');
        },
        confirmMessage: (selectedRows: AccreditationRequest[]) => {
          const validRequests = selectedRows.filter(r => r.status === 'submitted');
          const totalSelected = selectedRows.length;
          const validCount = validRequests.length;
          const skippedCount = totalSelected - validCount;
          
          let message = `¿Está seguro que desea dar visto bueno a ${validCount} solicitud${validCount !== 1 ? 'es' : ''}?`;
          
          if (skippedCount > 0) {
            message += ` Se omitirán ${skippedCount} solicitud${skippedCount !== 1 ? 'es' : ''} que no están enviadas.`;
          }
          
          return message;
        },
        confirmTitle: "Dar Visto Bueno Masivo",
        requiresReason: true,
        reasonLabel: "Comentarios (opcional)",
        reasonPlaceholder: "Ingrese comentarios sobre el visto bueno...",
        handler: async (selectedRows: AccreditationRequest[], reason?: string) => {
          // Filtrar solo las solicitudes que pueden revisarse (solo submitted)
          const validRequests = selectedRows.filter(r => r.status === 'submitted');
          
          if (validRequests.length === 0) {
            // No hay solicitudes válidas para revisar
            return;
          }
          
          const uuids = validRequests.map(r => r.uuid);
          router.post('/accreditation-requests/bulk/review', { uuids, comments: reason || '' }, {
            preserveState: false,
            preserveScroll: true,
            onError: (errors) => {
              console.error('[BULK REVIEW] Error:', errors);
            }
          });
        }
      },
      // Aprobar masivo
      {
        label: "Aprobar seleccionadas",
        icon: <CheckCircle className="h-4 w-4" />,
        permission: "accreditation_request.approve",
        showCondition: (selectedRows: AccreditationRequest[]) => {
          return selectedRows.some(r => ['submitted', 'under_review'].includes(r.status));
        },
        confirmMessage: (selectedRows: AccreditationRequest[]) => {
          const validRequests = selectedRows.filter(r => ['submitted', 'under_review'].includes(r.status));
          const totalSelected = selectedRows.length;
          const validCount = validRequests.length;
          const skippedCount = totalSelected - validCount;
          
          let message = `¿Está seguro que desea aprobar ${validCount} solicitud${validCount !== 1 ? 'es' : ''} de acreditación?`;
          
          if (skippedCount > 0) {
            message += ` Se omitirán ${skippedCount} solicitud${skippedCount !== 1 ? 'es' : ''} que ya están aprobadas.`;
          }
          
          return message;
        },
        confirmTitle: "Aprobar Solicitudes",
        handler: async (selectedRows: AccreditationRequest[]) => {
          // Filtrar solo las solicitudes que pueden aprobarse
          const validRequests = selectedRows.filter(r => ['submitted', 'under_review'].includes(r.status));
          
          if (validRequests.length === 0) {
            // No hay solicitudes válidas para aprobar
            return;
          }
          
          const uuids = validRequests.map(r => r.uuid);
          router.post('/accreditation-requests/bulk/approve', {
            uuids: uuids
          }, {
            preserveState: false,
            preserveScroll: true,
            onError: (errors) => {
              console.error('[BULK APPROVE] Error:', errors);
            }
          });
        }
      },
      // Rechazar masivo
      {
        label: "Rechazar seleccionadas",
        icon: <XCircle className="h-4 w-4" />,
        permission: "accreditation_request.reject",
        showCondition: (selectedRows: AccreditationRequest[]) => {
          return selectedRows.some(r => ['submitted', 'under_review'].includes(r.status));
        },
        confirmMessage: (selectedRows: AccreditationRequest[]) => {
          const validRequests = selectedRows.filter(r => ['submitted', 'under_review'].includes(r.status));
          const totalSelected = selectedRows.length;
          const validCount = validRequests.length;
          const skippedCount = totalSelected - validCount;
          
          let message = `¿Está seguro que desea rechazar ${validCount} solicitud${validCount !== 1 ? 'es' : ''} de acreditación?`;
          
          if (skippedCount > 0) {
            message += ` Se omitirán ${skippedCount} solicitud${skippedCount !== 1 ? 'es' : ''} que no están en estado válido.`;
          }
          
          return message;
        },
        confirmTitle: "Rechazar Solicitudes",
        requiresReason: true,
        reasonLabel: "Motivo del rechazo",
        reasonPlaceholder: "Ingrese el motivo del rechazo...",
        handler: async (selectedRows: AccreditationRequest[], reason?: string) => {
          // Filtrar solo las solicitudes que pueden rechazarse
          const validRequests = selectedRows.filter(r => ['submitted', 'under_review'].includes(r.status));
          
          if (validRequests.length === 0) {
            // No hay solicitudes válidas para rechazar
            return;
          }
          
          const uuids = validRequests.map(r => r.uuid);
          router.post('/accreditation-requests/bulk/reject', {
            uuids: uuids,
            reason: reason
          }, {
            preserveState: false,
            preserveScroll: true,
            onError: (errors) => {
              console.error('[BULK REJECT] Error:', errors);
            }
          });
        }
      }
    ]
  };

  // Verificar permisos usando la información de auth ya declarada
  const canCreate = Array.isArray(auth.user?.permissions) && auth.user.permissions.includes('accreditation_request.create');

  return (
    <>
      {/* Diálogo de suspensión */}
      <SuspensionDialog 
        open={suspensionDialog.isOpen}
        onOpenChange={(open) => setSuspensionDialog(prev => ({ ...prev, isOpen: open }))}
        title="Suspender Credencial"
        description={suspensionDialog.requestData ? 
          `¿Está seguro que desea suspender la credencial de ${suspensionDialog.requestData.employee.first_name} ${suspensionDialog.requestData.employee.last_name}? Esta acción no puede deshacerse.` : 
          ""}
        onConfirm={handleSuspendConfirm}
      />

      {/* Diálogo de acción (Rechazar o Devolver) */}
      <ActionDialog 
        open={actionDialog.isOpen}
        onOpenChange={(open) => setActionDialog(prev => ({ ...prev, isOpen: open }))}
        title={actionDialog.action === 'reject' ? "Rechazar Solicitud" : "Devolver para Corrección"}
        description={actionDialog.requestData ? 
          `${actionDialog.action === 'reject' 
            ? '¿Está seguro que desea rechazar la solicitud de' 
            : '¿Está seguro que desea devolver para corrección la solicitud de'} ${actionDialog.requestData.employee.first_name} ${actionDialog.requestData.employee.last_name}?` : 
          ""}
        reasonLabel={actionDialog.action === 'reject' ? "Motivo del rechazo" : "Motivo para devolver"}
        reasonPlaceholder={actionDialog.action === 'reject' ? "Ingrese el motivo del rechazo" : "Ingrese el motivo para devolver"}
        confirmButtonLabel={actionDialog.action === 'reject' ? "Rechazar" : "Devolver"}
        confirmButtonClass={actionDialog.action === 'reject' ? "bg-destructive hover:bg-destructive/90" : "bg-amber-500 hover:bg-amber-600"}
        isReasonRequired={true}
        onConfirm={handleActionConfirm}
      />
      
      <BaseIndexPage<AccreditationRequest> 
        data={props.accreditation_requests} 
        filters={props.filters} 
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
                    Crear solicitudes para múltiples colaboradores
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
