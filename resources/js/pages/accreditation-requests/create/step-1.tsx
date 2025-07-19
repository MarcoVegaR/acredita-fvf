import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { Step1Form } from "../wizard-form";
import { step1Schema, Event } from "../schema";

// Propiedades que recibe el componente desde el backend
interface Step1Props {
  events: Event[];
  selectedEventId?: string;
}

export default function Step1({ events, selectedEventId }: Step1Props) {
  const formOptions = {
    title: "Nueva solicitud de acreditación",
    subtitle: "Paso 1: Selección de evento",
    endpoint: "/accreditation-requests/create/step-1",
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
        title: "Paso 1: Evento",
        href: "/accreditation-requests/create/step-1",
      },
    ],
    permissions: {
      create: "accreditation_request.create",
    },
    actions: {
      save: {
        label: "Continuar al Paso 2",
        disabledText: "No tienes permisos para crear solicitudes de acreditación",
      },
      cancel: {
        label: "Cancelar",
        href: "/accreditation-requests",
      },
    },
    // Configuración para mostrar los pasos del wizard en la parte superior
    wizardConfig: {
      currentStep: 1,
      totalSteps: 4,
      steps: [
        { label: "Evento", isActive: true, isComplete: false },
        { label: "Empleado", isActive: false, isComplete: false },
        { label: "Zonas", isActive: false, isComplete: false },
        { label: "Confirmación", isActive: false, isComplete: false },
      ]
    }
  };

  return (
    <>
      <Head title="Nueva solicitud de acreditación - Paso 1" />
      <BaseFormPage
        options={formOptions}
        schema={step1Schema}
        defaultValues={{
          event_id: selectedEventId || "",
        }}
        FormComponent={() => (
          <Step1Form events={events} />
        )}
      />
    </>
  );
}
