import React from "react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { Template, templateSchema } from "./schema";
import { TemplateForm } from "./template-form";
import { route } from "ziggy-js";
import { preprocessTemplateFormData, FormData } from "./helpers";

// Usando FormData de helpers.ts para los datos del formulario

// Define las props para la vista de edición
interface EditTemplateProps {
  template: Template;
  events: Array<{
    id: number;
    name: string;
  }>;
}

export default function EditTemplate({ template, events }: EditTemplateProps) {
  const formOptions = {
    title: `Editar Plantilla: ${template.name}`,
    subtitle: `Evento: ${template.event?.name}`,
    endpoint: route("templates.update", { template: template.uuid }),
    moduleName: "templates",
    // Enviamos siempre POST y añadimos _method="put" en beforeSubmit
    isEdit: false,
    recordId: template.uuid,
    breadcrumbs: [
      { title: "Dashboard", href: route("dashboard") },
      { title: "Plantillas", href: route("templates.index") },
      { title: template.name, href: route("templates.show", { template: template.uuid }) },
      { title: "Editar", href: "#" },
    ],
    permissions: {
      edit: "templates.edit",
    },
    // Función para preprocesar los datos antes de enviarlos al servidor
    beforeSubmit: (formData: FormData) => {
      // Si se está adjuntando un archivo o simplemente queremos mantener PUT semántico
      formData._method = 'put';
      
      const processedData = preprocessTemplateFormData(formData);
      return processedData;
    },
    actions: {
      save: {
        label: "Guardar Cambios",
        disabledText: "No tienes permisos para editar plantillas",
      },
      cancel: {
        label: "Cancelar",
        href: route("templates.index"),
      },
    },
    // Pasamos las opciones específicas que necesita el TemplateForm
    // a través del contexto del formulario
    events,
    templateFile: template.file_url,
    isUpdate: true
  };

  return (
    <>
      <BaseFormPage
        options={formOptions}
        schema={templateSchema}
        defaultValues={{
          event_id: template.event_id,
          name: template.name,
          layout_meta: template.layout_meta,
        }}
        serverErrors={{}} // Opcional, para errores del servidor
        FormComponent={TemplateForm}
      />
    </>
  );
}
