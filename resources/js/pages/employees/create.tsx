import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { EmployeeForm } from "./employee-form";
import { createEmployeeSchema } from "./schema";
import { UserIcon, ClipboardList } from "lucide-react";

import { Provider } from "./schema";

interface CreateEmployeeProps {
  providers?: Provider[];
  errors?: Record<string, string>;
}

export default function CreateEmployee({ providers = [], errors = {} }: CreateEmployeeProps) {
  const formOptions = {
    title: "Registrar Empleado",
    subtitle: "Complete el formulario para registrar un nuevo empleado en el sistema",
    endpoint: "/employees",
    moduleName: "employees",
    isEdit: false,
    breadcrumbs: [
      {
        title: "Dashboard",
        href: "/dashboard",
      },
      {
        title: "Empleados",
        href: "/employees",
      },
      {
        title: "Registrar Empleado",
        href: "/employees/create",
      }
    ],
    defaultTab: "general",
    tabs: [
      { value: "general", label: "Datos Generales", icon: <UserIcon className="h-4 w-4" /> },
      { value: "photo", label: "Fotografía", icon: <ClipboardList className="h-4 w-4" /> },
    ],
    permissions: {
      create: "employee.manage", // Permiso para administrar empleados (similar a provider.manage)
    },
    beforeSubmit: (data: Record<string, unknown>) => {
      // Convertir la foto en base64 al campo correcto para el backend
      if (data.croppedPhoto) {
        // Backend espera el campo 'photo', no 'photo_base64'
        data.photo = data.croppedPhoto;
        
        // Eliminamos los campos temporales que no deben ir al servidor
        delete data.croppedPhoto;
      }
      return data;
    },
    actions: {
      save: {
        label: "Registrar Empleado",
        disabledText: "No tienes permisos para registrar empleados",
      },
      cancel: {
        label: "Cancelar",
        href: "/employees",
      },
    },
  };

  return (
    <>
      <Head title="Registrar Empleado" />
      <BaseFormPage
        options={formOptions}
        schema={createEmployeeSchema}
        defaultValues={{
          provider_id: undefined as unknown as number,
          document_type: "",
          document_number: "",
          first_name: "",
          last_name: "",
          function: "",
          active: true,
          croppedPhoto: null,
        }}
        serverErrors={errors}
        FormComponent={(props) => <EmployeeForm {...props} availableProviders={providers} />}
      />
    </>
  );
}
