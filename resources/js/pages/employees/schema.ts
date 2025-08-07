import { z } from "zod";

// Tipos de documentos disponibles según la migración
export const DOCUMENT_TYPES = [
  { value: "V", label: "Venezolano" },
  { value: "E", label: "Extranjero" },
  { value: "P", label: "Pasaporte" }
] as const;

// Interfaz TypeScript para el colaborador
export interface Employee {
  id?: number;
  uuid?: string;
  provider_id: number;
  document_type: string;
  document_number: string;
  first_name: string;
  last_name: string;
  function: string;
  photo_path?: string | null;
  active: boolean;
  // Campo temporal para la carga de foto (puede ser File o string base64)
  photo?: File | string | null;
  // Campo para almacenar la imagen recortada en formato base64
  croppedPhoto?: string | null;
}

// Interfaz para proveedor (para select)
export interface Provider {
  id: number;
  name: string;
}

// Esquema de validación Zod para crear colaborador
export const createEmployeeSchema = z.object({
  provider_id: z.coerce.number().min(1, "El proveedor es requerido"),
  document_type: z.string().min(1, "El tipo de documento es requerido"),
  document_number: z.string().min(1, "El número de documento es requerido"),
  first_name: z.string().min(1, "El nombre es requerido"),
  last_name: z.string().min(1, "El apellido es requerido"),
  function: z.string().min(1, "La función/cargo es requerido"),
  photo_path: z.string().nullable().optional(),
  active: z.boolean().default(true),
  // Campos temporales para manejo de fotos
  photo: z.any().optional(),
  croppedPhoto: z.string().nullable().optional(),
});

// Esquema de validación Zod para actualizar colaborador
export const updateEmployeeSchema = createEmployeeSchema.extend({
  id: z.number(),
  uuid: z.string().optional(),
});

// Tipo inferido
export type CreateEmployeeFormData = z.infer<typeof createEmployeeSchema>;
export type UpdateEmployeeFormData = z.infer<typeof updateEmployeeSchema>;
