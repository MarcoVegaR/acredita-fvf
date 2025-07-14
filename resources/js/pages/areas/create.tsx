import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { AreaForm } from "./area-form";
import { createAreaSchema, FormArea, Area } from "./schema";
import { z } from "zod";

export default function CreateArea() {
  const formOptions = {
    title: "Crear Área",
    subtitle: "Complete el formulario para registrar una nueva área en el sistema",
    endpoint: "/areas",
    moduleName: "areas",
    isEdit: false,
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
        title: "Crear Área",
        href: "/areas/create",
      }
    ],
    permissions: {
      create: "areas.create",
    },
    actions: {
      save: {
        label: "Crear Área",
        disabledText: "No tienes permisos para crear áreas",
      },
      cancel: {
        label: "Cancelar",
        href: "/areas",
      },
    },
  };

  const defaultValues: FormArea = {
    code: "",
    name: "",
    description: "",
    active: true,
  };

  return (
    <>
      <Head title="Crear Área" />
      <BaseFormPage<Area>
        options={formOptions}
        schema={createAreaSchema as unknown as z.ZodType<Area>}
        defaultValues={defaultValues}
        FormComponent={AreaForm}
      />
    </>
  );
}
