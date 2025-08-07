import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { BulkStep2Form } from "../bulk-form";
import { bulkStep2Schema, Event, EmployeeWithZones } from "../bulk-schema";

interface BulkStep2Props {
  event: Event;
  employees: EmployeeWithZones[];
}

export default function BulkStep2({ event, employees }: BulkStep2Props) {
  const formOptions = {
    title: "Solicitud Masiva de Acreditación",
    subtitle: "Paso 2: Seleccionar Colaboradores",
    endpoint: "/accreditation-requests/bulk/step-2",
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
        title: "Paso 2: Colaboradores",
        href: "/accreditation-requests/bulk/step-2",
      },
    ],
    permissions: {
      create: "accreditation_request.create",
    },
    actions: {
      save: {
        label: "Siguiente",
        disabledText: "Debe seleccionar al menos un empleado",
      },
      cancel: {
        label: "Anterior",
        href: "/accreditation-requests/bulk/step-1",
      },
    },
    wizardConfig: {
      currentStep: 2,
      totalSteps: 4,
      steps: [
        { label: "Evento", isActive: true, isComplete: true },
        { label: "Colaboradores", isActive: true, isComplete: false },
        { label: "Zonas", isActive: false, isComplete: false },
        { label: "Confirmación", isActive: false, isComplete: false },
      ]
    }
  };

  return (
    <>
      <Head title="Solicitud Masiva - Paso 2" />
      <BaseFormPage
        options={formOptions}
        schema={bulkStep2Schema}
        defaultValues={{
          event_id: event.id.toString(),
          employee_ids: [],
        }}
        FormComponent={() => (
          <BulkStep2Form event={event} employees={employees} />
        )}
      />
    </>
  );
}
