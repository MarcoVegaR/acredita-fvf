import React from "react";
import { BaseShowPage } from "@/components/base-show/base-show-page";
import { Template } from "./schema";
import { FileDown, Star, AlertCircle, Info, Image, Layout, Clock, RefreshCw } from "lucide-react";
import { route } from "ziggy-js";
import { Tabs, TabsList, TabsTrigger, TabsContent } from "@/components/ui/tabs";
import { TextBlock } from "./types";
import { usePage } from "@inertiajs/react";

// Define las props para la vista de detalle
interface ShowTemplateProps {
  template: Template;
  can_set_default: boolean;
  can_regenerate_credentials: boolean;
}

export default function ShowTemplate({ template, can_set_default, can_regenerate_credentials }: ShowTemplateProps) {
  const { auth } = usePage<{ auth: { user?: { permissions?: string[] } } }>().props;
  
  // Debug logs
  console.log('ShowTemplate props:', { 
    template, 
    can_set_default, 
    can_regenerate_credentials,
    auth_permissions: auth?.user?.permissions 
  });

  // Opciones para la vista de detalle
  const options = {
    title: template.name,
    subtitle: `Plantilla de evento: ${template.event?.name || 'Sin evento asignado'}`,
    breadcrumbs: [
      { title: "Dashboard", href: route("dashboard") },
      { title: "Plantillas", href: route("templates.index") },
      { title: template.name, href: route("templates.show", { template: template.uuid }) },
    ],
    backUrl: route("templates.index"),
    entity: {
      ...template,
      id: template.id || 0
    },
    moduleName: "templates",
    sections: [
      {
        title: "Detalles de la plantilla",
        fields: [], // Añadido campo requerido por SectionDef
        custom: ({ entity }: { entity: Template }) => (
          <Tabs defaultValue="basic" className="w-full">
            <TabsList className="mb-4">
              <TabsTrigger value="basic" className="flex items-center gap-1.5">
                <Info className="h-4 w-4" />
                Información básica
              </TabsTrigger>
              <TabsTrigger value="preview" className="flex items-center gap-1.5">
                <Image className="h-4 w-4" />
                Vista previa
              </TabsTrigger>
              <TabsTrigger value="layout" className="flex items-center gap-1.5">
                <Layout className="h-4 w-4" />
                Layout
              </TabsTrigger>
              <TabsTrigger value="metadata" className="flex items-center gap-1.5">
                <Clock className="h-4 w-4" />
                Metadatos
              </TabsTrigger>
            </TabsList>
            
            {/* Tab: Información básica */}
            <TabsContent value="basic" className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 border rounded-lg">
                <div className="space-y-2">
                  <div>
                    <span className="text-sm font-medium text-muted-foreground">ID</span>
                    <p>{entity.id}</p>
                  </div>
                  <div>
                    <span className="text-sm font-medium text-muted-foreground">UUID</span>
                    <p className="font-mono text-sm">{entity.uuid}</p>
                  </div>
                  <div>
                    <span className="text-sm font-medium text-muted-foreground">Evento</span>
                    <p>{entity.event?.name || 'Sin evento asignado'}</p>
                  </div>
                </div>
                <div className="space-y-2">
                  <div>
                    <span className="text-sm font-medium text-muted-foreground">Nombre</span>
                    <p>{entity.name}</p>
                  </div>
                  <div>
                    <span className="text-sm font-medium text-muted-foreground">Predeterminada</span>
                    <p>{entity.is_default ? "Sí" : "No"}</p>
                  </div>
                  <div>
                    <span className="text-sm font-medium text-muted-foreground">Versión</span>
                    <p>v{entity.version || 1}</p>
                  </div>
                </div>
              </div>
            </TabsContent>
            
            {/* Tab: Vista previa */}
            <TabsContent value="preview" className="space-y-4">
              <div className="overflow-hidden border rounded-lg">
                <div className="aspect-[1.414/1] w-full relative">
                  <img 
                    src={entity.file_url} 
                    alt={`Vista previa de ${entity.name}`}
                    className="object-contain w-full h-full"
                  />
                  
                  {/* Indicador de línea de pliegue */}
                  <div 
                    className="absolute left-0 right-0 border-t-2 border-dashed border-blue-500 flex items-center justify-center"
                    style={{ 
                      top: `${((entity.layout_meta?.fold_mm || 0) / 200) * 100}%`,
                      width: '100%'
                    }}
                  >
                    <span className="bg-blue-100 text-blue-800 text-xs font-medium px-2 py-0.5 rounded">
                      Línea de pliegue: {entity.layout_meta?.fold_mm || 0}mm
                    </span>
                  </div>
                  
                  {/* Indicador de rectángulo para foto */}
                  <div 
                    className="absolute border-2 border-blue-500 bg-blue-500/20 flex items-center justify-center"
                    style={{ 
                      top: `${((entity.layout_meta?.rect_photo?.y || 0) / 200) * 100}%`,
                      left: `${((entity.layout_meta?.rect_photo?.x || 0) / 200) * 100}%`,
                      width: `${((entity.layout_meta?.rect_photo?.width || 0) / 200) * 100}%`,
                      height: `${((entity.layout_meta?.rect_photo?.height || 0) / 200) * 100}%`
                    }}
                  >
                    <span className="bg-white/80 text-blue-800 text-xs font-medium px-2 py-0.5 rounded">
                      Área para foto
                    </span>
                  </div>
                  
                  {/* Indicador de rectángulo para QR */}
                  <div 
                    className="absolute border-2 border-green-500 bg-green-500/20 flex items-center justify-center"
                    style={{ 
                      top: `${((entity.layout_meta?.rect_qr?.y || 0) / 200) * 100}%`,
                      left: `${((entity.layout_meta?.rect_qr?.x || 0) / 200) * 100}%`,
                      width: `${((entity.layout_meta?.rect_qr?.width || 0) / 200) * 100}%`,
                      height: `${((entity.layout_meta?.rect_qr?.height || 0) / 200) * 100}%`
                    }}
                  >
                    <span className="bg-white/80 text-green-800 text-xs font-medium px-2 py-0.5 rounded">
                      Área para QR
                    </span>
                  </div>
                  
                  {/* Indicadores de bloques de texto */}
                  {entity.layout_meta?.text_blocks && entity.layout_meta.text_blocks.map((block: TextBlock) => (
                    <div 
                      key={block.id}
                      className="absolute border-2 border-orange-500 bg-orange-500/20 flex items-center justify-center"
                      style={{ 
                        top: `${((block.y || 0) / 200) * 100}%`,
                        left: `${((block.x || 0) / 200) * 100}%`,
                        width: `${((block.width || 0) / 200) * 100}%`,
                        height: `${((block.height || 0) / 200) * 100}%`
                      }}
                    >
                      <span className="bg-white/80 text-orange-800 text-xs font-medium px-2 py-0.5 rounded">
                        {block.id}
                      </span>
                    </div>
                  ))}
                </div>
                <div className="p-4 flex justify-between items-center bg-muted/20">
                  <span className="text-sm text-muted-foreground">
                    {entity.is_default ? 
                      "Esta es la plantilla predeterminada para este evento" : 
                      "Esta plantilla no es la predeterminada"
                    }
                  </span>
                  <a 
                    href={entity.file_url} 
                    download
                    className="inline-flex items-center gap-1.5 bg-white text-gray-800 border border-gray-300 hover:bg-gray-100 px-3 py-1.5 rounded text-sm font-medium transition-colors"
                  >
                    <FileDown className="h-4 w-4" />
                    Descargar
                  </a>
                </div>
              </div>
            </TabsContent>
            
            {/* Tab: Información del layout */}
            <TabsContent value="layout" className="space-y-4">
              <div className="p-4 border rounded-lg">
                <div className="mb-4">
                  <span className="text-sm font-medium text-muted-foreground">Línea de pliegue</span>
                  <p>{entity.layout_meta?.fold_mm || 0}mm desde el borde superior</p>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                  <div className="border rounded-lg p-4 bg-blue-50">
                    <h4 className="font-medium mb-2 text-blue-800">Área de foto</h4>
                    <ul className="list-disc pl-5 space-y-1 text-sm">
                      <li>Posición X: {entity.layout_meta?.rect_photo?.x || 0}</li>
                      <li>Posición Y: {entity.layout_meta?.rect_photo?.y || 0}</li>
                      <li>Ancho: {entity.layout_meta?.rect_photo?.width || 0}</li>
                      <li>Alto: {entity.layout_meta?.rect_photo?.height || 0}</li>
                    </ul>
                  </div>
                  <div className="border rounded-lg p-4 bg-green-50">
                    <h4 className="font-medium mb-2 text-green-800">Área de código QR</h4>
                    <ul className="list-disc pl-5 space-y-1 text-sm">
                      <li>Posición X: {entity.layout_meta?.rect_qr?.x || 0}</li>
                      <li>Posición Y: {entity.layout_meta?.rect_qr?.y || 0}</li>
                      <li>Ancho: {entity.layout_meta?.rect_qr?.width || 0}</li>
                      <li>Alto: {entity.layout_meta?.rect_qr?.height || 0}</li>
                    </ul>
                  </div>
                  {entity.layout_meta?.text_blocks && entity.layout_meta.text_blocks.length > 0 ? (
                    <div className="md:col-span-2 mt-2">
                      <h4 className="font-medium mb-2">Bloques de texto ({entity.layout_meta?.text_blocks?.length || 0})</h4>
                      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        {entity.layout_meta?.text_blocks?.map((block) => (
                          <div key={block.id} className="border rounded p-3 bg-orange-50">
                            <h5 className="font-medium text-orange-800">{block.id}</h5>
                            <p className="text-sm">Posición: ({block.x || 0}, {block.y || 0})</p>
                            <p className="text-sm">Tamaño: {block.width || 0}x{block.height || 0}</p>
                            <p className="text-sm">Fuente: {block.font_size || 12}pt</p>
                            <p className="text-sm">Alineación: {block.alignment || 'left'}</p>
                          </div>
                        ))}
                      </div>
                    </div>
                  ) : (
                    <div className="md:col-span-2 flex items-center gap-2 text-amber-600 p-3 bg-amber-50 rounded-lg">
                      <AlertCircle className="h-5 w-5" />
                      <span>No se han definido bloques de texto para esta plantilla.</span>
                    </div>
                  )}
                </div>
              </div>
            </TabsContent>
            
            {/* Tab: Metadatos */}
            <TabsContent value="metadata" className="space-y-4">
              <div className="p-4 border rounded-lg">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <span className="text-sm font-medium text-muted-foreground">Creado</span>
                    <p>{entity.created_at}</p>
                  </div>
                  <div>
                    <span className="text-sm font-medium text-muted-foreground">Última actualización</span>
                    <p>{entity.updated_at}</p>
                  </div>
                </div>
              </div>
            </TabsContent>
          </Tabs>
        )
      }
    ],
    actions: [
      {
        label: "Establecer como predeterminada",
        icon: <Star className="mr-2 h-4 w-4" />,
        variant: "outline",
        permission: "templates.set_default",
        condition: !template.is_default && can_set_default,
        confirmDialog: {
          title: "Establecer como predeterminada",
          description: "¿Está seguro de establecer esta plantilla como predeterminada? La plantilla predeterminada actual dejará de serlo.",
          confirmLabel: "Establecer",
          cancelLabel: "Cancelar"
        },
        action: () => ({
          method: "POST",
          url: route("templates.set_default", { template: template.uuid }),
          successMessage: "Plantilla establecida como predeterminada correctamente",
          redirectUrl: null // Sin redirección
        })
      },
      {
        label: "Regenerar credenciales",
        icon: <RefreshCw className="mr-2 h-4 w-4" />,
        variant: "outline",
        permission: "credentials.regenerate",
        condition: can_regenerate_credentials,
        confirmDialog: {
          title: "Regenerar credenciales",
          description: `¿Está seguro de regenerar todas las credenciales del evento "${template.event?.name}" usando esta plantilla?\n\nEsto actualizará todas las credenciales existentes con el nuevo diseño y puede tomar algunos minutos.`,
          confirmLabel: "Regenerar",
          cancelLabel: "Cancelar"
        },
        action: () => ({
          method: "POST",
          url: route("templates.regenerate_credentials", { template: template.uuid }),
          successMessage: "Las credenciales se están regenerando en segundo plano",
          redirectUrl: null // Sin redirección
        })
      }
    ]
  };

  return <BaseShowPage options={options} />;
}
