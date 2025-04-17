import React from "react";
import { formatDateToLocale } from "@/utils/format-date";

interface DateRendererProps {
  value: string | null;
  format?: Intl.DateTimeFormatOptions;
}

/**
 * Componente reutilizable para renderizar fechas en formatos consistentes
 */
export const DateRenderer: React.FC<DateRendererProps> = ({ value, format }) => {
  if (!value) return <span className="text-muted-foreground italic">No disponible</span>;
  
  return <span>{formatDateToLocale(value, format)}</span>;
};
