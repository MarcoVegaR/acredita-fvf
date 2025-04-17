import React from "react";
import { formatExportValue } from "@/utils/translations/column-labels";
import { cn } from "@/lib/utils";

interface ShowFieldProps {
  label: string;
  value: unknown;
  renderFn?: (value: unknown) => React.ReactNode;
}

export function ShowField({ label, value, renderFn }: ShowFieldProps) {
  // Manejar el renderizado personalizado si se proporciona una función
  const renderedValue = renderFn ? renderFn(value) : null;

  // Formatear automáticamente si no hay función de renderizado
  const formattedValue = !renderFn ? formatValue(value) : null;

  return (
    <div className="py-2">
      <dt className="text-sm font-medium text-muted-foreground mb-1">{label}</dt>
      <dd 
        className={cn(
          "text-sm text-foreground",
          typeof value === "boolean" && "flex items-center"
        )}
        tabIndex={0} // Para hacer este elemento focusable con teclado
      >
        {renderedValue ?? formattedValue}
      </dd>
    </div>
  );
}

// Función para formatear valores de diferentes tipos
export function formatValue(value: unknown): React.ReactNode {
  // Valor vacío
  if (value === null || value === undefined) {
    return <span className="text-muted-foreground italic">No disponible</span>;
  }

  // Booleanos
  if (typeof value === "boolean") {
    return value ? (
      <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
        Sí
      </span>
    ) : (
      <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
        No
      </span>
    );
  }

  // Fechas
  if (value instanceof Date || 
      (typeof value === "string" && /^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/.test(value))) {
    const date = value instanceof Date ? value : new Date(value);
    return date.toLocaleString();
  }

  // Arreglos
  if (Array.isArray(value)) {
    if (value.length === 0) {
      return <span className="text-muted-foreground italic">Ninguno</span>;
    }
    
    // Si es un array, formatear como lista
    return (
      <ul className="list-disc list-inside">
        {value.map((item: unknown, index) => (
          <li key={index}>{formatValue(item)}</li>
        ))}
      </ul>
    );
  }

  // Objetos
  if (typeof value === "object" && value !== null) {
    try {
      // Detectar si es un objeto con nombre
      if ('name' in value) {
        // Usar type assertion para acceder a la propiedad name
        return (value as {name: string}).name;
      }
      return JSON.stringify(value);
    } catch {
      return <span className="text-muted-foreground italic">[Objeto complejo]</span>;
    }
  }

  // Default: convertir a string
  return formatExportValue(String(value));
}
