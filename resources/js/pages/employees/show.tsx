import React from "react";
import { BaseShowPage, TabConfig } from "@/components/base-show/base-show-page";
import { route } from "ziggy-js";
import { Employee } from "./schema";
import { UserIcon, BriefcaseIcon, BuildingIcon, FileTextIcon, ImageIcon, ClockIcon, FileCheck } from "lucide-react";

// Importar los componentes de renderizado reutilizables
import { DateRenderer } from "@/components/base-show/renderers/date-renderer";
import { StatusRenderer } from "@/components/base-show/renderers/status-renderer";
import { DocumentsTab } from "@/components/documents/DocumentsTab";
import ImagesSection from "@/components/images/ImagesSection";

// Definición local de ImageType para evitar problemas de importación
interface ImageType {
  id: number;
  code: string;
  label: string;
  module: string;
}

interface Provider {
  id: number;
  name: string;
  uuid: string;
}

// Extiende la interfaz Employee para garantizar que id es un número requerido
// y que sea compatible con Entity (incluye índice para propiedades dinámicas)
interface EmployeeWithId extends Employee {
  id: number;
  [key: string]: unknown;
}

interface EmployeeProps {
  employee: EmployeeWithId & {
    provider?: Provider;
  };
  documentTypes?: Array<{
    id: number;
    code: string;
    label: string;
    module: string | null;
  }>;
  imageTypes?: Array<ImageType>;
  userPermissions?: string[];
}

export default function ShowEmployee({ employee, documentTypes = [], imageTypes = [], userPermissions = [] }: EmployeeProps) {
  // Verificar si el usuario tiene permisos para ver documentos e imágenes
  const canViewDocuments = userPermissions.includes('documents.view') || userPermissions.includes('documents.view.employees');
  const canViewImages = userPermissions.includes('images.view') || userPermissions.includes('images.view.employees');
  
  // Mapeador de tipos de documento según la migración
  const documentTypeMap: Record<string, string> = {
    "V": "Venezolano",
    "E": "Extranjero",
    "P": "Pasaporte"
  };

  // Configuración de tabs con iconos descriptivos
  const tabs: TabConfig[] = [
    { 
      value: "general", 
      label: "Información General", 
      icon: <UserIcon className="h-4 w-4" /> 
    },
    { 
      value: "provider", 
      label: "Proveedor", 
      icon: <BuildingIcon className="h-4 w-4" /> 
    },
    // Solo mostrar la pestaña de documentos si el usuario tiene permisos
    ...(canViewDocuments ? [
      { 
        value: "documents", 
        label: "Documentos", 
        icon: <FileTextIcon className="h-4 w-4" /> 
      }
    ] : []),
    // Solo mostrar la pestaña de imágenes si el usuario tiene permisos
    ...(canViewImages ? [
      { 
        value: "images", 
        label: "Imágenes", 
        icon: <ImageIcon className="h-4 w-4" /> 
      }
    ] : []),
    { 
      value: "metadata", 
      label: "Metadatos", 
      icon: <ClockIcon className="h-4 w-4" /> 
    },
  ];

  // Configuración de la página de detalle
  const showOptions = {
    title: `${employee.first_name} ${employee.last_name}`,
    subtitle: "Ficha del colaborador",
    headerContent: (
      <div className="flex items-center space-x-4 py-3">
        <div className="flex-shrink-0">
          {employee.photo_path ? (
            <div className="w-24 h-32 border rounded-md overflow-hidden">
              <img 
                src={`/storage/${employee.photo_path}`}
                alt={`${employee.first_name} ${employee.last_name}`} 
                className="h-full w-full object-cover"
              />
            </div>
          ) : (
            <div className="flex items-center justify-center w-24 h-32 rounded-md bg-primary/10 text-primary">
              <UserIcon className="h-12 w-12" />
            </div>
          )}
        </div>
        <div>
          <div className="flex items-center gap-2">
            <h2 className="text-xl font-bold text-foreground">
              {employee.first_name} {employee.last_name}
            </h2>
            <StatusRenderer value={!!employee.active} />
          </div>
          <div className="flex items-center text-muted-foreground">
            <BriefcaseIcon className="h-4 w-4 mr-1.5" />
            <span>{employee.function}</span>
          </div>
          {employee.provider && (
            <div className="flex items-center text-muted-foreground mt-1">
              <BuildingIcon className="h-4 w-4 mr-1.5" />
              <span>{employee.provider.name}</span>
            </div>
          )}
          <div className="flex items-center text-muted-foreground mt-1">
            <FileCheck className="h-4 w-4 mr-1.5" />
            <span>
              {documentTypeMap[employee.document_type] || employee.document_type}: {employee.document_number}
            </span>
          </div>
        </div>
      </div>
    ),
    breadcrumbs: [
      { title: "Inicio", href: "/" },
      { title: "Colaboradores", href: route('employees.index') },
      { title: `${employee.first_name} ${employee.last_name}`, href: route('employees.show', { uuid: employee.uuid }) },
    ],
    entity: employee,
    moduleName: "employees",
    backUrl: route('employees.index'),
    
    // Configuración de tabs
    tabs,
    defaultTab: "general",
    
    sections: [
      // Tab: Información General
      {
        title: "Información personal",
        tab: "general",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          {
            key: "first_name",
            label: "Nombre"
          },
          {
            key: "last_name",
            label: "Apellido"
          },
          {
            key: "function",
            label: "Función / Cargo",
            render: (value: unknown) => (
              <div className="flex items-center">
                <BriefcaseIcon className="h-4 w-4 mr-1.5 text-muted-foreground" />
                <span>{value as string}</span>
              </div>
            )
          }
        ],
      },
      {
        title: "Documento de identidad",
        tab: "general",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          {
            key: "document_type",
            label: "Tipo de documento",
            render: (value: unknown) => (
              <span>{documentTypeMap[value as string] || value as string}</span>
            )
          },
          {
            key: "document_number",
            label: "Número de documento"
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
            label: "Estado",
            render: (value: unknown) => (
              <StatusRenderer 
                value={value as boolean} 
                positiveLabel="Activo" 
                negativeLabel="Inactivo" 
              />
            )
          },
        ],
      },
      
      // Tab: Información del proveedor
      {
        title: "Datos del proveedor",
        tab: "provider",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          {
            key: "provider.name",
            label: "Nombre del proveedor",
            render: (_: unknown, entity: unknown) => {
              const employeeEntity = entity as Employee & { provider?: Provider };
              return employeeEntity.provider ? (
                <div className="flex items-center">
                  <BuildingIcon className="h-4 w-4 mr-1.5 text-muted-foreground" />
                  <span>{employeeEntity.provider.name}</span>
                </div>
              ) : (
                <span className="text-muted-foreground">No asignado</span>
              );
            }
          },
          {
            key: "provider.uuid",
            label: "Ver detalles del proveedor",
            render: (_: unknown, entity: unknown) => {
              const employeeEntity = entity as Employee & { provider?: Provider };
              return employeeEntity.provider ? (
                <a 
                  href={route('providers.show', { 'uuid': employeeEntity.provider.uuid })}
                  className="text-primary hover:underline flex items-center"
                >
                  <BuildingIcon className="h-4 w-4 mr-1.5" />
                  Ver ficha del proveedor
                </a>
              ) : (
                <span className="text-muted-foreground">No disponible</span>
              );
            }
          }
        ],
        permission: "provider.view", // Solo visible para usuarios que pueden ver proveedores
      },
      
      // Tab para documentos
      ...(canViewDocuments ? [
        {
          tab: "documents",
          title: "Documentos del empleado",
          fields: [
            {
              key: "",
              label: "",
              render: () => (
                <div className="w-full">
                  <DocumentsTab
                    module="employees"
                    entityId={employee.id || 0} /* Usar 0 como fallback si id es undefined */
                    types={documentTypes}
                    permissions={userPermissions}
                  />
                </div>
              )
            }
          ]
        }
      ] : []),
      
      // Tab para imágenes
      ...(canViewImages ? [
        {
          tab: "images",
          title: "Imágenes del empleado",
          fields: [
            {
              key: "",
              label: "",
              render: () => (
                <div className="w-full">
                  <ImagesSection
                    module="employees"
                    entityId={employee.id || 0} /* Usar 0 como fallback si id es undefined */
                    types={imageTypes.length > 0 ? imageTypes : [
                      { id: 1, code: 'profile', label: 'Perfil', module: 'employees' }
                    ]}
                    permissions={userPermissions}
                    readOnly={true}
                  />
                </div>
              )
            }
          ]
        }
      ] : []),

      // Tab para Metadatos
      {
        tab: "metadata",
        title: "Metadatos",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          { key: "id", label: "ID interno" },
          { key: "uuid", label: "UUID" },
          { 
            key: "created_at", 
            label: "Fecha de creación", 
            render: (value: unknown) => <DateRenderer value={value as string} /> 
          },
          { 
            key: "updated_at", 
            label: "Última actualización", 
            render: (value: unknown) => <DateRenderer value={value as string} /> 
          },
        ],
        permission: "employee.manage", // Solo visible para administradores
      },
    ],
  };

  return <BaseShowPage options={showOptions} />;
}
