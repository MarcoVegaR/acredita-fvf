import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { ProviderForm } from "./provider-form";
import { updateProviderSchema, Provider, ProviderFormValues } from "./schema";
import { z } from "zod";
import { BuildingIcon, UserIcon } from "lucide-react";

interface EditProviderProps {
  provider: Provider & {
    id: number;
    uuid: string;
  };
  areas?: Array<{
    id: number;
    name: string;
    description?: string;
  }>;
  errors?: Record<string, string>;
}

export default function EditProvider({ provider, areas = [], errors = {} }: EditProviderProps) {
  // Determinar las pestañas según el tipo de proveedor
  const tabs = [
    { value: "general", label: "Información General", icon: <BuildingIcon className="h-4 w-4" /> }
  ];

  // Añadir la pestaña de administrador solo si es proveedor externo
  if (provider.type === 'external') {
    tabs.push({ value: "admin", label: "Administrador", icon: <UserIcon className="h-4 w-4" /> });
  }

  // Debug: verificar si tenemos áreas disponibles
  console.log('EditProvider - Áreas recibidas:', areas);
  
  const formOptions = {
    title: "Editar Proveedor",
    subtitle: `Modificando información de ${provider.name}`,
    endpoint: `/providers/${provider.uuid}`,
    moduleName: "providers",
    isEdit: true,
    recordId: provider.id,
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
        title: `Editar Proveedor: ${provider.name}`,
        href: `/providers/${provider.uuid}/edit`,
      }
    ],
    defaultTab: "general",
    tabs: tabs,
    permissions: {
      edit: "provider.manage_own_area", // Cambiado para permitir a area_manager editar proveedores
    },
    // Proceso previo al envío del formulario
    beforeSubmit: (data: ProviderFormValues) => {
      // Si es edición y el usuario no cambió la contraseña, eliminarla del envío
      if (provider.type === 'external' && data.user && !data.user.password) {
        if (data.user) {
          // eslint-disable-next-line @typescript-eslint/no-unused-vars
          const { password, ...userRest } = data.user;
          data.user = userRest;
        }
      }
      return data; // Ya tipado correctamente como ProviderFormValues
    },
    actions: {
      save: {
        label: "Actualizar Proveedor",
        disabledText: "No tienes permisos para editar proveedores",
      },
      cancel: {
        label: "Cancelar",
        href: `/providers`,
      },
    },
  };

  // Preparar valores por defecto para el formulario
  const defaultValues: ProviderFormValues = {
    ...provider,
    // Asegurar que user exista si es proveedor externo
    user: provider.type === 'external' && provider.user ? {
      id: provider.user.id,
      name: provider.user.name || '',  // Garantizamos valores no undefined
      email: provider.user.email || '', // Garantizamos valores no undefined
      password: ''
    } : undefined
  };

  return (
    <>
      <Head title={`Editar Proveedor: ${provider.name}`} />
      <BaseFormPage<ProviderFormValues>
        options={formOptions}
        schema={(updateProviderSchema as unknown) as z.ZodType<ProviderFormValues>} // Conversión segura a través de unknown
        defaultValues={defaultValues}
        FormComponent={ProviderForm}
        serverErrors={errors}
      />
    </>
  );
}
