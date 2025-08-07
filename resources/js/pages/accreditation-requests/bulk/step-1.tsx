import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { BulkStep1Form } from "../bulk-form";
import { bulkStep1Schema, Event } from "../bulk-schema";

interface BulkStep1Props {
  events: Event[];
}

export default function BulkStep1({ events }: BulkStep1Props) {
  const formOptions = {
    title: "Solicitud Masiva de Acreditación",
    subtitle: "Paso 1: Seleccionar Evento",
    endpoint: "/accreditation-requests/bulk/step-1",
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
        title: "Solicitud masiva",
        href: "/accreditation-requests/bulk",
      },
      {
        title: "Paso 1: Evento",
        href: "/accreditation-requests/bulk/step-1",
      },
    ],
    permissions: {
      create: "accreditation_request.create",
    },
    actions: {
      save: {
        label: "Siguiente",
        disabledText: "No tienes permisos para crear solicitudes de acreditación",
      },
      cancel: {
        label: "Cancelar",
        href: "/accreditation-requests",
      },
    },
    wizardConfig: {
      currentStep: 1,
      totalSteps: 4,
      steps: [
        { label: "Evento", isActive: true, isComplete: false },
        { label: "Colaboradores", isActive: false, isComplete: false },
        { label: "Zonas", isActive: false, isComplete: false },
        { label: "Confirmación", isActive: false, isComplete: false },
      ]
    }
  };

  return (
    <>
      <Head title="Solicitud Masiva - Paso 1" />
      <BaseFormPage
        options={formOptions}
        schema={bulkStep1Schema}
        defaultValues={{
          event_id: "",
        }}
        FormComponent={() => (
          <BulkStep1Form events={events} />
        )}
      />
    </>
  );
}
