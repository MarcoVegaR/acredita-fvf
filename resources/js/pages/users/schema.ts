import { z } from "zod";

// Esquema para crear usuarios (incluye contraseña requerida)
export const createUserSchema = z.object({
  name: z.string().min(1, "El nombre es obligatorio").max(255, "El nombre no puede exceder los 255 caracteres"),
  email: z.string().email("Formato de correo inválido").min(1, "El correo es obligatorio"),
  password: z.string().min(8, "La contraseña debe tener al menos 8 caracteres").refine(
    (val) => {
      return /[A-Z]/.test(val) && 
        /[a-z]/.test(val) && 
        /[0-9]/.test(val);
    },
    {
      message: "La contraseña debe incluir mayúsculas, minúsculas y números"
    }
  ),
  password_confirmation: z.string().min(1, "Debe confirmar la contraseña"),
  active: z.boolean().optional().default(true),
  roles: z.array(z.string()).optional().default([]),
}).refine((data) => {
  // Validar que las contraseñas coincidan
  return data.password === data.password_confirmation;
}, {
  message: "Las contraseñas no coinciden",
  path: ["password_confirmation"]
});

// Esquema para actualizar usuarios (contraseña opcional)
export const updateUserSchema = z.object({
  name: z.string().min(1, "El nombre es obligatorio").max(255, "El nombre no puede exceder los 255 caracteres"),
  email: z.string().email("Formato de correo inválido").min(1, "El correo es obligatorio"),
  password: z.string().optional().refine(
    (val) => {
      // Solo validar si existe un valor
      if (!val || val.length === 0) return true;
      return val.length >= 8 && 
        /[A-Z]/.test(val) && 
        /[a-z]/.test(val) && 
        /[0-9]/.test(val);
    },
    {
      message: "La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas y números"
    }
  ),
  password_confirmation: z.string().optional(),
  active: z.boolean().optional().default(true),
  roles: z.array(z.string()).optional().default([]),
}).refine((data) => {
  // Validar que las contraseñas coincidan si se está estableciendo una
  if (data.password && data.password.length > 0) {
    return data.password === data.password_confirmation;
  }
  return true;
}, {
  message: "Las contraseñas no coinciden",
  path: ["password_confirmation"]
});

// Tipo para User basado en el esquema
export type User = z.infer<typeof updateUserSchema>;
