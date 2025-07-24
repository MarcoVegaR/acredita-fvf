import React, { useState, useEffect } from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { CreateBatchForm } from "./create-form";
import { createBatchSchema } from "./schema";
import { PrinterIcon, CalendarIcon, FilterIcon } from "lucide-react";
import { FilterData, BatchPreview, CreateBatchFormData } from "./types";

interface CreatePrintBatchProps {
  filtersData: FilterData;
  preview?: BatchPreview;
}

export default function CreatePrintBatch({ filtersData, preview: initialPreview }: CreatePrintBatchProps) {
  console.log('[CREATE.TSX] Component RENDERED with props:', { filtersData: !!filtersData, initialPreview });
  
  const [preview, setPreview] = useState<BatchPreview | null>(initialPreview || null);
  const [isLoadingPreview, setIsLoadingPreview] = useState(false);
  const [previewError, setPreviewError] = useState<string | null>(null);

  // Actualizar preview cuando cambian las props
  useEffect(() => {
    console.log('[CREATE.TSX] useEffect - initialPreview changed:', initialPreview);
    if (initialPreview) {
      console.log('[CREATE.TSX] Setting preview from props:', initialPreview);
      setPreview(initialPreview);
      setPreviewError(null);
    }
  }, [initialPreview]);

  const formOptions = {
    title: "Crear Lote de Impresión",
    subtitle: "Configure los filtros para generar un lote de impresión masiva de credenciales",
    endpoint: "/print-batches",
    moduleName: "print-batches",
    isEdit: false,
    breadcrumbs: [
      {
        title: "Dashboard",
        href: "/dashboard",
      },
      {
        title: "Lotes de Impresión",
        href: "/print-batches",
      },
      {
        title: "Crear Lote",
        href: "/print-batches/create",
      }
    ],
    defaultTab: "filters",
    tabs: [
      { 
        value: "filters", 
        label: "Filtros de Credenciales", 
        icon: <FilterIcon className="h-4 w-4" /> 
      },
      { 
        value: "preview", 
        label: "Vista Previa", 
        icon: <CalendarIcon className="h-4 w-4" /> 
      },
    ],
    permissions: {
      create: "print_batch.manage",
    },
    actions: {
      save: {
        label: "Crear Lote de Impresión",
        disabledText: "No tienes permisos para crear lotes de impresión",
        loadingText: "Creando lote...",
      },
      cancel: {
        label: "Cancelar",
        href: "/print-batches",
      },
    },
    customComponents: {
      beforeForm: (
        <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div className="flex items-start space-x-3">
            <PrinterIcon className="h-5 w-5 text-blue-600 mt-0.5" />
            <div>
              <h3 className="text-sm font-medium text-blue-900">
                Información sobre Lotes de Impresión
              </h3>
              <p className="text-sm text-blue-700 mt-1">
                Los lotes de impresión consolidan múltiples credenciales en un PDF optimizado 
                para impresión masiva. El proceso es asíncrono y puede tomar varios minutos 
                dependiendo de la cantidad de credenciales.
              </p>
            </div>
          </div>
        </div>
      ),
    }
  };

  // Función para obtener preview de credenciales
  const handlePreview = async (formData: CreateBatchFormData) => {
    console.log('[CREATE.TSX] handlePreview called with formData:', formData);
    
    if (!formData.event_id) {
      console.log('[CREATE.TSX] No event_id, showing error');
      setPreviewError("Debe seleccionar un evento para obtener la vista previa");
      setPreview(null);
      return;
    }

    console.log('[CREATE.TSX] Starting preview request with FETCH (no navigation)...');
    setIsLoadingPreview(true);
    setPreviewError(null);

    try {
      // Usar fetch puro para evitar navegación/remount de Inertia
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      
      const response = await fetch('/print-batches/preview', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken || '',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(formData)
      });

      console.log('[CREATE.TSX] Fetch response status:', response.status);

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({ message: 'Error de red' }));
        throw new Error(errorData.message || `HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      console.log('[CREATE.TSX] Fetch success, received data:', data);
      
      // Actualizar solo el estado local - NO hay navegación
      setPreview(data.preview);
      
    } catch (error) {
      console.error('[CREATE.TSX] Fetch error:', error);
      setPreviewError(error instanceof Error ? error.message : 'Error al obtener vista previa');
      setPreview(null);
    } finally {
      console.log('[CREATE.TSX] Preview request finished');
      setIsLoadingPreview(false);
    }
  };

  return (
    <>
      <Head title="Crear Lote de Impresión" />
      <BaseFormPage 
        options={formOptions}
        schema={createBatchSchema}
        defaultValues={{
          event_id: undefined,
          area_id: undefined,
          provider_id: undefined,
          only_unprinted: false
        }}
        FormComponent={(props) => (
          <CreateBatchForm 
            {...props}
            filtersData={filtersData}
            preview={preview}
            isLoadingPreview={isLoadingPreview}
            previewError={previewError}
            onPreview={handlePreview}
          />
        )}
      />
    </>
  );
}
