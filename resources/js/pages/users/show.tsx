import React from "react";
import { BaseShowPage, TabConfig } from "@/components/base-show/base-show-page";
import { User } from "@/types";
import { UserIcon, ShieldIcon, ClockIcon, FileTextIcon } from "lucide-react";
// Column labels accessed directly in components when needed

// Importar los componentes de renderizado reutilizables
import { DateRenderer } from "@/components/base-show/renderers/date-renderer";
import { StatusRenderer } from "@/components/base-show/renderers/status-renderer";
import { ChipListRenderer } from "@/components/base-show/renderers/chip-list-renderer";
import { RolePermissionsRenderer } from "@/components/base-show/renderers/role-permissions-renderer";
import { DocumentsTab } from "@/components/documents/DocumentsTab";

interface Permission {
  name: string;
  nameshow: string;
}

interface UserProps {
  user: User & {
    role_names: string[];
  };
  allRoles: Array<{
    id: number;
    name: string;
  }>;
  permissions: Permission[];
  rolePermissions: Record<string, Permission[]>;
  documentTypes?: Array<{
    id: number;
    code: string;
    label: string;
    module: string | null;
  }>;
  userDocuments?: Array<{id: number; filename: string; [key: string]: string | number | boolean | null}>;
  userPermissions?: string[];
}

export default function ShowUser({ user, rolePermissions, documentTypes = [], userPermissions = [] }: UserProps) {
  // Eliminamos variables no utilizadas

  // Función para obtener iniciales del usuario (para el avatar)
  const getUserInitials = (name: string): string => {
    return name
      .split(' ')
      .map(part => part.charAt(0))
      .join('')
      .toUpperCase()
      .substring(0, 2);
  };

  // Verificar si el usuario tiene permisos para ver documentos
  const canViewDocuments = userPermissions.includes('documents.view.users') || userPermissions.includes('documents.view');

  // Configuración de tabs con iconos más descriptivos
  const tabs: TabConfig[] = [
    { 
      value: "general", 
      label: "Información General", 
      icon: <UserIcon className="h-4 w-4" /> 
    },
    { 
      value: "permissions", 
      label: "Roles y Permisos", 
      icon: <ShieldIcon className="h-4 w-4" /> 
    },
    // Solo mostrar la pestaña de documentos si el usuario tiene permisos
    ...(canViewDocuments ? [
      { 
        value: "documents", 
        label: "Documentos", 
        icon: <FileTextIcon className="h-4 w-4" /> 
      }
    ] : []),
    { 
      value: "metadata", 
      label: "Metadatos", 
      icon: <ClockIcon className="h-4 w-4" /> 
    },
  ];

  // Configuración de la página de detalle con encabezado mejorado
  const showOptions = {
    title: user.name,
    subtitle: "Detalle del usuario",
    headerContent: (
      <div className="flex items-center space-x-4 py-3">
        <div className="flex-shrink-0">
          {user.avatar ? (
            <img 
              src={user.avatar} 
              alt={user.name} 
              className="h-16 w-16 rounded-full border-2 border-primary/20"
            />
          ) : (
            <div className="flex items-center justify-center h-16 w-16 rounded-full bg-primary/10 text-primary font-semibold text-lg">
              {getUserInitials(user.name)}
            </div>
          )}
        </div>
        <div>
          <div className="flex items-center gap-2">
            <h2 className="text-xl font-bold text-foreground">{user.name}</h2>
            <StatusRenderer value={!!user.active} />
          </div>
          <p className="text-muted-foreground">{user.email}</p>
          <div className="mt-1">
            <ChipListRenderer items={user.role_names} />
          </div>
        </div>
      </div>
    ),
    breadcrumbs: [
      { title: "Inicio", href: "/" },
      { title: "Usuarios", href: "/users" },
      { title: user.name, href: `/users/${user.id}` },
    ],
    entity: user,
    moduleName: "users",
    
    // Configuración de tabs
    tabs,
    defaultTab: "general",
    
    sections: [
      // Tab: Información General
      {
        title: "Información de cuenta",
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
          "name",
          "email",
        ],
      },
      {
        title: "Estado",
        tab: "general",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          {
            key: "email_verified_at",
            label: "Email verificado",
            render: (value: unknown) => (
              <StatusRenderer 
                value={!!(value as string | null)} 
                positiveLabel="Verificado" 
                negativeLabel="No verificado" 
                type="icon" 
              />
            )
          },
          {
            key: "active",
            label: "Estado de la cuenta",
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
      
      // Tab: Roles y Permisos
      {
        title: "Roles asignados",
        tab: "permissions",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          {
            key: "role_names",
            label: "Roles",
            render: (value: unknown) => (
              <ChipListRenderer 
                items={value as string[]} 
                emptyMessage="Sin roles asignados" 
                color="blue" 
              />
            )
          },
        ],
        // Eliminado el permiso para asegurar que esta sección sea visible
      },
      {
        title: "Permisos del usuario",
        tab: "permissions",
        className: "bg-card rounded-lg border shadow-sm p-6",
        fields: [
          {
            key: "role_names",
            label: "Permisos",
            render: (value: unknown) => (
              <RolePermissionsRenderer 
                roles={value as string[]} 
                rolePermissions={rolePermissions} 
              />
            )
          },
        ],
        // Eliminado el permiso para asegurar que esta sección sea visible
      },
      
      // Tab para documentos
      ...(canViewDocuments ? [
        {
          tab: "documents",
          title: "Documentos del usuario",
          // Usamos un enfoque diferente: en lugar de render o condition, usamos el array fields
          // con un custom renderer que muestra directamente el componente DocumentsTab
          fields: [
            {
              key: "",
              label: "", // Sin etiqueta para ocupar todo el espacio
              render: () => (
                <div className="w-full">
                  <DocumentsTab
                    module="users"
                    entityId={user.id}
                    types={documentTypes}
                    permissions={userPermissions}
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
        fields: [
          { key: "created_at", render: (value: unknown) => <DateRenderer value={value as string} /> },
          { key: "updated_at", render: (value: unknown) => <DateRenderer value={value as string} /> },
          { key: "email_verified_at", render: (value: unknown) => (
            value ? <DateRenderer value={value as string} /> : <span className="text-muted-foreground">No verificado</span>
          ) },
          // Agregar otros campos de metadatos relevantes
        ]
      },
    ],
  };

  return <BaseShowPage options={showOptions} />;
}
