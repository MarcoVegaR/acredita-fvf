import React from "react";
import { Head } from "@inertiajs/react";
import { DocumentsSection } from "@/components/documents/DocumentsSection";
import { Document, DocumentType, User } from "@/types";
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
  
  // Esta vista es explícitamente para la gestión de documentos, NO es la vista show

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
              title: "Documentos",
              // Usar un campo ficticio con render personalizado para mostrar DocumentsSection
              // Usar una clase personalizada para esta sección sin padding y sin bordes internos
              className: "border rounded-lg overflow-hidden",
              // Un solo campo que ocupe todo el espacio disponible
              fields: [
                {
                  key: '',
                  // Sin etiqueta para evitar espacio adicional
                  label: '', 
                  // Renderizamos nuestro componente
                  render: () => (
                    <div className="col-span-2 w-full -mx-4 -my-4">
                      <DocumentsSection
                        module="users" 
                        entityId={user.id}
                        types={types}
                        permissions={permissions}
                        readOnly={false} 
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
