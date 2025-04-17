import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { UserForm } from "./user-form";
import { updateUserSchema, User } from "./schema";
import { UserIcon, KeyIcon, ShieldIcon } from "lucide-react";

interface EditUserProps {
  user: User & {
    id: number;
  };
  userRoles?: string[];
  errors?: Record<string, string>;
}

export default function EditUser({ user, userRoles = [], errors = {} }: EditUserProps) {
  const formOptions = {
    title: "Editar Usuario",
    subtitle: `Modificando información de ${user.name}`,
    endpoint: `/users/${user.id}`,
    moduleName: "users",
    isEdit: true,
    recordId: user.id,
    breadcrumbs: [
      {
        title: "Dashboard",
        href: "/dashboard",
      },
      {
        title: "Usuarios",
        href: "/users",
      },
      {
        title: `Editar Usuario: ${user.name}`,
        href: `/users/${user.id}/edit`,
      }
    ],
    defaultTab: "general",
    tabs: [
      { value: "general", label: "Información General", icon: <UserIcon className="h-4 w-4" /> },
      { value: "security", label: "Seguridad", icon: <KeyIcon className="h-4 w-4" /> },
      { value: "roles", label: "Roles y permisos", icon: <ShieldIcon className="h-4 w-4" /> },
    ],
    permissions: {
      edit: "users.edit",
    },
    beforeSubmit: (data: Partial<User>) => {
      // Si no se está cambiando la contraseña, eliminarla del envío
      if (!data.password) {
        const { password, password_confirmation, ...rest } = data;
        return rest;
      }
      return data;
    },
    actions: {
      save: {
        label: "Actualizar Usuario",
        disabledText: "No tienes permisos para editar usuarios",
      },
      cancel: {
        label: "Volver",
        href: "/users",
      },
    },
  };

  // Preparar valores por defecto
  const defaultValues = {
    ...user,
    // Asegurar que campos opcionales estén inicializados correctamente
    password: "",
    password_confirmation: "",
    active: user.active ?? true,
    roles: userRoles,
  };

  return (
    <>
      <Head title={`Editar Usuario - ${user.name}`} />
      <BaseFormPage
        options={formOptions}
        schema={updateUserSchema}
        defaultValues={defaultValues}
        serverErrors={errors}
        FormComponent={UserForm}
      />
    </>
  );
}
