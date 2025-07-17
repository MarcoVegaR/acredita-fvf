import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { EmployeeForm } from "./employee-form";
import { updateEmployeeSchema, Employee, Provider } from "./schema";
import { UserIcon, ClipboardList } from "lucide-react";

interface EditEmployeeProps {
  employee: Employee;
  providers?: Provider[];
  errors?: Record<string, string>;
}

export default function EditEmployee({ employee, providers = [], errors = {} }: EditEmployeeProps) {
  const formOptions = {
    title: "Editar Empleado",
    subtitle: `Modificando información de ${employee.first_name} ${employee.last_name}`,
    endpoint: `/employees/${employee.uuid}`,
    moduleName: "employees",
    isEdit: true,
    recordId: employee.uuid,
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
        title: `Editar: ${employee.first_name} ${employee.last_name}`,
        href: `/employees/${employee.uuid}/edit`,
      }
    ],
    defaultTab: "general",
    tabs: [
      { value: "general", label: "Datos Generales", icon: <UserIcon className="h-4 w-4" /> },
      { value: "photo", label: "Fotografía", icon: <ClipboardList className="h-4 w-4" /> },
    ],
    permissions: {
      // Siguiendo la estructura de permisos similar a proveedores (ver memory)
      edit: "employee.manage", // Permiso general para administrar empleados
      view: "employee.view", // Permiso para ver empleados
    },
    beforeSubmit: (data: Partial<Employee>) => {
      // Convertir la foto en base64 al campo correcto para el backend
      if (data.croppedPhoto && !data.croppedPhoto.startsWith('/storage/')) {
        // Si es una nueva imagen base64, la procesamos
        data.photo = data.croppedPhoto;
      }
      
      // Eliminamos los campos temporales que no deben ir al servidor
      delete data.croppedPhoto;
      
      return data;
    },
    actions: {
      save: {
        label: "Actualizar Empleado",
        disabledText: "No tienes permisos para editar empleados",
      },
      cancel: {
        label: "Volver",
        href: "/employees",
      },
    },
  };

  // Preparar valores por defecto
  const defaultValues = {
    ...employee,
    // Configurar la imagen para visualización si existe
    croppedPhoto: employee.photo_path 
      ? `/storage/${employee.photo_path}` 
      : null,
  };

  return (
    <>
      <Head title={`Editar Empleado - ${employee.first_name} ${employee.last_name}`} />
      <BaseFormPage
        options={formOptions}
        schema={updateEmployeeSchema}
        defaultValues={defaultValues}
        serverErrors={errors}
        FormComponent={(props) => <EmployeeForm {...props} availableProviders={providers} />}
      />
    </>
  );
}
