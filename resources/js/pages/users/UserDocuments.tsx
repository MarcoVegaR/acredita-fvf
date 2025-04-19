import React from "react";
import { Head } from "@inertiajs/react";
import { DocumentsSection } from "@/components/documents/DocumentsSection";
import { Document, DocumentType } from "@/types";

// Define User interface explicitly here since it's not exported from @/types
interface User {
  id: number;
  name: string;
  email: string;
  active?: boolean;
  role_names?: string[];
  [key: string]: string | number | boolean | string[] | undefined; // Allow for other properties
}
import { getColumnLabel } from "@/utils/translations/column-labels";
import { BaseShowPage } from "@/components/base-show/base-show-page";

interface UserDocumentsProps {
  user: User;
  documents: Document[];
  types: DocumentType[];
  permissions: string[];
}

export default function UserDocuments({
  user,
  types,
  permissions,
}: UserDocumentsProps) {
  const title = `Documentos de ${user.name}`;
  
  const breadcrumbs = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Usuarios", href: "/users" },
    { title: user.name, href: `/users/${user.id}` },
    { title: "Documentos", href: `/users/${user.id}/documents` }
  ];

  return (
    <>
      <Head title={title} />
      
      <BaseShowPage
        options={{
          title: title,
          subtitle: `Gestión de documentos para ${user.name}`,
          breadcrumbs: breadcrumbs,
          moduleName: "documents",
          entity: user,
          sections: [
            {
              title: getColumnLabel('documents', 'section_title'),
              className: "mt-4",
              // Usamos fields para renderizar el componente DocumentsSection
              fields: [
                {
                  key: "documents",
                  label: "", // Sin etiqueta para ocupar todo el espacio
                  render: () => (
                    <div className="w-full">
                      <DocumentsSection
                        module="users"
                        entityId={user.id}
                        types={types}
                        permissions={permissions}
                      />
                    </div>
                  )
                }
              ]
            }
          ]
        }}
      />
    </>
  );
}
