import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { ProviderForm } from "./provider-form";
import { ProviderFormValues, createProviderSchema } from "./schema";
import { z } from "zod";
import { BuildingIcon, UserIcon } from "lucide-react";

interface CreateProviderProps {
  areas?: Array<{
    id: number;
    name: string;
    description?: string;
  }>;
}

export default function CreateProvider({ areas = [] }: CreateProviderProps) {
  // Debug: verificar si tenemos áreas disponibles
  console.log('CreateProvider - Áreas recibidas:', areas);
  
  const formOptions = {
    title: "Crear Proveedor",
    subtitle: "Complete el formulario para registrar un nuevo proveedor en el sistema",
    endpoint: "/providers",
    moduleName: "providers",
    isEdit: false,
    additionalProps: {
      areas: areas
    },
    breadcrumbs: [
      {
        title: "Dashboard",
        href: "/dashboard",
      },
      {
        title: "Proveedores",
        href: "/providers",
      },
      {
        title: "Crear Proveedor",
        href: "/providers/create",
      }
    ],
    defaultTab: "general",
    tabs: [
      { value: "general", label: "Información General", icon: <BuildingIcon className="h-4 w-4" /> },
      { value: "admin", label: "Administrador", icon: <UserIcon className="h-4 w-4" /> },
    ],
    permissions: {
      create: "provider.manage_own_area", // Cambiado para permitir a area_manager crear proveedores
    },
    actions: {
      save: {
        label: "Crear Proveedor",
        disabledText: "No tienes permisos para crear proveedores",
      },
      cancel: {
        label: "Cancelar",
        href: "/providers",
      },
    },
  };

  return (
    <>
      <Head title="Crear Proveedor" />
      <BaseFormPage<ProviderFormValues>
        options={formOptions}
        schema={(createProviderSchema as unknown) as z.ZodType<ProviderFormValues>} // Conversión segura a través de unknown
        defaultValues={{
          name: "",
          rif: "",
          phone: "",
          area_id: 0,
          type: "external",
          active: true,
          user: {
            name: "",
            email: "",
            password: "",
          }
        }}
        FormComponent={ProviderForm}
      />
    </>
  );
}
