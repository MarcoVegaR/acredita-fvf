import React from "react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { TemplateForm } from "./template-form";
import { templateSchema, defaultLayoutMeta } from "./schema";
import { route } from "ziggy-js";
import { preprocessTemplateFormData, FormData } from "./helpers";

// Define las props para la vista de creación
interface CreateTemplateProps {
  events: Array<{
    id: number;
    name: string;
  }>;
}

export default function CreateTemplate({ events }: CreateTemplateProps) {
  const formOptions = {
    title: "Crear Nueva Plantilla",
    subtitle: "Ingrese los datos para crear una nueva plantilla de credenciales",
    endpoint: route("templates.store"),
    moduleName: "templates",
    isEdit: false,
    breadcrumbs: [
      { title: "Dashboard", href: route("dashboard") },
      { title: "Plantillas", href: route("templates.index") },
      { title: "Crear Nueva", href: "#" },
    ],
    permissions: {
      create: "templates.create",
    },
    // Función para preprocesar los datos antes de enviarlos al servidor
    beforeSubmit: (formData: FormData) => {
      console.log('Antes de procesar:', formData);
      
      // Eliminar campo template_file si no hay archivo seleccionado
      if (!formData.template_file) {
        delete formData.template_file;
      }
      
      const processedData = preprocessTemplateFormData(formData);
      console.log('Después de procesar:', processedData);
      return processedData;
    },
    actions: {
      save: {
        label: "Crear Plantilla",
        disabledText: "No tienes permisos para crear plantillas",
      },
      cancel: {
        label: "Cancelar",
        href: route("templates.index"),
      },
    },
    // Pasamos las opciones específicas que necesita el TemplateForm
    // a través del contexto del formulario
    events,
    defaultLayout: defaultLayoutMeta,
    isUpdate: false,
  };

  return (
    <>
      <BaseFormPage
        options={formOptions}
        schema={templateSchema}
        defaultValues={{
          event_id: 0, // Usando 0 como valor inicial para event_id en lugar de null
          name: "",
          layout_meta: defaultLayoutMeta,
          is_default: false,
        }}
        serverErrors={{}} // Opcional, para errores del servidor
        FormComponent={TemplateForm}
      />
    </>
  );
}
