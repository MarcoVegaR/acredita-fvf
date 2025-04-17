# Patrón Base Form: Guía de Implementación para Creación y Edición

Esta guía proporciona instrucciones para implementar el patrón estándar de formularios de creación y edición en la aplicación. Sigue estos pasos para mantener la consistencia en todos los módulos.

## Índice

1. [Estructura del patrón](#estructura-del-patrón)
2. [Creación de un nuevo módulo](#creación-de-un-nuevo-módulo)
   - [Paso 1: Definición del modelo](#paso-1-definición-del-modelo)
   - [Paso 2: Implementación de la vista Create](#paso-2-implementación-de-la-vista-create)
   - [Paso 3: Implementación de la vista Edit](#paso-3-implementación-de-la-vista-edit)
   - [Paso 4: Implementación del formulario compartido](#paso-4-implementación-del-formulario-compartido)
3. [Componentes base disponibles](#componentes-base-disponibles)
4. [Mejores prácticas](#mejores-prácticas)
   - [Validación con Zod](#validación-con-zod)
   - [Uso de Tabs para formularios extensos](#uso-de-tabs-para-formularios-extensos)
   - [Notificaciones estándar](#notificaciones-estándar)
   - [Componentes especializados](#componentes-especializados)

## Estructura del patrón

El patrón de formularios implementa una arquitectura de tres componentes para cada entidad:

```
resources/js/pages/[entidad]/
├── create.tsx        # Vista de creación
├── edit.tsx          # Vista de edición 
├── [entidad]-form.tsx # Formulario compartido
└── schema.ts         # Esquema de validación Zod e interfaces TypeScript
```

Este patrón permite:
- Compartir lógica de formulario entre creación y edición
- Validación y tipado consistente
- Separación clara de responsabilidades
- Mantenimiento simplificado

## Creación de un nuevo módulo

### Paso 1: Definición del modelo

Crea el archivo `schema.ts` que define la interfaz TypeScript y el esquema de validación Zod:

```tsx
// resources/js/pages/[entidad]/schema.ts
import { z } from "zod";

// Interfaz TypeScript para la entidad
export interface Entidad {
  id?: number;
  nombre: string;
  descripcion: string;
  activo: boolean;
  // Añade otras propiedades según la entidad
}

// Esquema de validación Zod
export const entidadSchema = z.object({
  nombre: z.string().min(1, "Este campo es obligatorio"),
  descripcion: z.string().optional(),
  activo: z.boolean().default(true),
  // Reglas de validación para otras propiedades
});

// Opcional: exportar tipo inferido
// export type Entidad = z.infer<typeof entidadSchema>;
```

### Paso 2: Implementación de la vista Create

Crea la vista de creación utilizando el componente `BaseFormPage`:

```tsx
// resources/js/pages/[entidad]/create.tsx
import React from "react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { EntidadForm } from "./[entidad]-form";
import { entidadSchema } from "./schema";
import route from "ziggy-js";

export default function CreateEntidad() {
  return (
    <BaseFormPage
      title="Crear Nueva Entidad"
      subtitle="Ingrese los datos para crear una nueva entidad"
      backUrl={route("entidades.index")}
      breadcrumbs={[
        { label: "Dashboard", url: route("dashboard") },
        { label: "Entidades", url: route("entidades.index") },
        { label: "Crear Nueva", url: "#" },
      ]}
      schema={entidadSchema}
      defaultValues={{
        nombre: "",
        descripcion: "",
        activo: true,
      }}
      endpoint={route("entidades.store")}
      FormComponent={EntidadForm}
      permission="entidades.create"
      submitLabel="Crear Entidad"
    />
  );
}
```

### Paso 3: Implementación de la vista Edit

Crea la vista de edición utilizando el mismo patrón:

```tsx
// resources/js/pages/[entidad]/edit.tsx
import React from "react";
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { EntidadForm } from "./[entidad]-form";
import { entidadSchema } from "./schema";
import route from "ziggy-js";
import { Entidad } from "./schema";

interface Props {
  entidad: Entidad;
}

export default function EditEntidad({ entidad }: Props) {
  return (
    <BaseFormPage
      title={`Editar Entidad: ${entidad.nombre}`}
      subtitle="Modifique los datos de la entidad"
      backUrl={route("entidades.index")}
      breadcrumbs={[
        { label: "Dashboard", url: route("dashboard") },
        { label: "Entidades", url: route("entidades.index") },
        { label: `Editar: ${entidad.nombre}`, url: "#" },
      ]}
      schema={entidadSchema}
      defaultValues={entidad}
      endpoint={route("entidades.update", entidad.id)}
      FormComponent={EntidadForm}
      permission="entidades.edit"
      isEdit={true}
      data={entidad}
      submitLabel="Actualizar Entidad"
    />
  );
}
```

### Paso 4: Implementación del formulario compartido

Crea el componente de formulario compartido que se utiliza tanto en creación como en edición:

```tsx
// resources/js/pages/[entidad]/[entidad]-form.tsx
import React from "react";
import { FormField, FormItem, FormLabel, FormControl, FormMessage, FormDescription } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { Textarea } from "@/components/ui/textarea";
import { FormSection } from "@/components/base-form/form-section";
import { FormTabContainer, FormTab } from "@/components/base-form";
import { BaseFormOptions, useFormContext } from "@/components/base-form/base-form";
import { Entidad } from "./schema";

export function EntidadForm({ options }: { options: BaseFormOptions<Entidad> }) {
  // Obtener el formulario del contexto
  const { form, isSubmitting } = useFormContext<Entidad>();
  
  // Determinar si es edición o creación
  const isEditing = options.isEdit;

  return (
    <FormTabContainer defaultValue="general">
      <FormTab value="general" label="Información General">
        <FormSection title="Datos Principales" columns={1}>
          <FormField
            control={form.control}
            name="nombre"
            render={({ field }) => (
              <FormItem>
                <FormLabel>Nombre</FormLabel>
                <FormControl>
                  <Input {...field} placeholder="Nombre de la entidad" />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
          
          <FormField
            control={form.control}
            name="descripcion"
            render={({ field }) => (
              <FormItem>
                <FormLabel>Descripción</FormLabel>
                <FormControl>
                  <Textarea {...field} placeholder="Descripción detallada" />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
          
          <FormField
            control={form.control}
            name="activo"
            render={({ field }) => (
              <FormItem className="flex flex-row items-start space-x-3 space-y-0 rounded-md border p-4">
                <FormControl>
                  <Checkbox
                    checked={field.value}
                    onCheckedChange={field.onChange}
                  />
                </FormControl>
                <div className="space-y-1 leading-none">
                  <FormLabel>Entidad activa</FormLabel>
                  <FormDescription>
                    Si está desactivada, no estará disponible en el sistema
                  </FormDescription>
                </div>
              </FormItem>
            )}
          />
        </FormSection>
      </FormTab>
    </FormTabContainer>
  );
}
```

## Componentes base disponibles

El sistema proporciona los siguientes componentes base:

### BaseFormPage

Componente principal para crear rápidamente páginas de formulario:

```tsx
<BaseFormPage
  title="Título del formulario"
  subtitle="Descripción o instrucciones"
  backUrl={route("entidades.index")}
  breadcrumbs={[/* array de migas de pan */]}
  schema={entidadSchema}          // Esquema Zod
  defaultValues={{}}              // Valores iniciales
  endpoint={route("entidades.store")} // URL de envío
  FormComponent={EntidadForm}     // Componente de formulario
  permission="entidades.create"  // Permiso requerido
  isEdit={false}                 // Modo edición
  data={null}                    // Datos para edición
  submitLabel="Guardar"          // Texto del botón
/>
```

### FormSection

Sección para agrupar campos relacionados:

```tsx
<FormSection 
  title="Título de la sección" 
  description="Descripción opcional" 
  columns={2} // 1 o 2 columnas
>
  {/* Campos del formulario */}
</FormSection>
```

### FormTabContainer y FormTab

Organización de formularios en pestañas:

```tsx
<FormTabContainer defaultValue="general">
  <FormTab value="general" label="General">
    {/* Contenido de la pestaña */}
  </FormTab>
  <FormTab value="avanzado" label="Configuración avanzada">
    {/* Contenido de la segunda pestaña */}
  </FormTab>
</FormTabContainer>
```

## Mejores prácticas

### Validación con Zod

Usa Zod para validación de formularios siguiendo este patrón:

```tsx
// Establece validaciones claras para cada campo
const entidadSchema = z.object({
  nombre: z.string().min(1, "El nombre es obligatorio"),
  telefono: z.string().regex(/^\d{10}$/, "Debe ser un teléfono válido de 10 dígitos"),
  email: z.string().email("Formato de correo inválido"),
  fecha: z.string().refine(
    (val) => /^\d{4}-\d{2}-\d{2}$/.test(val),
    { message: "Formato de fecha incorrecto (YYYY-MM-DD)" }
  )
});

// Para validaciones entre campos relacionados
schema.refine(
  (data) => data.fechaInicio <= data.fechaFin,
  {
    message: "La fecha de inicio debe ser anterior a la fecha final",
    path: ["fechaInicio"] // Campo donde se mostrará el error
  }
);
```

### Uso de Tabs para formularios extensos

Organiza formularios extensos en pestañas temáticas:

```tsx
<FormTabContainer defaultValue="general">
  <FormTab value="general" label="Información General">
    {/* Datos básicos */}
  </FormTab>
  
  <FormTab value="contacto" label="Información de Contacto">
    {/* Datos de contacto */}
  </FormTab>
  
  <FormTab value="avanzado" label="Configuración Avanzada">
    {/* Configuraciones especiales */}
  </FormTab>
</FormTabContainer>
```

### Notificaciones estándar

Utiliza el sistema unificado Sonner para notificaciones:

```tsx
// En componentes del cliente
import { toast } from "sonner";

// Notificación de éxito
toast.success("Operación completada", {
  position: "bottom-right",
  duration: 2000
});

// Notificación de error
toast.error("Ha ocurrido un error", {
  position: "bottom-right",
  duration: 3000
});

// En el backend (controladores)
session()->flash('success', 'Registro guardado correctamente');
```
```

#### Paso 2: Crear el componente de formulario específico

```tsx
// user-form.tsx
import React from "react";
import { FormField, FormItem, FormLabel, FormControl, FormMessage, FormDescription } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { FormSection } from "@/components/base-form/form-section";
import { FormTabContainer, FormTab } from "@/components/base-form/form-tab";
import { useFormContext } from "@/components/base-form/base-form";
import { UserIcon, KeyIcon, ShieldIcon } from "lucide-react";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { InfoIcon } from "lucide-react";

export function UserForm({ options }: { options: BaseFormOptions<User> }) {
  // Obtener el formulario del contexto
  const { form, isSubmitting } = useFormContext<User>();
  
  // Estados para la UI
  const [availableRoles, setAvailableRoles] = useState<RoleInfo[]>([]);
  const [passwordVisible, setPasswordVisible] = useState<boolean>(false);
  const [confirmPasswordVisible, setConfirmPasswordVisible] = useState<boolean>(false);
  const [generatedPassword, setGeneratedPassword] = useState<string>("");

  // Determinar si es formulario de edición o creación
  const isEditing = options.isEdit;

  return (
    <div className="user-form-container">
      <div className="mb-5">
        <Badge className={isEditing 
          ? "px-2 py-1 bg-blue-100 text-blue-800" 
          : "px-2 py-1 bg-emerald-100 text-emerald-800"
        }>
          {isEditing ? 'Edición de usuario' : 'Nuevo usuario'}
        </Badge>
      </div>
      
      <FormTabContainer defaultValue={options.defaultTab || "general"} className="shadow-sm rounded-lg border">
        <FormTab value="general" label="Información General" icon={<UserIcon className="h-4 w-4" />}>
          <FormSection title="Datos personales" columns={2}>
            <FormField
              control={form.control}
              name="name"
            render={({ field }) => (
              <FormItem>
                <FormLabel>Nombre</FormLabel>
                <FormControl>
                  <Input placeholder="Nombre completo" {...field} />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
          
          <FormField
            control={form.control}
            name="email"
            render={({ field }) => (
              <FormItem>
                <div className="flex items-center gap-1">
                  <FormLabel>Correo electrónico</FormLabel>
                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger asChild>
                        <InfoIcon className="h-4 w-4 text-muted-foreground cursor-help" />
                      </TooltipTrigger>
                      <TooltipContent>
                        <p className="w-[200px] text-sm">Debe ser único en el sistema y será usado para iniciar sesión</p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                </div>
                <FormControl>
                  <Input type="email" placeholder="correo@ejemplo.com" {...field} />
                </FormControl>
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
                  <FormLabel>Usuario activo</FormLabel>
                  <FormDescription>
                    Si está desactivado, el usuario no podrá iniciar sesión
                  </FormDescription>
                </div>
              </FormItem>
            )}
          />
        </FormSection>
      </FormTab>
      
      <FormTab value="security" label="Seguridad" icon={<KeyIcon className="h-4 w-4" />}>
        <FormSection title="Contraseña" description="Establezca una contraseña segura para el usuario">
          <FormField
            control={form.control}
            name="password"
            render={({ field }) => (
              <FormItem>
                <FormLabel>Contraseña</FormLabel>
                <FormDescription>
                  {options.isEdit ? "Dejar en blanco para mantener la contraseña actual" : "Mínimo 8 caracteres, incluir mayúsculas, minúsculas y números"}
                </FormDescription>
                <FormControl>
                  <Input type="password" placeholder="••••••••" {...field} value={field.value || ""} />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
          
          <FormField
            control={form.control}
            name="password_confirmation"
            render={({ field }) => (
              <FormItem>
                <FormLabel>Confirmar contraseña</FormLabel>
                <FormControl>
                  <Input type="password" placeholder="••••••••" {...field} value={field.value || ""} />
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        </FormSection>
      </FormTab>
      
      <FormTab value="roles" label="Roles" icon={<ShieldIcon className="h-4 w-4" />}>
        <FormSection title="Roles asignados" description="Asigne uno o más roles al usuario">
          <FormField
            control={form.control}
            name="roles"
            render={() => (
              <FormItem>
                <div className="mb-4">
                  <FormLabel>Roles del usuario</FormLabel>
                  <FormDescription>
                    Los roles determinan qué permisos tendrá el usuario en el sistema
                  </FormDescription>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  {["admin", "editor", "viewer", "user"].map((role) => (
                    <FormField
                      key={role}
                      control={form.control}
                      name="roles"
                      render={({ field }) => {
                        return (
                          <FormItem
                            key={role}
                            className="flex flex-row items-start space-x-3 space-y-0 rounded-md border p-4"
                          >
                            <FormControl>
                              <Checkbox
                                checked={field.value?.includes(role)}
                                onCheckedChange={(checked) => {
                                  return checked
                                    ? field.onChange([...(field.value || []), role])
                                    : field.onChange(
                                        field.value?.filter(
                                          (value) => value !== role
                                        )
                                      )
                                }}
                              />
                            </FormControl>
                            <div className="space-y-1 leading-none">
                              <FormLabel className="text-base">
                                {role.charAt(0).toUpperCase() + role.slice(1)}
                              </FormLabel>
                              <FormDescription>
                                {getRoleDescription(role)}
                              </FormDescription>
                            </div>
                          </FormItem>
                        )
                      }}
                    />
                  ))}
                </div>
                <FormMessage />
              </FormItem>
            )}
          />
        </FormSection>
      </FormTab>
    </FormTabContainer>
  );
}

// Función auxiliar para obtener la descripción de cada rol
function getRoleDescription(role: string): string {
  const descriptions: Record<string, string> = {
    admin: "Acceso completo a todas las funcionalidades del sistema",
    editor: "Puede ver y editar usuarios, pero no crear o eliminar",
    viewer: "Solo puede ver información, sin capacidad de modificación",
    user: "Acceso básico con permisos mínimos",
  };
  
  return descriptions[role] || "Sin descripción disponible";
}
```

#### Paso 3: Crear las páginas de creación y edición

```tsx
// pages/users/create.tsx
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { UserForm } from "./user-form";
import { userSchema } from "./schema";

export default function CreateUser() {
  const formOptions = {
    title: "Crear Usuario",
    subtitle: "Complete el formulario para registrar un nuevo usuario en el sistema",
    endpoint: "/users",
    moduleName: "users",
    isEdit: false,
    breadcrumbs: [
      { title: "Dashboard", href: "/dashboard" },
      { title: "Usuarios", href: "/users" },
      { title: "Crear", href: "/users/create" },
    ],
    defaultTab: "general",
    tabs: [
      { value: "general", label: "Información General" },
      { value: "security", label: "Seguridad" },
      { value: "roles", label: "Roles" },
    ],
    permissions: {
      create: "users.create",
    },
    actions: {
      save: {
        label: "Crear Usuario",
        disabledText: "No tienes permisos para crear usuarios",
      },
      cancel: {
        label: "Cancelar",
        href: "/users",
      },
    },
  };

  return (
    <BaseFormPage
      options={formOptions}
      schema={userSchema}
      defaultValues={{
        name: "",
        email: "",
        active: true,
        roles: [],
      }}
      FormComponent={UserForm}
    />
  );
}
```

```tsx
// pages/users/edit.tsx
import { BaseFormPage } from "@/components/base-form/base-form-page";
import { UserForm } from "./user-form";
import { userSchema } from "./schema";

interface EditUserProps {
  user: {
    id: number;
    name: string;
    email: string;
    active: boolean;
    roles: string[];
  };
  errors?: Record<string, string>;
}

export default function EditUser({ user, errors = {} }: EditUserProps) {
  const formOptions = {
    title: "Editar Usuario",
    subtitle: `Modificando información de ${user.name}`,
    endpoint: `/users/${user.id}`,
    moduleName: "users",
    isEdit: true,
    recordId: user.id,
    breadcrumbs: [
      { title: "Dashboard", href: "/dashboard" },
      { title: "Usuarios", href: "/users" },
      { title: "Editar", href: `/users/${user.id}/edit` },
    ],
    defaultTab: "general",
    tabs: [
      { value: "general", label: "Información General" },
      { value: "security", label: "Seguridad" },
      { value: "roles", label: "Roles" },
    ],
    permissions: {
      edit: "users.edit",
    },
    actions: {
      save: {
        label: "Actualizar Usuario",
        disabledText: "No tienes permisos para editar usuarios",
      },
      cancel: {
        label: "Cancelar",
        href: "/users",
      },
    },
  };

  return (
    <BaseFormPage
      options={formOptions}
      schema={userSchema}
      defaultValues={user}
      serverErrors={errors}
      FormComponent={UserForm}
    />
  );
}
```

### Creación manual de un formulario

Si necesitas mayor flexibilidad, puedes usar directamente el componente `BaseForm`:

```tsx
import { BaseForm } from "@/components/base-form/base-form";
import { z } from "zod";
import { useForm } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";

// Definir esquema y componente personalizado...

export default function CustomForm() {
  const formOptions = {
    // Configuración...
  };
  
  const form = useForm({
    resolver: zodResolver(schema),
    defaultValues: {
      // Valores iniciales...
    },
  });
  
  return (
    <BaseForm 
      options={formOptions}
      schema={schema}
      defaultValues={defaultValues}
    >
      {/* Contenido del formulario */}
    </BaseForm>
  );
}
```

## Configuración avanzada

### Esquemas de validación

El sistema utiliza Zod para validación, permitiendo definir esquemas complejos con validaciones personalizadas:

```tsx
const schema = z.object({
  // Campos con validaciones...
}).refine((data) => {
  // Validaciones condicionales o inter-campos
  return true/false;
}, {
  message: "Mensaje de error",
  path: ["campo_específico"] // Opcional, para asociar el error a un campo
});
```

### Formularios con tabs

Para formularios complejos, utiliza los componentes `FormTabContainer` y `FormTab`:

```tsx
<FormTabContainer defaultValue="general">
  <FormTab value="general" label="General" icon={<Icon />}>
    {/* Contenido de la pestaña */}
  </FormTab>
  
  <FormTab value="advanced" label="Avanzado" icon={<Icon />}>
    {/* Contenido de la pestaña */}
  </FormTab>
</FormTabContainer>
```

### Secciones de formulario

Organiza campos relacionados en secciones:

```tsx
<FormSection 
  title="Información personal" 
  description="Datos básicos del usuario"
  columns={2}
  collapsible={true}
  defaultOpen={true}
>
  {/* Campos del formulario */}
</FormSection>
```

### Manejo de errores

El sistema incluye un resumen de errores con navegación directa a los campos erróneos:

```tsx
<FormErrorSummary 
  errors={form.formState.errors} 
  labels={labelMap}
/>
```

### Control de acciones

Configura los botones de acción con opciones personalizadas:

```tsx
actions: {
  save: {
    label: "Guardar cambios",
    variant: "default",
    disabledText: "Sin permisos",
  },
  cancel: {
    label: "Volver",
    variant: "outline",
    href: "/ruta/retorno",
  },
}
```

### Campos con ayuda contextual

Añade tooltips a los campos para proporcionar ayuda contextual:

```tsx
<div className="flex items-center gap-1">
  <FormLabel>Campo</FormLabel>
  <TooltipProvider>
    <Tooltip>
      <TooltipTrigger asChild>
        <InfoIcon className="h-4 w-4 text-muted-foreground cursor-help" />
      </TooltipTrigger>
      <TooltipContent>
        <p>Texto de ayuda para este campo</p>
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>
</div>
```

## Integración con Backend

El patrón Base Form está diseñado para integrarse perfectamente con:

1. **Form Requests de Laravel** para validación
2. **Sistema de permisos Spatie** para autorización
3. **Notificaciones Sonner** para feedback al usuario
4. **Inertia.js** para navegación sin recargar la página

## Notificaciones

El sistema de formularios utiliza el sistema de notificaciones basado en Sonner existente:

```tsx
import { toast } from "sonner";

// En caso de éxito (manejado automáticamente por el sistema flash del backend)
// Para notificaciones manuales desde el cliente
toast.success("Operación completada con éxito");

### Componentes especializados

#### Multi-selección con checkboxes (para roles u opciones múltiples)

Implementa selección múltiple siguiendo este patrón:

```tsx
<FormField
  control={form.control}
  name="roles"
  render={() => (
    <FormItem>
      <FormLabel>Roles asignados</FormLabel>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        {availableRoles.map((role) => (
          <FormField
            key={role.name}
            control={form.control}
            name="roles"
            render={({ field }) => (
              <FormItem className="flex flex-row items-start space-x-3 space-y-0 rounded-lg border p-4">
                <FormControl>
                  <Checkbox
                    checked={field.value?.includes(role.name)}
                    onCheckedChange={(checked) => {
                      return checked
                        ? field.onChange([...(field.value || []), role.name])
                        : field.onChange(
                            field.value?.filter(value => value !== role.name)
                          )
                    }}
                  />
                </FormControl>
                <div className="space-y-1 leading-none">
                  <FormLabel className="text-base">
                    {role.label || role.name}
                  </FormLabel>
                  <FormDescription>
                    {role.description}
                  </FormDescription>
                </div>
              </FormItem>
            )}
          />
        ))}
      </div>
    </FormItem>
  )}
/>
```

#### Generador de contraseñas seguras

Integra generación de contraseñas siguiendo este patrón:

```tsx
// Estado para la contraseña generada
const [generatedPassword, setGeneratedPassword] = useState<string>("");

// Función de generación
const generateSecurePassword = () => {
  // Lógica para generar contraseña segura...
  const password = /* algoritmo de generación */;
  setGeneratedPassword(password);
};

// En el formulario
<div className="border border-dashed p-4 rounded-lg">
  <div className="flex justify-between mb-2">
    <h4>Generador de contraseñas</h4>
    <Button onClick={generateSecurePassword} size="sm">Generar</Button>
  </div>
  
  {generatedPassword && (
    <div className="bg-muted/20 p-3 rounded-md">
      <div className="flex justify-between">
        <code className="font-mono">{generatedPassword}</code>
        <Button onClick={() => {
          form.setValue("password", generatedPassword);
          form.setValue("password_confirmation", generatedPassword);
          toast.success("Contraseña aplicada");
        }} size="sm">Aplicar</Button>
      </div>
    </div>
  )}
</div>
```

Recuerda siempre seguir el patrón documentado para mantener la consistencia entre todos los módulos.

## Sistema de permisos

El patrón utiliza el hook `usePermissions` para verificar los permisos del usuario:

```tsx
const { can } = usePermissions();

// Verificar si el usuario puede realizar una acción
if (can("users.create")) {
  // Acción permitida
}
```

## Ejemplos completos

Ver los ejemplos de UserForm y CreateUser/EditUser en esta documentación.
