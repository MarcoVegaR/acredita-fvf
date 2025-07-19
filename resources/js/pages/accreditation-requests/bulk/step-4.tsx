import React from "react";
import { Head } from "@inertiajs/react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { BulkStep4Form } from "../bulk-form";
import { bulkStep4Schema, Event, EmployeeWithZones, Zone } from "../bulk-schema";

interface BulkStep4Props {
  event: Event;
  employeesWithZones: EmployeeWithZones[];
  zones: Zone[];
}

export default function BulkStep4({ event, employeesWithZones, zones }: BulkStep4Props) {
  const formOptions = {
    title: "Solicitud Masiva de Acreditación",
    subtitle: "Paso 4: Confirmación",
    endpoint: "/accreditation-requests/bulk/step-4",
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
        title: "Paso 4: Confirmación",
        href: "/accreditation-requests/bulk/step-4",
      },
    ],
    permissions: {
      create: "accreditation_request.create",
    },
    actions: {
      save: {
        label: `Crear ${employeesWithZones.length} Solicitudes`,
        disabledText: "Debe confirmar la información",
      },
      cancel: {
        label: "Anterior",
        href: "/accreditation-requests/bulk/step-3",
      },
    },
    wizardConfig: {
      currentStep: 4,
      totalSteps: 4,
      steps: [
        { label: "Evento", isActive: true, isComplete: true },
        { label: "Empleados", isActive: true, isComplete: true },
        { label: "Zonas", isActive: true, isComplete: true },
        { label: "Confirmación", isActive: true, isComplete: false },
      ]
    }
  };

  return (
    <>
      <Head title="Solicitud Masiva - Paso 4" />
      <BaseFormPage
        options={formOptions}
        schema={bulkStep4Schema}
        defaultValues={{
          event_id: event.id.toString(),
          employee_zones: employeesWithZones.reduce((acc, emp: EmployeeWithZones) => {
            acc[emp.id.toString()] = emp.zones?.map((zoneId: number) => zoneId.toString()) || [];
            return acc;
          }, {} as Record<string, string[]>),
          confirm: false,
          notes: "",
        }}
        FormComponent={() => (
          <BulkStep4Form 
            event={event} 
            employeesWithZones={employeesWithZones}
            zones={zones}
          />
        )}
      />
    </>
  );
}
