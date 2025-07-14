import { z } from "zod";
import { Area as AreaInterface } from "./columns";

// Definir la interfaz para los datos del formulario de área
export interface AreaFormData {
  code: string;
  name: string;
  description?: string;
  active: boolean;
}

// Interfaz para guardar en el esquema Zod
export type FormArea = Partial<AreaInterface> & AreaFormData;

// Esquema de validación para crear un área
export const createAreaSchema = z.object({
  code: z.string({
    required_error: "El código es obligatorio",
    invalid_type_error: "El código debe ser texto",
  })
    .min(1, "El código es obligatorio")
    .max(10, "El código no debe exceder los 10 caracteres"),
  name: z.string({
    required_error: "El nombre es obligatorio",
    invalid_type_error: "El nombre debe ser texto",
  })
    .min(1, "El nombre es obligatorio")
    .max(100, "El nombre no debe exceder los 100 caracteres"),
  description: z.string().optional(),
  active: z.boolean().default(true),
  // Campos opcionales que podrían estar presentes en la edición
  id: z.number().optional(),
  uuid: z.string().optional(),
  created_at: z.string().optional(),
  updated_at: z.string().optional(),
  deleted_at: z.string().nullable().optional(),
}).passthrough();

// Esquema de validación para actualizar un área
export const updateAreaSchema = createAreaSchema;

// Reexportar la interfaz Area desde columns.ts
export type { AreaInterface as Area };
