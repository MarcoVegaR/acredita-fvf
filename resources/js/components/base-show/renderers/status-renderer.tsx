import React from "react";
import { CheckCircle, XCircle } from "lucide-react";

interface StatusRendererProps {
  value: boolean;
  positiveLabel?: string;
  negativeLabel?: string;
  type?: "badge" | "icon";
}

/**
 * Componente reutilizable para renderizar estados booleanos
 */
export const StatusRenderer: React.FC<StatusRendererProps> = ({ 
  value, 
  positiveLabel = "Activo", 
  negativeLabel = "Inactivo",
  type = "badge"
}) => {
  if (type === "icon") {
    return value 
      ? <span className="flex items-center text-green-600"><CheckCircle className="w-4 h-4 mr-1" /> {positiveLabel}</span>
      : <span className="flex items-center text-amber-600"><XCircle className="w-4 h-4 mr-1" /> {negativeLabel}</span>;
  }

  return (
    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${
      value ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800"
    }`}>
      {value ? positiveLabel : negativeLabel}
    </span>
  );
};
