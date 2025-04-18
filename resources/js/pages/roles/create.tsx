import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { RoleForm } from "./role-form";
import { createRoleSchema } from "./schema";
import { ShieldIcon } from "lucide-react";

interface CreateRoleProps {
  permissions: { 
    name: string;
    module: string;
    description?: string;
  }[];
}

export default function CreateRole({ permissions = [] }: CreateRoleProps) {
  const formOptions = {
    title: "Crear Rol",
    subtitle: "Complete el formulario para registrar un nuevo rol en el sistema",
    endpoint: "/roles",
    moduleName: "roles",
    isEdit: false,
    breadcrumbs: [
      {
        title: "Dashboard",
        href: "/dashboard",
      },
      {
        title: "Roles",
        href: "/roles",
      },
      {
        title: "Crear Rol",
        href: "/roles/create",
      }
    ],
    defaultTab: "general",
    tabs: [
      { value: "general", label: "Información General", icon: <ShieldIcon className="h-4 w-4" /> },
      { value: "permissions", label: "Permisos", icon: <ShieldIcon className="h-4 w-4" /> },
    ],
    permissions: {
      create: "roles.create",
    },
    actions: {
      save: {
        label: "Crear Rol",
        disabledText: "No tienes permisos para crear roles",
      },
      cancel: {
        label: "Cancelar",
        href: "/roles",
      },
    },
  };

  return (
    <>
      <Head title="Crear Rol" />
      <BaseFormPage
        options={formOptions}
        schema={createRoleSchema}
        defaultValues={{
          name: "",
          guard_name: "web",
          permissions: [],
        }}
        FormComponent={(props) => <RoleForm {...props} permissions={permissions} />}
      />
    </>
  );
}
