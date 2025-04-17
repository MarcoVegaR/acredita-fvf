import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { UserForm } from "./user-form";
import { createUserSchema } from "./schema";
import { UserIcon, KeyIcon, ShieldIcon } from "lucide-react";

export default function CreateUser() {
  const formOptions = {
    title: "Crear Usuario",
    subtitle: "Complete el formulario para registrar un nuevo usuario en el sistema",
    endpoint: "/users",
    moduleName: "users",
    isEdit: false,
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
        title: "Crear Usuario",
        href: "/users/create",
      }
    ],
    defaultTab: "general",
    tabs: [
      { value: "general", label: "Información General", icon: <UserIcon className="h-4 w-4" /> },
      { value: "security", label: "Seguridad", icon: <KeyIcon className="h-4 w-4" /> },
      { value: "roles", label: "Roles y permisos", icon: <ShieldIcon className="h-4 w-4" /> },
    ],
    permissions: {
      create: "users.create",
    },
    actions: {
      save: {
        label: "Crear Usuario",
        disabledText: "No tienes permisos para crear usuarios",
      },
      cancel: {
        label: "Cancelar",
        href: "/users",
      },
    },
  };

  return (
    <>
      <Head title="Crear Usuario" />
      <BaseFormPage
        options={formOptions}
        schema={createUserSchema}
        defaultValues={{
          name: "",
          email: "",
          password: "",
          password_confirmation: "",
          active: true,
          roles: [],
        }}
        FormComponent={UserForm}
      />
    </>
  );
}
