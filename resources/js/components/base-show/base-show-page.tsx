import React, { useState } from "react";
import { Head } from "@inertiajs/react";
import { ShowSection } from "@/components/base-show/show-section";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";

import { usePermissions } from "@/hooks/usePermissions";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";

// Tipo genérico para la entidad
export interface Entity {
  id: number;
  // Usamos unknown en lugar de any para mejorar la seguridad de tipos
  // pero manteniendo la flexibilidad para propiedades dinámicas
  [key: string]: unknown;
}

// Definición para un campo individual
export interface FieldDef<T> {
  key: keyof T;
  label?: string;
  render?: (value: unknown, record: T) => React.ReactNode;
  permission?: string; // Permiso necesario para ver este campo
}

// Definición para una sección
export interface SectionDef<T> {
  title: string;
  fields: Array<keyof T | FieldDef<T>>;
  permission?: string; // Permiso necesario para ver esta sección
  condition?: (entity: T) => boolean; // Solo muestra la sección si devuelve true
}

// Definición para configuración de tabs
export interface TabConfig {
  value: string;
  label: string;
  icon?: React.ReactNode;
}

// Opciones para la página de detalle
export interface BaseShowOptions<T extends Entity> {
  // Metadatos y navegación
  title: string;
  subtitle?: string;
  breadcrumbs: BreadcrumbItem[];
  
  // Contenido personalizado para el encabezado
  headerContent?: React.ReactNode;
  
  // Datos principales
  entity: T;
  
  // Configuración de tabs (si no hay tabs, se usan secciones directamente)
  tabs?: TabConfig[];
  defaultTab?: string;
  
  // Secciones organizadas por tabs o globales si no hay tabs
  sections: Array<SectionDef<T> & { tab?: string, className?: string }>;
  
  // Configuración adicional
  moduleName?: string; // Para traducciones
}

// Props para el componente BaseShowPage
interface BaseShowPageProps<T extends Entity> {
  options: BaseShowOptions<T>;
}

// Componente principal BaseShowPage
export function BaseShowPage<T extends Entity>({ options }: BaseShowPageProps<T>) {
  // We're not using any props from usePage anymore
  const { can } = usePermissions();
  
  // Estado para la tab activa
  const [activeTab, setActiveTab] = useState<string>(options.defaultTab || (options.tabs && options.tabs.length > 0 ? options.tabs[0].value : 'default'));
  
  // NOTA: Eliminado el manejo duplicado de mensajes flash.
  // Los mensajes flash ahora son manejados exclusivamente por el componente FlashMessages

  // Filtrar secciones según permisos y condiciones
  const visibleSections = options.sections.filter(section => {
    // Verificar permiso si existe
    if (section.permission && !can(section.permission)) {
      return false;
    }
    
    // Verificar condición si existe
    if (section.condition && !section.condition(options.entity)) {
      return false;
    }
    
    return true;
  });

  // Agrupar secciones por tab si es necesario
  const renderSections = () => {
    // Si no hay tabs configurados, mostrar todas las secciones
    if (!options.tabs || options.tabs.length === 0) {
      return (
        <div className="space-y-8">
          {visibleSections.map((section, index) => (
            <ShowSection
              key={index}
              section={section}
              entity={options.entity}
              moduleName={options.moduleName}
            />
          ))}
        </div>
      );
    }
    
    // Con tabs, mostrar el contenido según la tab activa
    return (
      <Tabs defaultValue={activeTab} onValueChange={setActiveTab} className="w-full">
        <TabsList className="mb-4">
          {options.tabs.map((tab) => (
            <TabsTrigger key={tab.value} value={tab.value} className="flex items-center gap-1">
              {tab.icon && <span className="mr-1">{tab.icon}</span>}
              {tab.label}
            </TabsTrigger>
          ))}
        </TabsList>
        
        {options.tabs.map((tab) => {
          // Filtrar secciones para esta tab
          const tabSections = visibleSections.filter((section) => 
            section.tab === tab.value || (!section.tab && tab.value === 'default')
          );
          
          return (
            <TabsContent key={tab.value} value={tab.value} className="space-y-8 mt-6">
              {tabSections.map((section, index) => (
                <ShowSection
                  key={index}
                  section={section}
                  entity={options.entity}
                  moduleName={options.moduleName}
                />
              ))}
              
              {tabSections.length === 0 && (
                <div className="text-center p-8 border rounded-md bg-muted/20">
                  <p className="text-muted-foreground">No hay información disponible en esta sección</p>
                </div>
              )}
            </TabsContent>
          );
        })}
      </Tabs>
    );
  };

  return (
    <AppLayout breadcrumbs={options.breadcrumbs}>
      <Head title={options.title} />
      <div className="flex h-full flex-1 flex-col gap-5 p-5 pb-8">
        <div className="flex flex-col space-y-3 mb-5">
          <div className="py-4 sm:py-6 md:py-8">
            {/* Encabezado de la página */}
            <div className="mb-6">
              {options.headerContent ? (
                options.headerContent
              ) : (
                <>
                  <h1 className="text-2xl font-bold tracking-tight">{options.title}</h1>
                  {options.subtitle && (
                    <p className="text-muted-foreground mt-1">{options.subtitle}</p>
                  )}
                </>
              )}
            </div>
          </div>
          
          {/* Componente separador decorativo */}
          <div className="w-full mt-1 mb-4 flex items-center">
            <div className="h-1 w-16 bg-primary rounded-full"></div>
            <div className="h-px flex-1 bg-border ml-2"></div>
          </div>
        </div>
        
        {/* Contenido principal (con o sin tabs) */}
        {renderSections()}
      </div>
    </AppLayout>
  );
}
