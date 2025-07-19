import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { Step2Form } from "../wizard-form";
import { step2Schema, Event, Employee } from "../schema";

// Propiedades que recibe el componente desde el backend
interface Step2Props {
  event: Event;
  employees: Employee[];
  selectedEmployeeId?: string;
}

export default function Step2({ event, employees, selectedEmployeeId }: Step2Props) {
  const formOptions = {
    title: "Nueva solicitud de acreditación",
    subtitle: "Paso 2: Selección de empleado",
    endpoint: "/accreditation-requests/create/step-2",
    moduleName: "accreditation-requests",
    isEdit: false,
    breadcrumbs: [
      {
        title: "Dashboard",
        href: "/dashboard",
      },
      {
        title: "Solicitudes de acreditación",
        href: "/accreditation-requests",
      },
      {
        title: "Nueva solicitud",
        href: "/accreditation-requests/create",
      },
      {
        title: "Paso 2: Empleado",
        href: "/accreditation-requests/create/step-2",
      },
    ],
    permissions: {
      create: "accreditation_request.create",
    },
    actions: {
      save: {
        label: "Continuar al Paso 3",
        disabledText: "No tienes permisos para crear solicitudes de acreditación",
      },
      cancel: {
        label: "Volver",
        href: "/accreditation-requests/create/step-1",
      },
    },
    // Configuración para mostrar los pasos del wizard en la parte superior
    wizardConfig: {
      currentStep: 2,
      totalSteps: 4,
      steps: [
        { label: "Evento", isActive: true, isComplete: true },
        { label: "Empleado", isActive: true, isComplete: false },
        { label: "Zonas", isActive: false, isComplete: false },
        { label: "Confirmación", isActive: false, isComplete: false },
      ]
    }
  };

  return (
    <>
      <Head title="Nueva solicitud de acreditación - Paso 2" />
      <BaseFormPage
        options={formOptions}
        schema={step2Schema}
        defaultValues={{
          employee_id: selectedEmployeeId || "",
        }}
        FormComponent={() => (
          <Step2Form event={event} employees={employees} />
        )}
      />
    </>
  );
}
