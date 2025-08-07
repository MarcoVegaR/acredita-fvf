import React from "react";
import { FormField, FormItem, FormLabel, FormControl, FormMessage, FormDescription } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Textarea } from "@/components/ui/textarea";
import { FormSection } from "@/components/base-form/form-section";
import { Badge } from "@/components/ui/badge";
import { 
  BuildingIcon,
  Info,
  Hash,
  ClipboardEdit,
  Building,
  Palette
} from "lucide-react";
import { BaseFormOptions, useFormContext } from "@/components/base-form/base-form";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Area } from "./schema";

export function AreaForm({ options }: { options: BaseFormOptions<Area> }) {
  // Obtener el formulario del contexto
  const { form } = useFormContext<Area>();
  
  // Determinar si es formulario de edición o creación
  const isEditing = options.isEdit;
  
  return (
    <div className="area-form-container">
      <div className="mb-5">
        <Badge className={isEditing 
          ? "px-2 py-1 bg-blue-100 text-blue-800 hover:bg-blue-100" 
          : "px-2 py-1 bg-emerald-100 text-emerald-800 hover:bg-emerald-100"
        }>
          {isEditing 
            ? <ClipboardEdit className="h-3.5 w-3.5 mr-1" /> 
            : <Building className="h-3.5 w-3.5 mr-1" />
          }
          {isEditing ? 'Edición de área' : 'Nueva área'}
        </Badge>
      </div>
      
      <div className="shadow-sm rounded-lg border bg-card p-6">
        <FormSection 
          title="Datos básicos" 
          description="Información principal del área" 
          columns={2}
          className="relative"
        >
          <div className="absolute top-3 right-4">
            <BuildingIcon className="h-5 w-5 text-muted-foreground" />
          </div>
          <FormField
            control={form.control}
            name="code"
            render={({ field }) => (
              <FormItem className="space-y-2">
                <div className="flex items-center gap-1">
                  <FormLabel className="text-base font-medium">Código</FormLabel>
                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger asChild>
                        <Info className="h-4 w-4 text-muted-foreground cursor-help" />
                      </TooltipTrigger>
                      <TooltipContent sideOffset={5}>
                        <p className="w-[200px] text-sm">Código único que identifica el área (máx. 10 caracteres)</p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                </div>
                <FormControl>
                  <div className="relative">
                    <Hash className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input 
                      placeholder="Ej: GERCOM" 
                      {...field} 
                      className={`pl-9 transition-all focus-within:ring-2 focus-within:ring-primary/20 hover:border-input uppercase ${form.formState.errors.code ? 'border-destructive' : 'border-input/50'}`}
                      maxLength={10}
                      onChange={(e) => field.onChange(e.target.value.toUpperCase())}
                      aria-invalid={!!form.formState.errors.code}
                    />
                  </div>
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
      
          <FormField
            control={form.control}
            name="name"
            render={({ field }) => (
              <FormItem className="space-y-2">
                <div className="flex items-center gap-1">
                  <FormLabel className="text-base font-medium">Nombre del área</FormLabel>
                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger asChild>
                        <Info className="h-4 w-4 text-muted-foreground cursor-help" />
                      </TooltipTrigger>
                      <TooltipContent sideOffset={5}>
                        <p className="w-[200px] text-sm">Nombre completo del área como aparecerá en el sistema</p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                </div>
                <FormControl>
                  <div className="relative">
                    <BuildingIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                    <Input 
                      placeholder="Ej: Gerencia Comercial" 
                      {...field} 
                      className={`pl-9 transition-all focus-within:ring-2 focus-within:ring-primary/20 hover:border-input ${form.formState.errors.name ? 'border-destructive' : 'border-input/50'}`}
                      aria-invalid={!!form.formState.errors.name}
                    />
                  </div>
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        </FormSection>

        <FormSection 
          title="Descripción" 
          description="Descripción detallada del área y sus funciones"
          columns={1}
        >
          <FormField
            control={form.control}
            name="description"
            render={({ field }) => (
              <FormItem>
                <FormLabel className="text-base font-medium">Descripción</FormLabel>
                <FormControl>
                  <Textarea 
                    placeholder="Describe las funciones y responsabilidades del área..." 
                    {...field} 
                    value={field.value || ""}
                    className="min-h-32 resize-y"
                  />
                </FormControl>
                <FormDescription>
                  Este campo es opcional. Puede incluir detalles sobre el propósito, funciones y alcance del área.
                </FormDescription>
                <FormMessage />
              </FormItem>
            )}
          />
        </FormSection>
        
        <FormSection 
          title="Color de credenciales" 
          description="Define el color que usarán las credenciales para esta área"
          columns={1}
          className="relative"
        >
          <div className="absolute top-3 right-4">
            <Palette className="h-5 w-5 text-muted-foreground" />
          </div>
          <FormField
            control={form.control}
            name="color"
            render={({ field }) => (
              <FormItem>
                <FormLabel className="text-base font-medium">Color identificativo</FormLabel>
                <div className="flex items-center gap-4">
                  <FormControl>
                    <div className="relative flex items-center">
                      <Input
                        type="color"
                        {...field}
                        value={field.value as string}
                        className="h-10 w-24 cursor-pointer p-1"
                        onChange={(e) => field.onChange(e.target.value)}
                      />
                      <Input
                        type="text"
                        value={field.value as string}
                        className="ml-3 w-32 transition-all focus-within:ring-2 focus-within:ring-primary/20 hover:border-input"
                        onChange={(e) => {
                          const value = e.target.value;
                          if (value.startsWith('#') && value.length <= 7) {
                            field.onChange(value);
                          }
                        }}
                      />
                    </div>
                  </FormControl>
                  <div 
                    className="h-10 w-24 rounded-md border" 
                    style={{ backgroundColor: field.value as string }}
                  ></div>
                </div>
                <FormDescription>
                  Este color se usará como fondo del rol en las credenciales generadas para colaboradores de esta área.
                </FormDescription>
                <FormMessage />
              </FormItem>
            )}
          />
        </FormSection>

        <FormSection 
          title="Estado" 
          description="Define si el área está activa en el sistema"
          columns={1}
        >
          <FormField
            control={form.control}
            name="active"
            render={({ field }) => (
              <FormItem className="flex flex-row items-start space-x-3 space-y-0 rounded-md border p-4">
                <FormControl>
                  <Checkbox
                    checked={field.value}
                    onCheckedChange={field.onChange}
                  />
                </FormControl>
                <div className="space-y-1 leading-none">
                  <FormLabel className="text-base">
                    Área activa
                  </FormLabel>
                  <FormDescription>
                    Las áreas activas aparecerán en los listados y podrán ser seleccionadas en otros módulos.
                  </FormDescription>
                </div>
              </FormItem>
            )}
          />
        </FormSection>
      </div>
    </div>
  );
}
