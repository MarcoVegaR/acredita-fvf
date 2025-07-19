import { ColumnDef } from "@tanstack/react-table";
import { Button } from "@/components/ui/button";
import { ArrowUpDown } from "lucide-react";
import { Entity } from "@/components/base-index/base-index-page";

// Función para formatear fechas usando el API nativo de JavaScript
const formatDateTime = (dateValue: string | Date | null | undefined): string => {
  // Retornar un mensaje si el valor es nulo o indefinido
  if (!dateValue) {
    return 'N/D';
  }
  
  try {
    // Convertir a Date si es un string
    const date = typeof dateValue === 'string' ? new Date(dateValue) : dateValue;
    
    // Verificar si la fecha es válida
    if (isNaN(date.getTime())) {
      return 'Fecha inválida';
    }
    
    return new Intl.DateTimeFormat('es-ES', {
      dateStyle: 'medium',
      timeStyle: 'short'
    }).format(date);
  } catch (error) {
    console.error('Error al formatear fecha:', error);
    return 'Error de formato';
  }
};

// AccreditationRequest data interface definition  
export interface AccreditationRequest extends Entity {
  id: number;
  uuid: string;
  employee_id: number;
  employee: {
    id: number;
    first_name: string;
    last_name: string;
    document_type: string;
    document_number: string;
    photo_path?: string;
    uuid: string;
  };
  event_id: number;
  event: {
    id: number;
    name: string;
    date: string;
    venue: string;
  };
  zones: {
    id: number;
    name: string;
  }[];
  status: 'draft' | 'submitted' | 'under_review' | 'approved' | 'rejected' | 'cancelled';
  created_by_id: number;
  created_by: {
    id: number;
    name: string;
  };
  comments?: string;
  created_at: string;
  updated_at: string;
  submitted_at?: string;
  // Añadimos la propiedad [key: string]: unknown para cumplir con Entity
  [key: string]: unknown;
}

// Column definitions
export const columns: ColumnDef<AccreditationRequest>[] = [
  {
    accessorKey: "id",
    header: ({ column }) => (
      <Button
        variant="ghost"
        onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
        className="font-semibold"
      >
        ID
        <ArrowUpDown className="ml-2 h-4 w-4" />
      </Button>
    ),
    cell: ({ row }) => <div className="font-medium">{row.getValue("id")}</div>,
    enableSorting: true,
  },
  {
    accessorKey: "employee.first_name",
    header: () => <div className="font-semibold">Empleado</div>,
    cell: ({ row }) => {
      const employee = row.original.employee;
      
      if (!employee) {
        return <div className="text-gray-400 italic">Sin empleado</div>;
      }
      
      // Construir el nombre completo del empleado
      const fullName = `${employee.first_name} ${employee.last_name}`.trim();
      
      // Obtener las iniciales del nombre
      const initials = fullName
        ? fullName.split(' ').slice(0, 2).map(n => n && n[0]).join('').toUpperCase()
        : 'NP';
      
      // Construir el ID del documento
      const documentId = employee.document_type && employee.document_number
        ? `${employee.document_type}-${employee.document_number}`
        : 'Sin documento';
        
      return (
        <div className="flex items-center gap-2">
          {employee.photo_path ? (
            <div className="h-8 w-8 rounded-full overflow-hidden">
              <img src={`/storage/${employee.photo_path}`} alt={fullName} className="h-full w-full object-cover" />
            </div>
          ) : (
            <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-xs font-medium text-primary">{initials}</div>
          )}
          <div>
            <div className="font-medium">{fullName || 'Sin nombre'}</div>
            <div className="text-xs text-gray-500">{documentId}</div>
          </div>
        </div>
      );
    },
    enableSorting: false,
  },
  {
    accessorKey: "event.name",
    header: () => <div className="font-semibold">Evento</div>,
    cell: ({ row }) => {
      const event = row.original.event;
      return (
        <div>
          <div className="font-medium">{event.name}</div>
          <div className="text-xs text-gray-500">
            {event.venue} - {formatDateTime(new Date(event.date))}
          </div>
        </div>
      );
    },
    enableSorting: false,
  },
  {
    accessorKey: "zones",
    header: () => <div className="font-semibold">Zonas</div>,
    cell: ({ row }) => {
      const zones = row.original.zones || [];
      
      if (zones.length === 0) {
        return <div className="text-gray-400 italic">Sin zonas</div>;
      }
      
      return (
        <div className="flex flex-wrap gap-1 max-w-xs">
          {zones.map((zone) => (
            <div 
              key={zone.id}
              className="inline-flex items-center justify-center whitespace-nowrap rounded-md px-2.5 py-0.5 text-xs font-medium bg-blue-50 text-blue-700"
            >
              {zone.name}
            </div>
          ))}
        </div>
      );
    },
    enableSorting: false,
  },
  {
    accessorKey: "status",
    header: () => <div className="font-semibold">Estado</div>,
    cell: ({ row }) => {
      const status = row.getValue("status") as string;
      
      const statusConfig = {
        draft: { label: 'Borrador', bgColor: 'bg-gray-50', textColor: 'text-gray-700' },
        submitted: { label: 'Enviada', bgColor: 'bg-blue-50', textColor: 'text-blue-700' },
        under_review: { label: 'En revisión', bgColor: 'bg-yellow-50', textColor: 'text-yellow-700' },
        approved: { label: 'Aprobada', bgColor: 'bg-green-50', textColor: 'text-green-700' },
        rejected: { label: 'Rechazada', bgColor: 'bg-red-50', textColor: 'text-red-700' },
        cancelled: { label: 'Cancelada', bgColor: 'bg-gray-50', textColor: 'text-gray-500' },
      };
      
      const config = statusConfig[status as keyof typeof statusConfig] || statusConfig.draft;
      
      return (
        <div className={`inline-flex items-center justify-center whitespace-nowrap rounded-md px-2.5 py-0.5 text-xs font-medium ${config.bgColor} ${config.textColor}`}>
          {config.label}
        </div>
      );
    },
    enableSorting: false,
  },

];
