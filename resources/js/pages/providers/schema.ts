import { z } from "zod";

import { Entity } from "@/components/base-index/base-index-page";

// Interface Provider para TypeScript, asegura compatibilidad con Entity
export interface Provider extends Entity {
  // id ya está en Entity como number requerido
  uuid: string;
  name: string;
  rif: string;
  phone?: string;
  area_id: number;
  type: "internal" | "external";
  active: boolean;
  user?: {
    id?: number;
    name: string;
    email: string;
    password?: string;
  };
  // created_at y updated_at ya están en Entity
}

// Interface para los valores del formulario, tanto para crear como editar
export interface ProviderFormValues {
  // Campos del provider
  id?: number;
  uuid?: string;
  name: string;
  rif: string;
  phone?: string;
  area_id: number;
  type: "internal" | "external";
  active: boolean;
  user?: {
    id?: number;
    name: string;
    email: string;
    password?: string;
  };
  // Otros campos opcionales para compatibilidad
  created_at?: string;
  updated_at?: string;
  [key: string]: string | number | boolean | null | undefined | Record<string, unknown>; // Tipado más seguro para el índice de signature
}

// Esquema base para validación de campos comunes
export const baseProviderSchema = {
  name: z.string().min(1, "El nombre es obligatorio").max(150, "El nombre no puede exceder los 150 caracteres"),
  rif: z.string().min(1, "El RIF es obligatorio").max(20, "El RIF no puede exceder los 20 caracteres")
    .regex(/^[A-Z0-9-]+$/, "El RIF solo puede contener letras mayúsculas, números y guiones"),
  phone: z.string().max(30, "El teléfono no puede exceder los 30 caracteres").nullable().optional(),
  active: z.boolean().default(true),
};

// Esquema para crear proveedores
export const createProviderSchema = z.object({
  ...baseProviderSchema,
  area_id: z.number({
    required_error: "El área es obligatoria",
    invalid_type_error: "Debe seleccionar un área válida"
  }),
  type: z.enum(["internal", "external"], {
    required_error: "El tipo de proveedor es obligatorio",
  }),
  user: z.union([
    // Para proveedores internos: solo permitimos undefined
    z.undefined(),
    // Para proveedores externos: objeto con datos obligatorios
    z.object({
      name: z.string().min(1, "El nombre del usuario es obligatorio").max(255, "El nombre no puede exceder los 255 caracteres"),
      email: z.string().min(1, "El correo electrónico es obligatorio").email("Correo electrónico inválido").max(255),
      password: z.string().min(8, "La contraseña debe tener al menos 8 caracteres").optional(),
    })
  ])
}).superRefine((data, ctx) => {
  // Solo aplicar validación para proveedores externos
  if (data.type === "external") {
    if (!data.user || !data.user.name || !data.user.email) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "Los proveedores externos requieren información del usuario administrador",
        path: ["user"]
      });
      return false;
    }
  }
  return true;
});

// Esquema para actualizar proveedores
export const updateProviderSchema = z.object({
  ...baseProviderSchema,
  area_id: z.number({
    required_error: "El área es obligatoria",
    invalid_type_error: "Debe seleccionar un área válida"
  }),
  type: z.enum(["internal", "external"]).optional(), // Para poder determinar el tipo en actualizaciones
  user: z.union([
    // Para proveedores internos: solo permitimos undefined
    z.undefined(),
    // Para proveedores externos: objeto con datos
    z.object({
      name: z.string().min(1, "El nombre del usuario es obligatorio").max(255, "El nombre no puede exceder los 255 caracteres"),
      email: z.string().min(1, "El correo electrónico es obligatorio").email("Correo electrónico inválido").max(255),
      password: z.string().min(8, "La contraseña debe tener al menos 8 caracteres").optional().or(z.literal("")),
    })
  ])
}).superRefine((data, ctx) => {
  // Solo aplicar validación para proveedores externos
  if (data.type === "external") {
    if (!data.user || !data.user.name || !data.user.email) {
      ctx.addIssue({
        code: z.ZodIssueCode.custom,
        message: "Los proveedores externos requieren información del usuario administrador",
        path: ["user"]
      });
      return false;
    }
  }
  return true;
});

export type CreateProviderFormData = z.infer<typeof createProviderSchema>;
export type UpdateProviderFormData = z.infer<typeof updateProviderSchema>;
