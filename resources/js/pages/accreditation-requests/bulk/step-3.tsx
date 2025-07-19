import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { BulkStep3Form } from "../bulk-form";
import { bulkStep3Schema, Event, EmployeeWithZones, Zone, ZoneTemplate } from "../bulk-schema";

interface BulkStep3Props {
  event: Event;
  selectedEmployees: EmployeeWithZones[];
  zones: Zone[];
  templates?: ZoneTemplate[];
}

export default function BulkStep3({ event, selectedEmployees, zones }: BulkStep3Props) {
  const formOptions = {
    title: "Solicitud Masiva de Acreditación",
    subtitle: "Paso 3: Configurar Zonas de Acceso",
    endpoint: "/accreditation-requests/bulk/step-3",
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
        title: "Paso 3: Zonas",
        href: "/accreditation-requests/bulk/step-3",
      },
    ],
    permissions: {
      create: "accreditation_request.create",
    },
    actions: {
      save: {
        label: "Siguiente",
        disabledText: "Debe configurar zonas para todos los empleados",
      },
      cancel: {
        label: "Anterior",
        href: "/accreditation-requests/bulk/step-2",
      },
    },
    wizardConfig: {
      currentStep: 3,
      totalSteps: 4,
      steps: [
        { label: "Evento", isActive: true, isComplete: true },
        { label: "Empleados", isActive: true, isComplete: true },
        { label: "Zonas", isActive: true, isComplete: false },
        { label: "Confirmación", isActive: false, isComplete: false },
      ]
    }
  };

  return (
    <>
      <Head title="Solicitud Masiva - Paso 3" />
      <BaseFormPage
        options={formOptions}
        schema={bulkStep3Schema}
        defaultValues={{
          event_id: event.id.toString(),
          employee_zones: {},
        }}
        FormComponent={() => (
          <BulkStep3Form 
            event={event} 
            selectedEmployees={selectedEmployees}
            zones={zones}
          />
        )}
      />
    </>
  );
}
