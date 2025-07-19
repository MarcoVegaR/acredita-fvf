import { z } from "zod";

// Definición de interfaces para entidades
export interface Event {
  id: number;
  name: string;
  date: string;
  venue: string;
}

export interface Employee {
  id: number;
  name: string;
  document_id: string;
  photo_url?: string;
  position?: string;
  department?: string;
}

export interface Zone {
  id: number;
  name: string;
  description?: string;
}

// Esquema de validación para el paso 1 (selección de evento)
export const step1Schema = z.object({
  event_id: z.string({
    required_error: "Debe seleccionar un evento",
  }),
});

// Esquema de validación para el paso 2 (selección de empleado)
export const step2Schema = z.object({
  employee_id: z.string({
    required_error: "Debe seleccionar un empleado",
  }),
});

// Esquema de validación para el paso 3 (selección de zonas)
export const step3Schema = z.object({
  zones: z.array(z.string()).min(1, "Debe seleccionar al menos una zona"),
});

// Esquema de validación para el paso 4 (confirmación)
export const step4Schema = z.object({
  confirm: z.boolean().refine(val => val === true, {
    message: "Debe confirmar la solicitud",
  }),
  notes: z.string().optional(),
});

// Esquema completo de la solicitud de acreditación
export const accreditationRequestSchema = z.object({
  event_id: z.string(),
  employee_id: z.string(),
  zone_ids: z.array(z.string()).min(1),
  notes: z.string().optional(),
});

// Tipos inferidos de los esquemas
export type Step1FormValues = z.infer<typeof step1Schema>;
export type Step2FormValues = z.infer<typeof step2Schema>;
export type Step3FormValues = z.infer<typeof step3Schema>;
export type Step4FormValues = z.infer<typeof step4Schema>;
export type AccreditationRequestFormValues = z.infer<typeof accreditationRequestSchema>;

// Tipos para el estado del wizard
export interface WizardState {
  event_id?: string;
  employee_id?: string;
  zones?: string[];
  notes?: string;
  currentStep: number;
}
