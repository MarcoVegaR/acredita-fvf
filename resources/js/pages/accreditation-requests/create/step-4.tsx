import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { Step4Form } from "../wizard-form";
import { step4Schema, Event, Employee, Zone } from "../schema";

// Propiedades que recibe el componente desde el backend
interface Step4Props {
  event: Event;
  employee: Employee;
  selectedZones: Zone[];
  notes?: string;
}

export default function Step4({ event, employee, selectedZones, notes }: Step4Props) {
  const formOptions = {
    title: "Nueva solicitud de acreditación",
    subtitle: "Paso 4: Confirmación",
    endpoint: "/accreditation-requests/create/step-4",
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
        title: "Paso 4: Confirmación",
        href: "/accreditation-requests/create/step-4",
      },
    ],
    permissions: {
      create: "accreditation_request.create",
    },
    actions: {
      save: {
        label: "Enviar solicitud",
        disabledText: "No tienes permisos para crear solicitudes de acreditación",
      },
      cancel: {
        label: "Volver",
        href: "/accreditation-requests/create/step-3",
      },
    },
    // Configuración para mostrar los pasos del wizard en la parte superior
    wizardConfig: {
      currentStep: 4,
      totalSteps: 4,
      steps: [
        { label: "Evento", isActive: true, isComplete: true },
        { label: "Empleado", isActive: true, isComplete: true },
        { label: "Zonas", isActive: true, isComplete: true },
        { label: "Confirmación", isActive: true, isComplete: false },
      ]
    }
  };

  return (
    <>
      <Head title="Nueva solicitud de acreditación - Paso 4" />
      <BaseFormPage
        options={formOptions}
        schema={step4Schema}
        defaultValues={{
          notes: notes || "",
          confirm: false,
        }}
        FormComponent={() => (
          <Step4Form 
            event={event} 
            employee={employee}
            selectedZones={selectedZones}
          />
        )}
      />
    </>
  );
}
