import React from "react";
import { Head } from "@inertiajs/react";
import { BaseShowPage } from "@/components/base-show/base-show-page";
import ImagesSection from "@/components/images/ImagesSection";
import { ImageType, Image } from "@/types";

// Define User interface explicitly here since it's not exported from @/types
interface User {
  id: number;
  name: string;
  email: string;
  active?: boolean;
  role_names?: string[];
  [key: string]: string | number | boolean | string[] | undefined; // Allow for other properties
}

interface UserImagesProps {
  user: User;
  images: Image[];
  imageTypes: ImageType[];
  permissions: {
    canUpload: boolean;
    canDelete: boolean;
  };
}

export default function UserImages({
  user,
  imageTypes,
  permissions,
}: UserImagesProps) {
  const title = `Imágenes de ${user.name}`;
  
  const breadcrumbs = [
    { title: "Dashboard", href: "/dashboard" },
    { title: "Usuarios", href: "/users" },
    { title: user.name, href: `/users/${user.id}` },
    { title: "Imágenes", href: `/users/${user.id}/images` }
  ];

  // Convertir los permisos a la estructura esperada por ImagesSection
  const permissionsArray = [
    ...(permissions.canUpload ? ["images.upload.users"] : []),
    ...(permissions.canDelete ? ["images.delete.users"] : []),
    "images.view.users"
  ];

  return (
    <>
      <Head title={title} />
      
      <BaseShowPage
        options={{
          title: title,
          subtitle: `Gestión de imágenes para ${user.name}`,
          breadcrumbs: breadcrumbs,
          moduleName: "images",
          entity: user,
          sections: [
            {
              title: "Imágenes",
              className: "mt-4",
              // Usamos fields para renderizar el componente ImagesSection a través de un render personalizado
              fields: [
                {
                  key: "images",
                  label: "", // Sin etiqueta para ocupar todo el espacio
                  render: () => (
                    <div className="w-full">
                      <ImagesSection
                        module="users"
                        entityId={user.id}
                        types={imageTypes}
                        permissions={permissionsArray}
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
