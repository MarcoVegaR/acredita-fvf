import React, { useEffect, useState } from "react";
import { Head, router } from "@inertiajs/react";
import { BaseIndexPage } from "@/components/base-index/base-index-page";
import { columns } from "./columns";
import { PrintBatchStats, TablePrintBatch, ProcessingBatch } from "./types";
import { DownloadIcon } from "lucide-react";

interface Props {
  batches: {
    data: TablePrintBatch[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
  filters: Record<string, string | number | boolean>;
  stats: PrintBatchStats;
}

export default function PrintBatchesIndex({ batches, stats }: Props) {
  const [, setProcessingBatches] = useState<ProcessingBatch[]>([]);

  // Polling para lotes en procesamiento
  useEffect(() => {
    let previousProcessingCount = 0;
    
    // Verificar lotes en procesamiento y recargar si es necesario
    const checkAndRefreshIfNeeded = () => {
      fetch('/api/print-batches/processing')
        .then(res => res.json())
        .then(data => {
          const newProcessingBatches = data.batches || [];
          const currentProcessingCount = newProcessingBatches.length;
          
          setProcessingBatches(newProcessingBatches);
          
          // Recargar si hay lotes en procesamiento O si el número de lotes cambió
          // (esto incluye cuando lotes terminan de procesar)
          const shouldReload = currentProcessingCount > 0 || 
                              (currentProcessingCount !== previousProcessingCount);
          
          if (shouldReload) {
            console.log('[PRINT BATCHES] Estado de lotes cambió, recargando página...', {
              previous: previousProcessingCount,
              current: currentProcessingCount,
              processingBatches: newProcessingBatches.map((b: ProcessingBatch) => b.uuid)
            });
            router.reload({ only: ['batches', 'stats'] });
          }
          
          previousProcessingCount = currentProcessingCount;
        })
        .catch(error => {
          console.error('[PRINT BATCHES] Error al consultar lotes en procesamiento:', error);
        });
    };

    // Ejecutar inmediatamente y luego cada 8 segundos
    checkAndRefreshIfNeeded();
    const interval = setInterval(checkAndRefreshIfNeeded, 8000);

    return () => clearInterval(interval);
  }, []);

  const options = {
    title: "Lotes de Impresión",
    subtitle: "Gestionar lotes de impresión de credenciales",
    endpoint: "/print-batches",
    breadcrumbs: [
      { title: "Dashboard", href: "/dashboard" },
      { title: "Lotes de Impresión", href: "/print-batches" }
    ],
    
    // Botón principal de crear
    newButton: {
      show: true,
      label: "Crear Lote",
      onClick: () => router.visit('/print-batches/create'),
      permission: "print_batch.manage"
    },

    // Configuración de stats cards
    stats: [
      {
        label: "Total de Lotes",
        value: stats.total,
        icon: "FileText",
        color: "blue"
      },
      {
        label: "Listos",
        value: stats.ready,
        icon: "CheckCircle",
        color: "green"
      },
      {
        label: "Procesando",
        value: stats.processing,
        icon: "Clock",
        color: "yellow"
      },
      {
        label: "Fallidos", 
        value: stats.failed,
        icon: "AlertCircle",
        color: "red"
      }
    ],

    // Columnas
    columns: columns,
    
    // Datos
    data: batches.data,
    pagination: {
      total: batches.total,
      perPage: batches.per_page,
      currentPage: batches.current_page,
      lastPage: batches.last_page
    },
    
    // Configuración básica de filtros
    filterConfig: {},
    
    // Acciones de fila
    rowActions: {
      view: {
        enabled: true,
        label: "Ver Detalles",
        permission: "print_batch.manage",
        handler: (row: TablePrintBatch) => {
          router.get(`/print-batches/${row.uuid}`);
        },
      },
      edit: { 
        enabled: false, 
        label: "Editar" 
      },
      delete: { 
        enabled: false, 
        label: "Eliminar" 
      },
      // Acciones personalizadas
      custom: [
        // Descargar PDF - solo para lotes listos con PDF generado
        {
          label: "Descargar PDF",
          icon: <DownloadIcon className="h-4 w-4" />,
          permission: "print_batch.manage",
          showCondition: (batch: TablePrintBatch) => {
            const canDownload = batch.status === 'ready' && !!batch.pdf_path;
            console.log('[DOWNLOAD CONDITION] Verificando condición para descargar:', {
              uuid: batch.uuid,
              status: batch.status,
              pdf_path: batch.pdf_path,
              canDownload: canDownload
            });
            return canDownload;
          },
          handler: (batch: TablePrintBatch) => {
            console.log('[DOWNLOAD ACTION] Descargando PDF para lote:', {
              uuid: batch.uuid,
              url: `/print-batches/${batch.uuid}/download`
            });
            window.open(`/print-batches/${batch.uuid}/download`, '_blank');
          }
        }
      ]
    },
    
    // Configuración de ordenamiento
    defaultSort: {
      field: "created_at",
      order: "desc" as const
    },
    
    // Configuración de exportación
    exportConfig: {
      enabled: true,
      fileName: "lotes-impresion",
      exportTypes: ["excel", "csv"] as const
    },
    
    // Permisos
    permissions: {
      create: "print_batch.manage",
      view: "print_batch.manage",
      export: "print_batch.manage"
    }
  };

  return (
    <>
      <Head title="Lotes de Impresión" />
      <BaseIndexPage data={batches} options={options} />
    </>
  );
}
