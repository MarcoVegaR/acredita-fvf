import React, { useState, useMemo, useEffect } from "react";
import { useFormContext, FieldValues, Control } from "react-hook-form";

// Tipo para el formulario que incluye los métodos necesarios
type FormType = {
  getValues: (field?: string) => unknown;
  watch: <T>(callback: (value: T, info: { name?: string }) => void) => unknown;
  setValue: (name: string, value: unknown) => void;
  control: Control<FieldValues>;
  formState: { errors: Record<string, { message: string }> };
};
import { FormField, FormItem, FormLabel, FormControl, FormMessage, FormDescription } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { FormSection } from "@/components/base-form/form-section";
import { FormTabContainer, FormTab } from "@/components/base-form";
import { BaseFormOptions } from "@/components/base-form/base-form";
import { Provider } from "./schema";
import { Badge } from "@/components/ui/badge";
import { Combobox } from "@/components/ui/combobox";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { 
  BuildingIcon,
  UserIcon,
  CircleUserIcon,
  AlertCircle, 
  Loader2 
} from "lucide-react";

interface User {
  id: number;
  name: string;
  email: string;
}

interface Area {
  id: number;
  name: string;
  description?: string;
  manager_user_id?: number | null;
  manager?: User;
}

// Tipos para uso interno en el componente
type FormField = string | number | null;

// Hacemos el componente genérico para aceptar tanto Provider como ProviderFormValues
interface ProviderFormProps<T = Provider> {
  form?: FormType; // Usamos el tipo FormType definido
  options: BaseFormOptions<T> & {
    additionalProps?: {
      areas?: Area[];
      areasWithInternalProviders?: number[];
    }
  };
}

export function ProviderForm<T extends Record<string, unknown> = Provider>({ options, form: externalForm }: ProviderFormProps<T>) {
  // Usamos el form pasado como prop o el del contexto
  const formContext = useFormContext<Record<string, unknown>>();
  const form = externalForm || (formContext as unknown as FormType);

  // Estados para la UI
  const [loadingAreas] = useState<boolean>(false);
  const [providerType, setProviderType] = useState<string>((form.getValues("type") as string) || "external");
  const [selectedArea, setSelectedArea] = useState<Area | null>(null);
  
  // Determinar si es formulario de edición o creación
  const isEditing = options.isEdit;

  // Obtener las áreas desde additionalProps usando useMemo para evitar dependencias cambiantes
  const areas = useMemo(() => {
    return (options.additionalProps?.areas as Area[]) || [];
  }, [options.additionalProps?.areas]);
  
  // Obtener las áreas con proveedores internos desde additionalProps
  const areasWithInternalProviders = useMemo(() => {
    const providers = (options.additionalProps?.areasWithInternalProviders || []);
    console.log('Areas con proveedores internos:', providers);
    return providers;
  }, [options.additionalProps?.areasWithInternalProviders]);
  
  // Filtrar áreas para proveedores internos usando useMemo:
  // No deben tener ya un proveedor interno asignado (excepto en edición)
  const filteredAreas = useMemo(() => {
    console.log('Ejecutando filtro de áreas. Tipo:', providerType, 'Total áreas:', areas.length);
    
    if (providerType === "internal") {
      const filtered = areas.filter(area => {
        // El área no debe tener un proveedor interno ya asignado
        // Si estamos editando y el área ya está seleccionada, permitimos mantenerla
        const hasInternalProvider = areasWithInternalProviders.includes(area.id);
        
        // Obtener el valor del ID de área del formulario y convertirlo al tipo correcto para comparación
        const formAreaId = form.getValues("area_id");
        const parsedFormAreaId = typeof formAreaId === 'string' ? parseInt(formAreaId) : formAreaId;
        const isCurrentArea = isEditing && parsedFormAreaId === area.id;
        
        console.log(`Área ${area.id} ${area.name}: tiene proveedor interno=${hasInternalProvider}, es área actual=${isCurrentArea}, incluir=${!hasInternalProvider || isCurrentArea}`);
        
        // Solo validamos que no tenga ya un proveedor interno (o sea el actual en edición)
        return !hasInternalProvider || isCurrentArea;
      });
      
      console.log('Resultado: mostrando', filtered.length, 'de', areas.length, 'áreas');
      return filtered;
    } else {
      return areas;
    }
  }, [areas, providerType, areasWithInternalProviders, isEditing, form]);
  
  // Actualizar el estado del tipo de proveedor y gestionar cambios de área
  useEffect(() => {
    const subscription = form.watch<Record<string, unknown>>((value, { name }) => {
      if (name === "type") {
        setProviderType(value.type as string);
        
        // Para proveedores internos, asegurar que user sea undefined
        if (value.type === "internal") {
          console.log('Proveedor interno detectado, eliminando campo user');
          form.setValue("user", undefined);
        }
        
        // Limpiar área seleccionada si cambiamos a interno y no tiene gerente
        if (value.type === "internal" && value.area_id) {
          const areaValue = typeof value.area_id === 'string' ? parseInt(value.area_id) : value.area_id as number;
          const selectedArea = areas.find(a => a.id === areaValue);
          if (selectedArea && !selectedArea.manager_user_id) {
            // Usamos la misma interfaz tipada para manejar el valor vacío
            const typedForm = form as unknown as {
              setValue: (name: string, value: unknown) => void
            };
            typedForm.setValue("area_id", null);
          }
        }
      } else if (name === "area_id" && value.area_id) {
        // Buscar el área seleccionada
        const areaValue = typeof value.area_id === 'string' ? parseInt(value.area_id) : value.area_id as number;
        const selectedArea = areas.find(a => a.id === areaValue);
        setSelectedArea(selectedArea || null);
        
        // Si es un proveedor interno, asignar automáticamente el usuario gerente
        if (providerType === "internal" && selectedArea?.manager_user_id) {
          // Para valores que no están en el esquema necesitamos una conversión de tipos segura
          const typedForm = form as unknown as {
            setValue: (name: string, value: unknown) => void
          };
          typedForm.setValue("user_id", selectedArea.manager_user_id);
          
          // Si el área tiene gerente y estamos creando un proveedor interno
          if (selectedArea.manager && !isEditing) {
            form.setValue("name", `Interno ${selectedArea.name}`);
            // Para valores que no están en el esquema usamos la interfaz tipada
            typedForm.setValue("email", selectedArea.manager.email);
          }
        }
      }
    });

    // Manejar la suscripción de forma segura
    return () => {
      if (subscription && typeof subscription === 'object' && 'unsubscribe' in subscription) {
        (subscription as { unsubscribe: () => void }).unsubscribe();
      }
    };
  }, [form, areas, providerType, isEditing]);
  
  // Inicializar el área seleccionada cuando se carga el componente
  useEffect(() => {
    const areaId = form.getValues("area_id");
    if (areaId) {
      const areaValue = typeof areaId === 'string' ? parseInt(areaId) : areaId;
      const area = areas.find(a => a.id === areaValue);
      setSelectedArea(area || null);
    }
    
    // Inicializar user a undefined explícitamente al cargar si es un proveedor interno
    const currentType = form.getValues("type") as string;
    if (currentType === "internal") {
      console.log('Inicializando proveedor interno, eliminando campo user');
      form.setValue("user", undefined);
    }
  }, [form, areas]);

  return (
    <div className="provider-form-container">
      <div className="mb-5">
        <Badge className={isEditing 
          ? "px-2 py-1 bg-blue-100 text-blue-800" 
          : "px-2 py-1 bg-emerald-100 text-emerald-800"
        }>
          {isEditing ? "Editando Proveedor" : "Nuevo Proveedor"}
        </Badge>
        <h3 className="text-lg font-medium mt-1">
          {isEditing ? "Actualice la información del proveedor" : "Ingrese los datos del nuevo proveedor"}
        </h3>
        <p className="text-sm text-muted-foreground mt-1">
          {isEditing 
            ? "Modifique los campos necesarios y guarde los cambios" 
            : "Complete todos los campos obligatorios para registrar un nuevo proveedor"}
        </p>
      </div>

      <FormTabContainer defaultValue="general">
        <FormTab value="general" label="Información General" icon={<BuildingIcon className="h-4 w-4" />}>
          <FormSection title="Datos básicos" description="Información principal del proveedor">
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Nombre del Proveedor <span className="text-destructive">*</span></FormLabel>
                    <FormControl>
                      <Input placeholder="Nombre completo" value={field.value || ""} onChange={field.onChange} name={field.name} onBlur={field.onBlur} ref={field.ref} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="rif"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>RIF <span className="text-destructive">*</span></FormLabel>
                    <FormControl>
                      <Input placeholder="Ej: J-12345678-9" value={field.value || ""} onChange={field.onChange} name={field.name} onBlur={field.onBlur} ref={field.ref} />
                    </FormControl>
                    <FormDescription>
                      Registro de Información Fiscal (Solo letras mayúsculas, números y guiones)
                    </FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <FormField
                control={form.control}
                name="type"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Tipo de Proveedor <span className="text-destructive">*</span></FormLabel>
                    <Select 
                      onValueChange={field.onChange} 
                      defaultValue={field.value}
                      disabled={isEditing} // No permitir cambiar el tipo en edición
                    >
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Seleccione un tipo" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="internal">Interno</SelectItem>
                        <SelectItem value="external">Externo</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormDescription>
                      {field.value === "internal" 
                        ? "Proveedor interno: pertenece a la organización" 
                        : "Proveedor externo: empresa o persona externa"}
                    </FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />

              <FormField
                control={form.control}
                name="phone"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Teléfono</FormLabel>
                    <FormControl>
                      <Input placeholder="Número de contacto" value={field.value || ""} onChange={field.onChange} name={field.name} onBlur={field.onBlur} ref={field.ref} />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>

            <FormField
              control={form.control}
              name="area_id"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Área <span className="text-destructive">*</span></FormLabel>
                  <FormControl>
                    {loadingAreas ? (
                      <div className="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm text-muted-foreground">
                        <Loader2 className="h-4 w-4 animate-spin mr-2" />
                        <span>Cargando áreas...</span>
                      </div>
                    ) : (
                      <Combobox
                        options={Array.isArray(filteredAreas) ? filteredAreas.map(area => {
                          return { 
                            value: area.id ? area.id.toString() : '', 
                            label: providerType === "internal" && area.manager
                              ? `${area.name} (Gerente: ${area.manager.name})` 
                              : area.name || 'Sin nombre',
                            disabled: providerType === "internal" && !area.manager_user_id
                          };
                        }) : []}
                        value={field.value ? field.value.toString() : ""}
                        onChange={(value) => {
                          field.onChange(value ? parseInt(value) : null);
                        }}
                        disabled={(isEditing && providerType === "internal")}
                        placeholder="Seleccione un área"
                        searchPlaceholder="Buscar área..."
                        emptyMessage={providerType === "internal" 
                          ? "No hay áreas con gerentes asignados disponibles" 
                          : "No se encontraron áreas."}
                        className="w-full"
                      />
                    )}
                  </FormControl>
                  {providerType === "internal" && (
                    <FormDescription>
                      {isEditing ? (
                        <span className="flex items-center text-amber-600">
                          <AlertCircle className="h-4 w-4 mr-1" />
                          No es posible cambiar el área de un proveedor interno
                        </span>
                      ) : (
                        <span className="flex items-center text-blue-600">
                          <CircleUserIcon className="h-4 w-4 mr-1" />
                          Los proveedores internos solo pueden asignarse a áreas con gerente
                        </span>
                      )}
                    </FormDescription>
                  )}
                  {selectedArea?.manager && providerType === "internal" && (
                    <div className="mt-2 p-3 rounded-md bg-primary/5 border border-primary/20">
                      <h4 className="text-sm font-medium mb-1">Gerente del área</h4>
                      <div className="flex items-center gap-2">
                        <div className="h-8 w-8 rounded-full bg-primary/10 flex items-center justify-center text-primary">
                          {selectedArea.manager.name.charAt(0).toUpperCase()}
                        </div>
                        <div>
                          <p className="text-sm font-medium">{selectedArea.manager.name}</p>
                          <p className="text-xs text-muted-foreground">{selectedArea.manager.email}</p>
                        </div>
                      </div>
                    </div>
                  )}
                  <FormMessage />
                </FormItem>
              )}
            />

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
                    <FormLabel>Activo</FormLabel>
                    <FormDescription>
                      Indica si el proveedor está activo y disponible en el sistema
                    </FormDescription>
                  </div>
                </FormItem>
              )}
            />
          </FormSection>
        </FormTab>

        {/* Sección de administrador, solo visible para proveedores externos */}
        {providerType === "external" && (
          <FormTab value="admin" label="Administrador" icon={<UserIcon className="h-4 w-4" />}>
            <FormSection title="Datos del administrador" description="Información del usuario administrador del proveedor externo">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <FormField
                  control={form.control}
                  name="user.name"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Nombre <span className="text-destructive">*</span></FormLabel>
                      <FormControl>
                        <Input placeholder="Nombre del administrador" value={field.value || ""} onChange={field.onChange} name={field.name} onBlur={field.onBlur} ref={field.ref} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />

                <FormField
                  control={form.control}
                  name="user.email"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Correo electrónico <span className="text-destructive">*</span></FormLabel>
                      <FormControl>
                        <Input type="email" placeholder="correo@ejemplo.com" value={field.value || ""} onChange={field.onChange} name={field.name} onBlur={field.onBlur} ref={field.ref} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>

              {!isEditing && (
                <FormField
                  control={form.control}
                  name="user.password"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Contraseña</FormLabel>
                      <FormControl>
                        <Input 
                          type="password" 
                          placeholder="Contraseña para el usuario administrador" 
                          value={field.value || ""}
                          onChange={field.onChange}
                          name={field.name}
                          onBlur={field.onBlur}
                          ref={field.ref}
                        />
                      </FormControl>
                      <FormDescription>
                        Si no se proporciona, se generará una contraseña automáticamente
                      </FormDescription>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              )}

              {isEditing && (
                <FormField
                  control={form.control}
                  name="user.password"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Nueva contraseña</FormLabel>
                      <FormControl>
                        <Input 
                          type="password" 
                          placeholder="Deje en blanco para mantener la contraseña actual" 
                          value={field.value || ""}
                          onChange={field.onChange}
                          name={field.name}
                          onBlur={field.onBlur}
                          ref={field.ref}
                        />
                      </FormControl>
                      <FormDescription>
                        Solo complete este campo si desea cambiar la contraseña
                      </FormDescription>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              )}
            </FormSection>
          </FormTab>
        )}
      </FormTabContainer>
    </div>
  );
}
