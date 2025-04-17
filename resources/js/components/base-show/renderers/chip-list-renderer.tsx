import React from "react";

interface ChipListRendererProps {
  items: string[];
  emptyMessage?: string;
  color?: "blue" | "green" | "red" | "gray" | "purple";
}

/**
 * Componente reutilizable para renderizar listas de elementos como chips/etiquetas
 */
export const ChipListRenderer: React.FC<ChipListRendererProps> = ({ 
  items, 
  emptyMessage = "No hay elementos", 
  color = "blue" 
}) => {
  const colorClasses = {
    blue: "bg-blue-100 text-blue-800",
    green: "bg-green-100 text-green-800",
    red: "bg-red-100 text-red-800",
    gray: "bg-gray-100 text-gray-800",
    purple: "bg-purple-100 text-purple-800",
  };
  
  const colorClass = colorClasses[color];
  
  return (
    <div className="flex flex-wrap gap-1">
      {items && items.length > 0 ? (
        items.map((item, index) => (
          <span 
            key={index} 
            className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${colorClass}`}
          >
            {item}
          </span>
        ))
      ) : (
        <span className="text-muted-foreground italic">{emptyMessage}</span>
      )}
    </div>
  );
};
