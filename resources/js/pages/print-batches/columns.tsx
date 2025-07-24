import React from "react";
import { ColumnDef } from "@tanstack/react-table";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Copy, Calendar, FileText, Users, AlertCircle, CheckCircle, Clock } from "lucide-react";
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
      return (
        <div className="flex items-center space-x-2">
          <Calendar className="h-4 w-4 text-muted-foreground" />
          <span className="font-medium">{event.name}</span>
        </div>
      );
    },
  },
  {
    accessorKey: "area",
    header: "Área",
    cell: ({ row }) => {
      const area = row.getValue("area") as { name: string } | null;
      if (!area) {
        return <span className="text-muted-foreground">—</span>;
      }
      return <span>{area.name}</span>;
    },
  },
  {
    accessorKey: "provider",
    header: "Proveedor",
    cell: ({ row }) => {
      const provider = row.getValue("provider") as { name: string } | null;
      if (!provider) {
        return <span className="text-muted-foreground">—</span>;
      }
      return <span>{provider.name}</span>;
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
    accessorKey: "generatedBy",
    header: "Generado por",
    cell: ({ row }) => {
      const user = row.original.generatedBy as { name: string } | undefined;
      return (
        <span className="text-sm">
          {user?.name || 'No disponible'}
        </span>
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
  {
    accessorKey: "finished_at",
    header: "Finalizado",
    cell: ({ row }) => {
      const finishedAt = row.getValue("finished_at") as string | null;
      if (!finishedAt) {
        return <span className="text-muted-foreground">—</span>;
      }
      
      return (
        <span className="text-sm text-muted-foreground">
          {formatRelativeDate(finishedAt)}
        </span>
      );
    },
  },
];

// Tipos exportados para uso en otras partes
export type { TablePrintBatch };
