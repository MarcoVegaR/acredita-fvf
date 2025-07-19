import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { Step3Form } from "../wizard-form";
import { step3Schema, Event, Employee, Zone } from "../schema";

// Propiedades que recibe el componente desde el backend
interface Step3Props {
  event: Event;
  employee: Employee;
  zones: Zone[];
  selectedZoneIds?: string[];
}

export default function Step3({ event, employee, zones, selectedZoneIds }: Step3Props) {
  const formOptions = {
    title: "Nueva solicitud de acreditación",
    subtitle: "Paso 3: Selección de zonas",
    endpoint: "/accreditation-requests/create/step-3",
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
        title: "Paso 3: Zonas",
        href: "/accreditation-requests/create/step-3",
      },
    ],
    permissions: {
      create: "accreditation_request.create",
    },
    actions: {
      save: {
        label: "Continuar al Paso 4",
        disabledText: "No tienes permisos para crear solicitudes de acreditación",
      },
      cancel: {
        label: "Volver",
        href: "/accreditation-requests/create/step-2",
      },
    },
    // Configuración para mostrar los pasos del wizard en la parte superior
    wizardConfig: {
      currentStep: 3,
      totalSteps: 4,
      steps: [
        { label: "Evento", isActive: true, isComplete: true },
        { label: "Empleado", isActive: true, isComplete: true },
        { label: "Zonas", isActive: true, isComplete: false },
        { label: "Confirmación", isActive: false, isComplete: false },
      ]
    }
  };

  return (
    <>
      <Head title="Nueva solicitud de acreditación - Paso 3" />
      <BaseFormPage
        options={formOptions}
        schema={step3Schema}
        defaultValues={{
          zones: selectedZoneIds || [],
        }}
        FormComponent={() => (
          <Step3Form 
            event={event} 
            employee={employee}
            zones={zones}
          />
        )}
      />
    </>
  );
}
