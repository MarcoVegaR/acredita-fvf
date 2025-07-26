import React from "react";
import { ColumnDef } from "@tanstack/react-table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Copy, Calendar, FileText, Users, AlertCircle, CheckCircle, Clock, User } from "lucide-react";
import { TablePrintBatch } from "./types";
import { formatDistanceToNow } from "date-fns";
import { es } from "date-fns/locale";

// Función para copiar UUID al portapapeles
const copyToClipboard = (text: string) => {
  navigator.clipboard.writeText(text);
};

// Función para formatear fecha relativa
const formatRelativeDate = (dateString: string) => {
  return formatDistanceToNow(new Date(dateString), { 
    addSuffix: true, 
    locale: es 
  });
};

// Función para obtener icono según estado
const getStatusIcon = (status: string) => {
  switch (status) {
    case 'queued':
      return <Clock className="h-4 w-4" />;
    case 'processing':
      return <Clock className="h-4 w-4 animate-spin" />;
    case 'ready':
      return <CheckCircle className="h-4 w-4" />;
    case 'failed':
      return <AlertCircle className="h-4 w-4" />;
    case 'archived':
      return <FileText className="h-4 w-4" />;
    default:
      return <Clock className="h-4 w-4" />;
  }
};

export const columns: ColumnDef<TablePrintBatch>[] = [
  {
    accessorKey: "uuid",
    header: "UUID",
    cell: ({ row }) => {
      const uuid = row.getValue("uuid") as string;
      const shortUuid = uuid.substring(0, 8);
      
      return (
        <div className="flex items-center space-x-2">
          <code className="bg-gray-100 px-2 py-1 rounded text-xs font-mono">
            {shortUuid}
          </code>
          <Button
            variant="ghost"
            size="sm"
            onClick={() => copyToClipboard(uuid)}
            className="h-6 w-6 p-0"
          >
            <Copy className="h-3 w-3" />
          </Button>
        </div>
      );
    },
  },
  {
    accessorKey: "event",
    header: "Evento",
    cell: ({ row }) => {
      const event = row.getValue("event") as { name: string };
      const fullName = event?.name || 'Sin evento';
      
      // Truncar nombre si es muy largo (más de 25 caracteres)
      const truncatedName = fullName.length > 25 
        ? fullName.substring(0, 22) + '...'
        : fullName;
      
      return (
        <div className="flex items-center space-x-2" title={fullName}>
          <Calendar className="h-4 w-4 text-muted-foreground" />
          <span className="font-medium">{truncatedName}</span>
        </div>
      );
    },
  },
  {
    accessorKey: "areas",
    header: "Área",
    cell: ({ row }) => {
      // Debug logs
      console.log('[DEBUG AREAS] Row original:', row.original);
      console.log('[DEBUG AREAS] Areas raw:', row.original.areas);
      console.log('[DEBUG AREAS] Areas value:', row.getValue("areas"));
      
      // Intenta obtener áreas de diferentes formas para diagnosticar
      const areasFromOriginal = row.original.areas;
      const areasFromValue = row.getValue("areas");
      
      // Log detallado
      console.log('[DEBUG AREAS] Areas from original:', {
        value: areasFromOriginal,
        type: typeof areasFromOriginal,
        isArray: Array.isArray(areasFromOriginal),
        length: areasFromOriginal ? (Array.isArray(areasFromOriginal) ? areasFromOriginal.length : 'not array') : 'undefined'
      });
      
      // Usar el valor que esté disponible, con preferencia por original.areas
      const areas = areasFromOriginal || areasFromValue as Array<{ name: string }> | undefined;
      
      if (!areas || areas.length === 0) {
        console.log('[DEBUG AREAS] No areas found, returning placeholder');
        return <span className="text-muted-foreground">—</span>;
      }
      
      // Todas las áreas para el tooltip
      const allAreas = areas.map(area => area.name).join(', ');
      
      // Si hay más de 2 áreas, mostrar contador
      if (areas.length > 2) {
        return (
          <div className="flex flex-col" title={allAreas}>
            <span className="text-sm">{areas[0].name}</span>
            <span className="text-xs text-muted-foreground">+{areas.length - 1} más</span>
          </div>
        );
      }
      
      // Si son pocas áreas, mostrarlas todas
      return (
        <div className="flex flex-col" title={allAreas}>
          {areas.map((area, i) => (
            <span key={i} className="text-sm">{area.name}</span>
          ))}
        </div>
      );
    },
  },
  {
    accessorKey: "providers",
    header: "Proveedor",
    cell: ({ row }) => {
      // Debug logs
      console.log('[DEBUG PROVIDERS] Row original:', row.original);
      console.log('[DEBUG PROVIDERS] Providers raw:', row.original.providers);
      console.log('[DEBUG PROVIDERS] Providers value:', row.getValue("providers"));
      
      // Intenta obtener proveedores de diferentes formas para diagnosticar
      const providersFromOriginal = row.original.providers;
      const providersFromValue = row.getValue("providers");
      
      // Log detallado
      console.log('[DEBUG PROVIDERS] Providers from original:', {
        value: providersFromOriginal,
        type: typeof providersFromOriginal,
        isArray: Array.isArray(providersFromOriginal),
        length: providersFromOriginal ? (Array.isArray(providersFromOriginal) ? providersFromOriginal.length : 'not array') : 'undefined'
      });
      
      // Usar el valor que esté disponible, con preferencia por original.providers
      const providers = providersFromOriginal || providersFromValue as Array<{ name: string }> | undefined;
      
      if (!providers || providers.length === 0) {
        console.log('[DEBUG PROVIDERS] No providers found, returning placeholder');
        return <span className="text-muted-foreground">—</span>;
      }
      
      // Todos los proveedores para el tooltip
      const allProviders = providers.map(provider => provider.name).join(', ');
      
      // Si hay más de 2 proveedores, mostrar contador
      if (providers.length > 2) {
        return (
          <div className="flex flex-col" title={allProviders}>
            <span className="text-sm">{providers[0].name}</span>
            <span className="text-xs text-muted-foreground">+{providers.length - 1} más</span>
          </div>
        );
      }
      
      // Si son pocos proveedores, mostrarlos todos
      return (
        <div className="flex flex-col" title={allProviders}>
          {providers.map((provider, i) => (
            <span key={i} className="text-sm">{provider.name}</span>
          ))}
        </div>
      );
    },
  },
  {
    accessorKey: "total_credentials",
    header: "Credenciales",
    cell: ({ row }) => {
      const total = row.getValue("total_credentials") as number;
      const processed = row.original.processed_credentials;
      const isProcessing = row.original.is_processing;
      
      return (
        <div className="flex items-center space-x-2">
          <Users className="h-4 w-4 text-muted-foreground" />
          {isProcessing ? (
            <span className="text-sm">
              {processed} / {total}
            </span>
          ) : (
            <span className="text-sm font-medium">{total}</span>
          )}
        </div>
      );
    },
  },
  {
    accessorKey: "status",
    header: "Estado",
    cell: ({ row }) => {
      const status = row.getValue("status") as string;
      const isProcessing = row.original.is_processing;
      const progressPercentage = row.original.progress_percentage;
      
      // Generar badge info basado en el status
      const getStatusBadgeInfo = (status: string) => {
        switch (status) {
          case 'ready':
            return { label: 'Listo', color: 'green' };
          case 'processing':
            return { label: 'Procesando', color: 'yellow' };
          case 'queued':
            return { label: 'En Cola', color: 'blue' };
          case 'failed':
            return { label: 'Fallido', color: 'red' };
          case 'archived':
            return { label: 'Archivado', color: 'gray' };
          default:
            return { label: 'Desconocido', color: 'gray' };
        }
      };
      
      const statusBadge = getStatusBadgeInfo(status);
      
      return (
        <div className="space-y-1">
          <Badge 
            variant={
              statusBadge.color === 'green' ? 'default' :
              statusBadge.color === 'red' ? 'destructive' :
              statusBadge.color === 'yellow' ? 'secondary' :
              'outline'
            }
            className="flex items-center gap-1"
          >
            {getStatusIcon(status)}
            {statusBadge.label}
          </Badge>
          {isProcessing && (
            <div className="w-full bg-gray-200 rounded-full h-1.5">
              <div 
                className="bg-blue-600 h-1.5 rounded-full transition-all duration-300"
                style={{ width: `${progressPercentage}%` }}
              />
            </div>
          )}
        </div>
      );
    },
  },
  {
    accessorKey: "generated_by_user",
    header: "Generado por",
    cell: ({ row }) => {
      // Debug logs
      console.log('[DEBUG GENERATED_BY] Row original:', row.original);
      console.log('[DEBUG GENERATED_BY] User data:', row.original.generated_by_user);
      
      const user = row.original.generated_by_user as { id: number | null; name: string } | undefined;
      
      // Log detallado
      console.log('[DEBUG GENERATED_BY] User info:', {
        value: user,
        type: typeof user,
        name: user?.name || 'no name',
        id: user?.id || 'no id'
      });
      
      return (
        <div className="flex items-center space-x-2">
          <User className="h-4 w-4 text-muted-foreground" />
          <span className="text-sm font-medium">
            {user?.name || 'Sistema'}
          </span>
        </div>
      );
    },
  },
  {
    accessorKey: "created_at",
    header: "Creado",
    cell: ({ row }) => {
      const createdAt = row.getValue("created_at") as string;
      return (
        <span className="text-sm text-muted-foreground">
          {formatRelativeDate(createdAt)}
        </span>
      );
    },
  },
  // Columna de finalizado eliminada por no ser necesaria
];

// Tipos exportados para uso en otras partes
export type { TablePrintBatch };
