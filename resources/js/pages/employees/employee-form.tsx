import React, { useState, useEffect } from "react";
import { FormField, FormItem, FormLabel, FormControl, FormMessage, FormDescription } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { FormSection } from "@/components/base-form/form-section";
import { FormTabContainer, FormTab } from "@/components/base-form";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Employee, Provider, DOCUMENT_TYPES } from "./schema";
import { Badge } from "@/components/ui/badge";
import { 
  UserIcon, 
  ClipboardList, 
  Briefcase,
  User, 
  FileCheck, 
  Info,
  UserCheck,
  UserPlus
} from "lucide-react";
import { BaseFormOptions, useFormContext } from "@/components/base-form/base-form";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { toast } from "sonner";
import PhotoCropper from "@/components/image-cropper/photo-cropper";

interface EmployeeFormProps {
  options: BaseFormOptions<Employee> | BaseFormOptions<Partial<Employee>>;
  availableProviders?: Provider[];
}

export function EmployeeForm({ options, availableProviders = [] }: EmployeeFormProps) {
  // Obtener el formulario del contexto
  const { form } = useFormContext<Employee>();
  
  // Estados para la UI
  const [loadingProviders, setLoadingProviders] = useState<boolean>(true);
  const [providers, setProviders] = useState<Provider[]>(availableProviders);

  // Determinar si es formulario de edición o creación
  const isEditing = options.isEdit;
  
  // Cargar proveedores disponibles desde la API si no se proporcionaron
  useEffect(() => {
    const fetchProviders = async () => {
      if (availableProviders.length > 0) {
        setProviders(availableProviders);
        setLoadingProviders(false);
        return;
      }

      try {
        setLoadingProviders(true);
        // En un entorno real, esto sería una llamada a la API
        // Simulamos la carga
        setTimeout(() => {
          const mockProviders: Provider[] = [
            { id: 1, name: "Proveedor Ejemplo 1" },
            { id: 2, name: "Proveedor Ejemplo 2" },
            { id: 3, name: "Proveedor Ejemplo 3" }
          ];
          setProviders(mockProviders);
          setLoadingProviders(false);
        }, 500);
      } catch (error) {
        console.error("Error al cargar los proveedores:", error);
        toast.error("No se pudieron cargar los proveedores disponibles");
        setLoadingProviders(false);
      }
    };
    
    fetchProviders();
  }, [availableProviders]);
  
  // Manejar el cambio de la foto recortada
  const handleCroppedPhotoChange = (croppedImage: string | null) => {
    form.setValue("croppedPhoto", croppedImage);
  };

  return (
    <div className="employee-form-container">
      <div className="mb-5">
        <Badge className={isEditing 
          ? "px-2 py-1 bg-blue-100 text-blue-800 hover:bg-blue-100" 
          : "px-2 py-1 bg-emerald-100 text-emerald-800 hover:bg-emerald-100"
        }>
          {isEditing 
            ? <UserCheck className="h-3.5 w-3.5 mr-1" /> 
            : <UserPlus className="h-3.5 w-3.5 mr-1" />
          }
          {isEditing ? 'Edición de empleado' : 'Nuevo empleado'}
        </Badge>
      </div>
      
      <FormTabContainer 
        defaultValue={options.defaultTab || "general"} 
        className="shadow-sm rounded-lg border bg-card overflow-hidden"
      >
        <FormTab value="general" label="Información General" icon={<UserIcon className="h-4 w-4" />}>
          <FormSection 
            title="Datos del empleado" 
            description="Información personal del empleado" 
            columns={2}
          >
            {/* Nombre del empleado */}
            <FormField
              control={form.control}
              name="first_name"
              render={({ field }) => (
                <FormItem className="space-y-2">
                  <FormLabel className="text-base font-medium">Nombre</FormLabel>
                  <FormControl>
                    <div className="relative">
                      <User className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input 
                        placeholder="Nombre del empleado" 
                        {...field} 
                        className="pl-9 transition-all focus-within:ring-2 focus-within:ring-primary/20"
                      />
                    </div>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Apellido del empleado */}
            <FormField
              control={form.control}
              name="last_name"
              render={({ field }) => (
                <FormItem className="space-y-2">
                  <FormLabel className="text-base font-medium">Apellido</FormLabel>
                  <FormControl>
                    <div className="relative">
                      <User className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input 
                        placeholder="Apellido del empleado" 
                        {...field} 
                        className="pl-9 transition-all focus-within:ring-2 focus-within:ring-primary/20"
                      />
                    </div>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Tipo de documento */}
            <FormField
              control={form.control}
              name="document_type"
              render={({ field }) => (
                <FormItem className="space-y-2">
                  <FormLabel className="text-base font-medium">Tipo de documento</FormLabel>
                  <Select onValueChange={field.onChange} defaultValue={field.value}>
                    <FormControl>
                      <SelectTrigger>
                        <SelectValue placeholder="Seleccionar tipo" />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      {DOCUMENT_TYPES.map((type) => (
                        <SelectItem key={type.value} value={type.value}>
                          {type.label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Número de documento */}
            <FormField
              control={form.control}
              name="document_number"
              render={({ field }) => (
                <FormItem className="space-y-2">
                  <FormLabel className="text-base font-medium">Número de documento</FormLabel>
                  <FormControl>
                    <div className="relative">
                      <FileCheck className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input 
                        placeholder="Número de documento" 
                        {...field} 
                        className="pl-9 transition-all focus-within:ring-2 focus-within:ring-primary/20"
                      />
                    </div>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Proveedor al que pertenece */}
            <FormField
              control={form.control}
              name="provider_id"
              render={({ field }) => (
                <FormItem className="space-y-2">
                  <div className="flex items-center gap-1">
                    <FormLabel className="text-base font-medium">Proveedor</FormLabel>
                    <TooltipProvider>
                      <Tooltip>
                        <TooltipTrigger asChild>
                          <Info className="h-4 w-4 text-muted-foreground cursor-help" />
                        </TooltipTrigger>
                        <TooltipContent sideOffset={5}>
                          <p className="w-[200px] text-sm">Proveedor al que pertenece este empleado</p>
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                  </div>
                  <Select 
                    onValueChange={(value) => field.onChange(parseInt(value))}
                    defaultValue={field.value ? String(field.value) : undefined}
                    disabled={loadingProviders}
                  >
                    <FormControl>
                      <SelectTrigger>
                        <SelectValue placeholder={loadingProviders ? "Cargando proveedores..." : "Seleccionar proveedor"} />
                      </SelectTrigger>
                    </FormControl>
                    <SelectContent>
                      {providers.map((provider) => (
                        <SelectItem key={provider.id} value={String(provider.id)}>
                          {provider.name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Función o cargo */}
            <FormField
              control={form.control}
              name="function"
              render={({ field }) => (
                <FormItem className="space-y-2">
                  <FormLabel className="text-base font-medium">Función / Cargo</FormLabel>
                  <FormControl>
                    <div className="relative">
                      <Briefcase className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input 
                        placeholder="Función o cargo del empleado" 
                        {...field} 
                        className="pl-9 transition-all focus-within:ring-2 focus-within:ring-primary/20"
                      />
                    </div>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />

            {/* Estado activo */}
            <FormField
              control={form.control}
              name="active"
              render={({ field }) => (
                <FormItem className="flex flex-row items-center space-x-3 space-y-0 rounded-md border p-4">
                  <FormControl>
                    <Checkbox
                      checked={field.value}
                      onCheckedChange={field.onChange}
                    />
                  </FormControl>
                  <div className="space-y-1 leading-none">
                    <FormLabel>
                      Empleado activo
                    </FormLabel>
                    <FormDescription>
                      Los empleados inactivos no aparecerán en las listas
                    </FormDescription>
                  </div>
                </FormItem>
              )}
            />
          </FormSection>
        </FormTab>

        <FormTab value="photo" label="Fotografía" icon={<ClipboardList className="h-4 w-4" />}>
          <FormSection 
            title="Fotografía del empleado" 
            description="Sube y recorta la fotografía en proporción 3×4 cm"
          >
            <div className="w-full">
              {/* Componente de recorte de fotografía */}
              <FormField
                control={form.control}
                name="croppedPhoto"
                render={({ field }) => (
                  <FormItem>
                    <FormControl>
                      <PhotoCropper
                        value={field.value || undefined}
                        onChange={handleCroppedPhotoChange}
                        aspectRatio={3/4}
                        className="w-full"
                      />
                    </FormControl>
                    <FormDescription>
                      La foto debe ser clara, con fondo claro y mostrar el rostro completo de la persona.
                      Será recortada automáticamente en proporción 3×4 cm.
                    </FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
          </FormSection>
        </FormTab>
      </FormTabContainer>
    </div>
  );
}
