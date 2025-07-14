import React from "react";
import { ShowField } from "./show-field";
import { SectionDef, Entity } from "./base-show-page";
import { getColumnLabel } from "@/utils/translations/column-labels";
import { motion } from "framer-motion";
import { usePermissions } from "@/hooks/usePermissions";

interface ShowSectionProps<T extends Entity> {
  section: SectionDef<T> & { className?: string };
  entity: T;
  moduleName?: string;
}

export function ShowSection<T extends Entity>({ 
  section, 
  entity, 
  moduleName 
}: ShowSectionProps<T>) {
  const { can } = usePermissions();

  // Si la sección tiene contenido personalizado y no fields, manejarlo de forma especial
  if ('custom' in section) {
    // Si hay contenido personalizado, lo devolvemos directamente
    if (!section.fields || section.fields.length === 0) {
      return (
        <motion.section
          role="group"
          aria-labelledby={`section-title-${section.title.toLowerCase().replace(/\s+/g, "-")}`}
          className={section.className || "border rounded-lg overflow-hidden bg-card"}
          initial={{ opacity: 0, y: 20 }}
          animate={{ opacity: 1, y: 0 }}
          transition={{ duration: 0.3 }}
        >
          <div className="bg-muted px-4 py-3 border-b">
            <h2 
              id={`section-title-${section.title.toLowerCase().replace(/\s+/g, "-")}`}
              className="text-lg font-medium"
            >
              {section.title}
            </h2>
          </div>
          <div className="p-4">
            {typeof section.custom === 'function' && section.custom({ entity })}
          </div>
        </motion.section>
      );
    }
  }

  // Asegurarse de que fields exista antes de filtrar
  if (!section.fields || !Array.isArray(section.fields)) {
    console.warn(`La sección "${section.title}" no tiene un array de fields válido:`, section);
    return null;
  }

  // Procesamiento de campos para mostrar solo los que el usuario tiene permiso
  const visibleFields = section.fields.filter(field => {
    const fieldDef = typeof field === "object" ? field : { key: field };
    return !fieldDef.permission || can(fieldDef.permission);
  });

  // Si no hay campos visibles, no mostrar la sección
  if (visibleFields.length === 0 && !('custom' in section)) {
    return null;
  }

  return (
    <motion.section
      role="group"
      aria-labelledby={`section-title-${section.title.toLowerCase().replace(/\s+/g, "-")}`}
      className={section.className || "border rounded-lg overflow-hidden bg-card"}
      initial={{ opacity: 0, y: 20 }}
      animate={{ opacity: 1, y: 0 }}
      transition={{ duration: 0.3 }}
    >
      {/* Encabezado de la sección */}
      <div className="bg-muted px-4 py-3 border-b">
        <h2 
          id={`section-title-${section.title.toLowerCase().replace(/\s+/g, "-")}`}
          className="text-lg font-medium"
        >
          {section.title}
        </h2>
      </div>

      {/* Contenido de la sección - usando definition list para mejor semántica */}
      <dl className="grid grid-cols-1 md:grid-cols-2 gap-4 p-4">
        {visibleFields.map((field, index) => {
          // Determinar si el campo es una string simple o un objeto complejo
          const isFieldObject = typeof field === "object";
          const fieldKey = isFieldObject ? field.key : field;
          const fieldLabel = isFieldObject && field.label 
            ? field.label 
            : (moduleName ? getColumnLabel(moduleName, fieldKey.toString()) : fieldKey.toString());
          const renderFn = isFieldObject ? field.render : undefined;

          return (
            <ShowField
              key={index}
              label={fieldLabel}
              value={entity[fieldKey]}
              renderFn={renderFn ? (value) => renderFn(value, entity) : undefined}
            />
          );
        })}
      </dl>
    </motion.section>
  );
}
