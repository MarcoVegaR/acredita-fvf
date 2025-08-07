import { z } from "zod";

// Interfaces base
export interface Event {
  id: number;
  name: string;
  description?: string;
  start_date: string;
  end_date: string;
  location?: string;
  status: string;
  zones?: Zone[];
}

export interface Zone {
  id: number;
  name: string;
  description?: string;
  color?: string;
  capacity?: number;
}

// Interfaces para solicitudes masivas
export interface EmployeeWithZones {
  id: number;
  name: string;
  document_id: string;
  photo_url?: string;
  position?: string;
  department?: string;
  provider?: {
    id: number;
    name: string;
    type: string;
  };
  selected: boolean;
  zones: number[]; // IDs de zonas asignadas a este colaborador
}

export interface BulkRequestSummary {
  event_id: number;
  total_employees: number;
  employees: EmployeeWithZones[];
  notes?: string;
}

// Esquemas de validación para solicitud masiva

// Paso 1: Selección de evento (reutiliza el existente)
export const bulkStep1Schema = z.object({
  event_id: z.string({
    required_error: "Debe seleccionar un evento",
  }),
});

// Paso 2: Selección masiva de colaboradores
export const bulkStep2Schema = z.object({
  event_id: z.string({
    required_error: "El evento es obligatorio",
  }),
  employee_ids: z.array(z.string()).min(1, "Debe seleccionar al menos un colaborador"),
});

// Paso 3: Configuración de zonas por colaborador
export const bulkStep3Schema = z.object({
  event_id: z.string({
    required_error: "El evento es obligatorio",
  }),
  employee_zones: z.record(
    z.string(), // employee_id
    z.array(z.string()).min(1, "Cada colaborador debe tener al menos una zona")
  ).refine(
    (data) => Object.keys(data).length > 0,
    { message: "Debe configurar zonas para al menos un colaborador" }
  ),
});

// Paso 4: Confirmación masiva
export const bulkStep4Schema = z.object({
  event_id: z.string({
    required_error: "El evento es obligatorio",
  }),
  employee_zones: z.record(
    z.string(), // employee_id
    z.array(z.string()).min(1, "Cada colaborador debe tener al menos una zona")
  ),
  confirm: z.boolean().refine(val => val === true, {
    message: "Debe confirmar que la información es correcta",
  }),
  notes: z.string().optional(),
});

// Esquema completo para la solicitud masiva
export const bulkAccreditationRequestSchema = z.object({
  event_id: z.string(),
  employee_zones: z.record(
    z.string(), // employee_id
    z.array(z.string()).min(1)
  ),
  notes: z.string().optional(),
});

// Tipos inferidos
export type BulkStep1FormValues = z.infer<typeof bulkStep1Schema>;
export type BulkStep2FormValues = z.infer<typeof bulkStep2Schema>;
export type BulkStep3FormValues = z.infer<typeof bulkStep3Schema>;
export type BulkStep4FormValues = z.infer<typeof bulkStep4Schema>;
export type BulkAccreditationRequestFormValues = z.infer<typeof bulkAccreditationRequestSchema>;

// Estado del wizard masivo
export interface BulkWizardState {
  event_id?: string;
  employee_ids?: string[];
  employee_zones?: Record<string, string[]>;
  notes?: string;
  currentStep: number;
}

// Template para zonas reutilizables
export interface ZoneTemplate {
  id: string;
  name: string;
  zones: number[];
  description?: string;
}

// Configuración de filtros para colaboradores
export interface EmployeeFilters {
  search?: string;
  department?: string;
  position?: string;
  provider_id?: string;
}
