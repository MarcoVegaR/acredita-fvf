// Interfaces para el módulo de templates
import { Template } from "./schema";

export interface TextBlock {
  id: string;
  x?: number;
  y?: number;
  width?: number;
  height?: number;
  font_size?: number;
  alignment?: "left" | "center" | "right";
  // Tipo opcional para bloques especiales (por ejemplo, 'zones')
  type?: string;
  // Propiedades específicas para el bloque dinámico de zonas
  padding?: number;
  gap?: number;
  font_family?: string;
  font_color?: string;
}

export interface Rectangle {
  x?: number;
  y?: number;
  width?: number;
  height?: number;
}

export interface LayoutMeta {
  fold_mm?: number;
  rect_photo?: Rectangle;
  rect_qr?: Rectangle;
  text_blocks?: TextBlock[];
}

// Esta interfaz representa los datos de un evento para selección
export interface EventOption {
  id: number;
  name: string;
}

// Extiende Template para hacerla compatible con la interfaz Entity requerida por BaseIndexPage
export interface TableTemplate extends Omit<Template, 'id'> {
  id: number;  // Aquí convertimos id de opcional a obligatorio
  [key: string]: unknown; // Para compatibilidad con Entity
}
