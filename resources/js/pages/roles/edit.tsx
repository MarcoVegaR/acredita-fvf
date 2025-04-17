import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { RoleForm } from "./role-form";
import { updateRoleSchema, Role } from "./schema";
import { ShieldIcon } from "lucide-react";

interface EditRoleProps {
  role: Role;
  rolePermissions?: string[];
  permissions: { 
    name: string;
    module: string;
    description?: string;
  }[];
  errors?: Record<string, string>;
}

export default function EditRole({ role, rolePermissions = [], permissions = [], errors = {} }: EditRoleProps) {
  const formOptions = {
    title: "Editar Rol",
    subtitle: `Modificando información de rol: ${role.name}`,
    endpoint: `/roles/${role.id}`,
    moduleName: "roles",
    isEdit: true,
    recordId: role.id,
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
        title: `Editar Rol: ${role.name}`,
        href: `/roles/${role.id}/edit`,
      }
    ],
    defaultTab: "general",
    tabs: [
      { value: "general", label: "Información General", icon: <ShieldIcon className="h-4 w-4" /> },
      { value: "permissions", label: "Permisos", icon: <ShieldIcon className="h-4 w-4" /> },
    ],
    permissions: {
      edit: "roles.edit",
    },
    actions: {
      save: {
        label: "Actualizar Rol",
        disabledText: "No tienes permisos para editar roles",
      },
      cancel: {
        label: "Volver",
        href: "/roles",
      },
    },
  };

  // Preparar valores por defecto
  const defaultValues = {
    ...role,
    permissions: rolePermissions,
  };

  return (
    <>
      <Head title={`Editar Rol - ${role.name}`} />
      <BaseFormPage
        options={formOptions}
        schema={updateRoleSchema}
        defaultValues={defaultValues}
        serverErrors={errors}
        // @ts-expect-error - TypeScript no puede inferir correctamente la compatibilidad de tipos entre el FormComponent y las opciones
        FormComponent={(props) => <RoleForm {...props} permissions={permissions} />}
      />
    </>
  );
}
