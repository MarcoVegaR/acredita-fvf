import { z } from "zod";

// Schema para la creación de lotes de impresión
export const createBatchSchema = z.object({
  event_id: z.number({
    required_error: "El evento es obligatorio",
    invalid_type_error: "El evento debe ser un número válido"
  }).min(1, "Debe seleccionar un evento"),
  
  area_id: z.array(z.number()).optional().nullable(),
  
  provider_id: z.array(z.number()).optional().nullable(),
  
  only_unprinted: z.boolean().default(true)
});

// Schema para filtros del índice
export const filtersSchema = z.object({
  search: z.string().optional(),
  status: z.array(z.enum(['queued', 'processing', 'ready', 'failed', 'archived'])).optional(),
  event_id: z.number().optional(),
  area_id: z.array(z.number()).optional(),
  provider_id: z.array(z.number()).optional(),
  include_archived: z.boolean().default(false),
  sort: z.string().default('created_at'),
  order: z.enum(['asc', 'desc']).default('desc'),
  page: z.number().default(1),
  per_page: z.number().default(15)
});

// Schema para preview de lote
export const previewSchema = z.object({
  event_id: z.number().min(1, "Debe seleccionar un evento"),
  area_id: z.array(z.number()).optional().nullable(),
  provider_id: z.array(z.number()).optional().nullable(),
  only_unprinted: z.boolean().default(true)
});

// Tipos derivados de los schemas
export type CreateBatchFormData = z.infer<typeof createBatchSchema>;
export type FiltersData = z.infer<typeof filtersSchema>;
export type PreviewData = z.infer<typeof previewSchema>;

// Schema para validar el estado de un lote
export const batchStatusSchema = z.enum(['queued', 'processing', 'ready', 'failed', 'archived']);

// Mensajes de error personalizados
export const errorMessages = {
  event_id: {
    required: "Debe seleccionar un evento para crear el lote",
    invalid: "El evento seleccionado no es válido"
  },
  area_id: {
    invalid: "Una o más áreas seleccionadas no son válidas"
  },
  provider_id: {
    invalid: "Uno o más proveedores seleccionados no son válidos"
  },
  only_unprinted: {
    invalid: "El filtro 'solo no impresas' debe ser verdadero o falso"
  }
};

// Valores por defecto para formularios
export const defaultValues = {
  createBatch: {
    event_id: 0,
    area_id: [],
    provider_id: [],
    only_unprinted: true
  } as CreateBatchFormData,
  
  filters: {
    search: "",
    status: [],
    event_id: undefined,
    area_id: [],
    provider_id: [],
    include_archived: false,
    sort: "created_at" as const,
    order: "desc" as const,
    page: 1,
    per_page: 15
  } as FiltersData
};
