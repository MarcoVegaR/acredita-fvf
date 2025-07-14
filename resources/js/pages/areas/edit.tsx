import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { AreaForm } from "./area-form";
import { updateAreaSchema, Area } from "./schema";
import { z } from "zod";

interface EditAreaProps {
  area: Area & {
    id: number;
  };
  errors?: Record<string, string>;
}

export default function EditArea({ area, errors = {} }: EditAreaProps) {
  const formOptions = {
    title: "Editar Área",
    subtitle: `Modificando información de ${area.name}`,
    endpoint: `/areas/${area.id}`,
    moduleName: "areas",
    isEdit: true,
    recordId: area.id,
    breadcrumbs: [
      {
        title: "Dashboard",
        href: "/dashboard",
      },
      {
        title: "Áreas",
        href: "/areas",
      },
      {
        title: `Editar: ${area.name}`,
        href: `/areas/${area.id}/edit`,
      }
    ],
    permissions: {
      edit: "areas.edit",
    },
    actions: {
      save: {
        label: "Actualizar Área",
        disabledText: "No tienes permisos para editar áreas",
      },
      cancel: {
        label: "Volver",
        href: "/areas",
      },
    },
  };

  // Los valores por defecto vienen directamente del objeto area

  return (
    <>
      <Head title={`Editar Área - ${area.name}`} />
      <BaseFormPage<Area>
        options={formOptions}
        schema={updateAreaSchema as unknown as z.ZodType<Area>}
        defaultValues={area}
        serverErrors={errors}
        FormComponent={AreaForm}
      />
    </>
  );
}
