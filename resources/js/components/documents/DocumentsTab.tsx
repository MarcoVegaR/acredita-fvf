import React from "react";
import { DocumentsSection } from "./DocumentsSection";
import { DocumentType } from "@/types";
import { getColumnLabel } from "@/utils/translations/column-labels";

interface DocumentsTabProps {
  module: string;
  entityId: number;
  types: DocumentType[];
  permissions: string[];
  title?: string;
  readOnly?: boolean; // Modo solo lectura para la vista show
}

/**
 * Componente reutilizable para integrar documentos en cualquier vista de detalle
 * Se puede usar como contenido de una pestaña o como parte de una sección
 */
export function DocumentsTab({
  module,
  entityId,
  types,
  permissions,
  title,
  readOnly = true // Por defecto es modo solo lectura para vistas de detalle
}: DocumentsTabProps) {
  const sectionTitle = title || getColumnLabel('documents', 'section_title');

  return (
    <div className="space-y-4">
      {title && (
        <h3 className="text-lg font-medium">{sectionTitle}</h3>
      )}
      
      <DocumentsSection
        module={module}
        entityId={entityId}
        types={types}
        permissions={permissions}
        readOnly={readOnly} // Pasamos el modo solo lectura a DocumentsSection
      />
    </div>
  );
}
