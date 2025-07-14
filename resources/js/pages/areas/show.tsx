import React from "react";
import { BaseShowPage, TabConfig } from "@/components/base-show/base-show-page";
import { Area } from "./columns";
import { BuildingIcon, ClockIcon, InfoIcon } from "lucide-react";
import { DateRenderer } from "@/components/base-show/renderers/date-renderer";
import { StatusRenderer } from "@/components/base-show/renderers/status-renderer";

interface AreaProps {
  area: Area;
}

export default function ShowArea({ area }: AreaProps) {
  // Configuración de tabs con iconos descriptivos
  const tabs: TabConfig[] = [
    { 
      value: "general", 
      label: "Información General", 
      icon: <InfoIcon className="h-4 w-4" /> 
    },
    { 
      value: "metadata", 
      label: "Metadatos", 
      icon: <ClockIcon className="h-4 w-4" /> 
    },
  ];

  // Configuración de la página de detalle
  const showOptions = {
    title: area.name,
    subtitle: "Detalle del área",
    headerContent: (
      <div className="flex items-center space-x-4 py-3">
        <div className="flex-shrink-0">
          <div className="flex items-center justify-center h-14 w-14 rounded-full bg-primary/10 text-primary">
            <BuildingIcon className="h-7 w-7" />
          </div>
        </div>
        <div>
          <div className="flex items-center gap-2">
            <h2 className="text-xl font-bold text-foreground">{area.name}</h2>
            <StatusRenderer value={!!area.active} />
          </div>
          <p className="text-muted-foreground">Código: {area.code}</p>
        </div>
      </div>
    ),
    breadcrumbs: [
      { title: "Inicio", href: "/" },
      { title: "Áreas", href: "/areas" },
      { title: area.name, href: `/areas/${area.id}` },
    ],
    entity: area,
    moduleName: "areas",
    
    // Configuración de tabs
    tabs,
    defaultTab: "general",
    
    sections: [
      // Tab: Información General
      {
        title: "Información básica",
        tab: "general",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          {
            key: "id",
            label: "ID",
            render: (value: unknown) => (
              <span className="font-mono text-xs bg-muted px-2 py-1 rounded">{value as number}</span>
            )
          },
          "code",
          "name",
        ],
      },
      {
        title: "Descripción",
        tab: "general",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          {
            key: "description",
            label: "Descripción",
            render: (value: unknown) => (
              <div className="prose prose-sm max-w-none">
                {(value as string) || <span className="text-muted-foreground italic">Sin descripción</span>}
              </div>
            )
          },
        ],
      },
      {
        title: "Estado",
        tab: "general",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          {
            key: "active",
            label: "Estado del área",
            render: (value: unknown) => (
              <StatusRenderer 
                value={value as boolean} 
                positiveLabel="Activa" 
                negativeLabel="Inactiva" 
              />
            )
          },
        ],
      },
      
      // Tab para Metadatos
      {
        tab: "metadata",
        title: "Metadatos",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          { 
            key: "uuid", 
            label: "UUID",
            render: (value: unknown) => (
              <span className="font-mono text-xs bg-muted px-2 py-1 rounded break-all">{value as string}</span>
            )
          },
          { key: "created_at", render: (value: unknown) => <DateRenderer value={value as string} /> },
          { key: "updated_at", render: (value: unknown) => <DateRenderer value={value as string} /> },
          { 
            key: "deleted_at", 
            render: (value: unknown) => (
              value ? <DateRenderer value={value as string} /> : <span className="text-muted-foreground">No eliminado</span>
            ) 
          },
        ]
      },
    ],
  };

  return <BaseShowPage options={showOptions} />;
}
