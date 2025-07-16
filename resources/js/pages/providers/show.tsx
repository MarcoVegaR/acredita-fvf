import React, { ReactNode } from "react";
import { BaseShowPage, TabConfig, BaseShowOptions } from "@/components/base-show/base-show-page";
import { User } from "@/types";
import { 
  PhoneIcon, 
  ClockIcon,
  TruckIcon
} from "lucide-react";

// Importar los componentes de renderizado reutilizables
import { DateRenderer } from "@/components/base-show/renderers/date-renderer";
import { StatusRenderer } from "@/components/base-show/renderers/status-renderer";
import { Provider } from "./schema";

// Definición local de ImageType para evitar problemas de importación
interface ImageType {
  id: number;
  code: string;
  label: string;
  module: string;
}

interface ShowProviderProps {
  provider: Provider;
  areas?: Array<{
    id: number;
    name: string;
    code: string;
  }>;
  documentTypes?: Array<{
    id: number;
    code: string;
    label: string;
    module: string | null;
  }>;
  imageTypes?: Array<ImageType>;
  userPermissions?: string[];
}

export default function ShowProvider({ provider }: ShowProviderProps) {
  // Verificar permisos para documentos e imágenes - Temporalmente comentado
  /*
  const canViewDocuments = userPermissions.includes('documents.view.providers') || userPermissions.includes('documents.view');
  const canViewImages = userPermissions.includes('images.view.providers') || userPermissions.includes('images.view');
  */

  // Configuración de tabs - Temporalmente comentado
  const tabs: TabConfig[] = [
    { 
      value: "general", 
      label: "Información General", 
      icon: <TruckIcon className="h-4 w-4" /> 
    },
    { 
      value: "contact", 
      label: "Datos de Contacto", 
      icon: <PhoneIcon className="h-4 w-4" /> 
    },
    // Tabs de documentos e imágenes temporalmente comentados
    /*
    ...(canViewDocuments ? [
      { 
        value: "documents", 
        label: "Documentos", 
        icon: <FileTextIcon className="h-4 w-4" /> 
      }
    ] : []),
    ...(canViewImages ? [
      { 
        value: "images", 
        label: "Imágenes", 
        icon: <ImageIcon className="h-4 w-4" /> 
      }
    ] : []),
    */
    { 
      value: "metadata", 
      label: "Metadatos", 
      icon: <ClockIcon className="h-4 w-4" /> 
    },
  ];

  // Configuración de la página de detalle
  const showOptions: BaseShowOptions<Provider> = {
    title: provider.name,
    subtitle: "Detalle del proveedor",
    headerContent: (
      <div className="flex items-center space-x-4 py-3">
        <div className="flex-shrink-0">
          <div className="flex items-center justify-center h-16 w-16 rounded-full bg-primary/10 text-primary font-semibold text-xl">
            {provider.type === 'internal' ? 'IN' : 'EX'}
          </div>
        </div>
        <div>
          <div className="flex items-center gap-2">
            <h2 className="text-xl font-bold text-foreground">{provider.name}</h2>
            <StatusRenderer value={!!provider.active} />
          </div>
          <p className="text-muted-foreground">RIF: {provider.rif}</p>
          <div className="mt-1">
            <span className={`inline-flex items-center justify-center whitespace-nowrap rounded-md px-2.5 py-0.5 text-xs font-medium ${
              provider.type === "internal" 
                ? "bg-blue-50 text-blue-700" 
                : "bg-purple-50 text-purple-700"
            }`}>
              {provider.type === "internal" ? "Interno" : "Externo"}
            </span>
          </div>
        </div>
      </div>
    ),
    breadcrumbs: [
      { title: "Inicio", href: "/" },
      { title: "Proveedores", href: "/providers" },
      { title: provider.name, href: `/providers/${provider.uuid}` },
    ],
    entity: provider,
    moduleName: "providers",
    
    // Configuración de tabs
    tabs,
    defaultTab: "general",
    
    sections: [
      // Tab: Información General
      {
        title: "Información del Proveedor",
        tab: "general",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          {
            key: "uuid",
            label: "ID",
            render: (value: unknown): ReactNode => (
              <span className="font-mono text-xs bg-muted px-2 py-1 rounded">{value as string}</span>
            )
          },
          "name",
          "rif",
          {
            key: "type",
            label: "Tipo",
            render: (value: unknown): ReactNode => (
              <span className={`inline-flex items-center justify-center whitespace-nowrap rounded-md px-2.5 py-0.5 text-xs font-medium ${
                value === "internal" 
                  ? "bg-blue-50 text-blue-700" 
                  : "bg-purple-50 text-purple-700"
              }`}>
                {value === "internal" ? "Interno" : "Externo"}
              </span>
            )
          },
        ],
      },
      {
        title: "Área",
        tab: "general",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          {
            key: "area",
            label: "Área asignada",
            render: (value: unknown): ReactNode => {
              const area = value as { id: number; name: string };
              return area?.name || "No asignada";
            }
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
            label: "Estado del proveedor",
            render: (value: unknown): ReactNode => (
              <StatusRenderer 
                value={value as boolean} 
                positiveLabel="Activo" 
                negativeLabel="Inactivo" 
              />
            )
          },
        ],
      },
      
      // Tab: Datos de contacto
      {
        title: "Administrador de Contacto",
        tab: "contact",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          {
            key: "user",
            label: "Usuario administrador",
            render: (value: unknown): ReactNode => {
              const user = value as User;
              return user ? (
                <div className="space-y-1">
                  <div className="font-medium">{user.name}</div>
                  <div className="text-muted-foreground text-sm">{user.email}</div>
                </div>
              ) : "No asignado";
            }
          }
        ],
      },
      {
        title: "Datos de contacto",
        tab: "contact",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          {
            key: "phone",
            label: "Teléfono",
            render: (value: unknown): ReactNode => value ? <>{value}</> : <>No registrado</>
          }
        ],
      },
      
      // Tab para documentos - Temporalmente comentado
      /*
      ...(canViewDocuments ? [
        {
          tab: "documents",
          title: "Documentos del proveedor",
          fields: [
            {
              key: "documents" as keyof Provider,
              label: "",
              render: (): ReactNode => (
                <div className="w-full">
                  <DocumentsTab
                    module="providers"
                    entityId={provider.id}
                    types={documentTypes}
                    permissions={userPermissions}
                  />
                </div>
              )
            }
          ]
        }
      ] : []),
      */
      
      // Tab para imágenes - Temporalmente comentado
      /*
      ...(canViewImages ? [
        {
          tab: "images",
          title: "Imágenes del proveedor",
          fields: [
            {
              key: "images" as keyof Provider,
              label: "",
              render: (): ReactNode => (
                <div className="w-full">
                  <ImagesSection
                    module="providers"
                    entityId={provider.id}
                    types={imageTypes.length > 0 ? imageTypes : [
                      { id: 1, code: 'logo', label: 'Logo', module: 'providers' },
                      { id: 2, code: 'location', label: 'Ubicación', module: 'providers' }
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
      */
      
      // Tab para Metadatos
      {
        tab: "metadata",
        title: "Metadatos",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          { 
            key: "created_at", 
            label: "Fecha de creación",
            render: (value: unknown): ReactNode => <DateRenderer value={value as string} /> 
          },
          { 
            key: "updated_at", 
            label: "Última actualización",
            render: (value: unknown): ReactNode => <DateRenderer value={value as string} /> 
          }
        ]
      },
    ],
  };

  return <BaseShowPage options={showOptions} />;
}
