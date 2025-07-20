import { z } from "zod";

// Interfaz TypeScript para la plantilla
export interface Template {
  id?: number;
  uuid?: string;
  event_id: number;
  name: string;
  file_path?: string;
  file_url?: string;
  layout_meta: {
    fold_mm?: number;
    rect_photo?: {
      x?: number;
      y?: number;
      width?: number;
      height?: number;
    };
    rect_qr?: {
      x?: number;
      y?: number;
      width?: number;
      height?: number;
    };
    text_blocks?: Array<{
      id: string;
      x?: number;
      y?: number;
      width?: number;
      height?: number;
      font_size?: number;
      alignment?: "left" | "center" | "right";
    }>;
  };
  is_default?: boolean;
  event?: {
    id: number;
    name: string;
  };
  version?: number;
  created_at?: string;
  updated_at?: string;
}

// Esquema de validación Zod para la creación/edición de plantillas
export const templateSchema = z.object({
  event_id: z.number({ 
    required_error: "El evento es obligatorio",
    invalid_type_error: "El evento debe ser válido" 
  }),
  name: z.string()
    .min(1, "El nombre es obligatorio")
    .max(255, "El nombre debe tener máximo 255 caracteres"),
  template_file: z.instanceof(File, { message: "El archivo debe ser un archivo válido" })
    .optional()
    .refine(
      (file) => !file || file.size <= 5242880, // 5MB
      "El archivo debe ser menor a 5MB"
    )
    .refine(
      (file) => !file || ["image/png", "application/pdf"].includes(file.type),
      "Solo se permiten archivos PNG o PDF"
    ),
  layout_meta: z.object({
    fold_mm: z.coerce.number({
      required_error: "La línea de pliegue es obligatoria",
      invalid_type_error: "La línea de pliegue debe ser un número"
    }).min(1, "La línea de pliegue debe ser mayor a 0"),
    rect_photo: z.object({
      x: z.coerce.number().min(0, "La posición X debe ser mayor o igual a 0"),
      y: z.coerce.number().min(0, "La posición Y debe ser mayor o igual a 0"),
      width: z.coerce.number().min(1, "El ancho debe ser mayor a 0"),
      height: z.coerce.number().min(1, "La altura debe ser mayor a 0")
    }),
    rect_qr: z.object({
      x: z.coerce.number().min(0, "La posición X debe ser mayor o igual a 0"),
      y: z.coerce.number().min(0, "La posición Y debe ser mayor o igual a 0"),
      width: z.coerce.number().min(1, "El ancho debe ser mayor a 0"),
      height: z.coerce.number().min(1, "La altura debe ser mayor a 0")
    }),
    text_blocks: z.array(
      z.object({
        id: z.string().min(1, "El identificador es obligatorio"),
        x: z.coerce.number().min(0, "La posición X debe ser mayor o igual a 0"),
        y: z.coerce.number().min(0, "La posición Y debe ser mayor o igual a 0"),
        width: z.coerce.number().min(1, "El ancho debe ser mayor a 0"),
        height: z.coerce.number().min(1, "La altura debe ser mayor a 0"),
        font_size: z.coerce.number().min(1, "El tamaño de fuente debe ser mayor a 0"),
        alignment: z.enum(["left", "center", "right"], {
          invalid_type_error: "La alineación debe ser izquierda, centro o derecha"
        })
      })
    ).optional().default([])
  })
});

// Valor por defecto para un nuevo layout meta
export const defaultLayoutMeta = {
  fold_mm: 50,
  rect_photo: {
    x: 10,
    y: 10,
    width: 100,
    height: 120
  },
  rect_qr: {
    x: 10,
    y: 150,
    width: 80,
    height: 80
  },
  text_blocks: []
};
