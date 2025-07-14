/**
 * Funciones de ayuda para el manejo de plantillas
 */
import { Template } from "./schema";
import { TextBlock, LayoutMeta } from "./types";

// Tipo genérico para objetos con propiedades anidadas
type RecursivePartial<T> = {
  [P in keyof T]?: T[P] extends (infer U)[] 
    ? RecursivePartial<U>[] 
    : T[P] extends object 
      ? RecursivePartial<T[P]> 
      : T[P];
};

// Tipo para los datos del formulario
export type FormData = RecursivePartial<Template> & {
  template_file?: File | null;
  _method?: string;
};

/**
 * Asegura que todos los valores numéricos dentro de un objeto sean de tipo número
 * Convierte recursivamente cualquier propiedad que debería ser numérica pero es una cadena
 */
export const ensureNumericValues = (data: unknown): unknown => {
  if (data === null || data === undefined) {
    return data;
  }
  
  // Si es un array, procesar cada elemento
  if (Array.isArray(data)) {
    return data.map(item => ensureNumericValues(item));
  }
  
  // Si es un valor string que puede convertirse a número, convertirlo
  if (typeof data === 'string' && !isNaN(Number(data)) && data.trim() !== '') {
    return Number(data);
  }

  // Si es un objeto, procesar cada propiedad
  if (typeof data === 'object' && data !== null) {
    const result: Record<string, unknown> = {};
    
    Object.keys(data as object).forEach(key => {
      result[key] = ensureNumericValues((data as Record<string, unknown>)[key]);
    });
    
    return result;
  }

  // En otro caso, devolver el valor sin cambios
  return data;
};

/**
 * Preprocesa los datos del formulario de plantilla para asegurar que todos los valores numéricos
 * en layout_meta sean realmente números
 */
export const preprocessTemplateFormData = (formData: FormData): FormData => {

  // Clonar solo layout_meta para no perder valores tipo File u otros no serializables
  const processedData: FormData = { ...formData };
  
  if (formData.layout_meta) {
    // Hacer una copia profunda de layout_meta usando JSON porque solo contiene datos primitivos
    const layoutMetaCopy = JSON.parse(JSON.stringify(formData.layout_meta));
    processedData.layout_meta = layoutMetaCopy as LayoutMeta;
  }
  
  // Si existe layout_meta, procesar sus valores
  if (processedData.layout_meta) {

    // Procesar todo el layout_meta recursivamente usando ensureNumericValues
    if (processedData.layout_meta) {
      processedData.layout_meta = ensureNumericValues(processedData.layout_meta) as RecursivePartial<LayoutMeta>;
    }
    
    // 2. Procesar rect_photo
    if (processedData.layout_meta.rect_photo) {
      const rect = processedData.layout_meta.rect_photo;
      processedData.layout_meta.rect_photo = {
        x: Number(rect.x || 0),
        y: Number(rect.y || 0),
        width: Number(rect.width || 0),
        height: Number(rect.height || 0)
      };
    }
    
    // 3. Procesar rect_qr
    if (processedData.layout_meta.rect_qr) {
      const rect = processedData.layout_meta.rect_qr;
      processedData.layout_meta.rect_qr = {
        x: Number(rect.x || 0),
        y: Number(rect.y || 0),
        width: Number(rect.width || 0),
        height: Number(rect.height || 0)
      };
    }
    
    // 4. Procesar text_blocks
    if (Array.isArray(processedData.layout_meta.text_blocks)) {
      processedData.layout_meta.text_blocks = processedData.layout_meta.text_blocks.map((block: Partial<TextBlock>) => ({
        ...block,
        id: (block.id ?? "") as string,
        x: Number(block.x || 0),
        y: Number(block.y || 0),
        width: Number(block.width || 0),
        height: Number(block.height || 0),
        font_size: Number(block.font_size || 12)
      })) as TextBlock[];
    }
    
    // 5. Finalmente, aplicar conversión recursiva para cualquier otro valor que podría haber quedado
    processedData.layout_meta = ensureNumericValues(processedData.layout_meta) as RecursivePartial<LayoutMeta>;
    

  }
  
  return processedData;
};
