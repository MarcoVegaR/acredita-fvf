import React, { createContext, useContext, useState } from "react";
import { Form as FormRoot } from "@/components/ui/form";
import { toast } from "sonner";
import { useForm, UseFormReturn, SubmitHandler, DefaultValues } from "react-hook-form";
import { zodResolver } from "@hookform/resolvers/zod";
import { z } from "zod";
// Importaciones correctas con rutas relativas
import { FormErrorSummary } from "@/components/base-form/form-error-summary";
import { FormHeader } from "@/components/base-form/form-header";
import { FormActions } from "@/components/base-form/form-actions";
import { usePermissions } from "@/hooks/usePermissions";
import { router } from "@inertiajs/react";

// Define el contexto del formulario para compartir el estado entre componentes
interface FormContextType<T extends Record<string, any>> {
  form: UseFormReturn<T>;
  isSubmitting: boolean;
  options: BaseFormOptions<T>;
}

const FormContext = createContext<FormContextType<any> | undefined>(undefined);

// Hook para acceder al contexto del formulario
export function useFormContext<T extends Record<string, any>>() {
  const context = useContext(FormContext);
  if (!context) {
    throw new Error("useFormContext debe ser usado dentro de un BaseForm");
  }
  return context as FormContextType<T>;
}

export interface ButtonConfig {
  label?: string;
  icon?: React.ReactNode;
  variant?: "default" | "outline" | "secondary" | "ghost" | "link" | "destructive";
}

export interface TabConfig {
  value: string;
  label: string;
  icon?: React.ReactNode;
}

export interface BaseFormOptions<T> {
  // Información general
  title: string;
  subtitle?: string;
  endpoint: string;
  moduleName: string;
  isEdit: boolean;
  recordId?: number | string;
  
  // Navegación
  breadcrumbs: { title: string; href: string }[];
  
  // Configuración de tabs para formularios complejos
  tabs?: TabConfig[];
  defaultTab?: string;
  
  // Permisos
  permissions: {
    create?: string;
    edit?: string;
    view?: string;
  };
  
  // Callbacks
  onSuccess?: (page: any) => void;
  beforeSubmit?: (data: T) => T | Promise<T>;
  onCancel?: () => void;
  
  // Acciones
  actions?: {
    save: ButtonConfig & { 
      disabledText?: string;
    };
    cancel: ButtonConfig & { 
      href?: string;
    };
  };
}

// Eliminamos la extensión de ComponentPropsWithoutRef para evitar errores de tipado
interface BaseFormProps<T extends Record<string, any>> {
  options: BaseFormOptions<T>;
  schema: z.ZodType<T>;
  defaultValues: Partial<T>;
  serverErrors?: Record<string, string>;
  children: React.ReactNode;
}

export function BaseForm<T extends Record<string, any>>({
  options,
  schema,
  defaultValues,
  serverErrors = {},
  children,
  // eslint-disable-next-line @typescript-eslint/no-unused-vars
  ...formProps
}: BaseFormProps<T>) {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const { can } = usePermissions();

  // Configurar react-hook-form con zod para validación
  const form = useForm<T>({
    resolver: zodResolver(schema),
    defaultValues: defaultValues as DefaultValues<T>,
    mode: "onChange",
  });

  // Procesar errores del servidor
  React.useEffect(() => {
    // Si hay errores del servidor, mapearlos a los campos correspondientes
    if (serverErrors && Object.keys(serverErrors).length > 0) {
      Object.entries(serverErrors).forEach(([key, value]) => {
        form.setError(key as any, { 
          type: "server", 
          message: value as string 
        });
      });
      
      // Notificar error global si existe
      if (serverErrors._error) {
        toast.error(serverErrors._error);
      }
      
      // Scroll al resumen de errores
      document.getElementById("form-error-summary")?.scrollIntoView({
        behavior: "smooth",
      });
    }
  }, [serverErrors, form]);

  // Manejar el envío del formulario
  const handleSubmit: SubmitHandler<T> = async (data) => {
    try {
      setIsSubmitting(true);
      
      // Procesar los datos antes de enviar si se especifica beforeSubmit
      let processedData = { ...data };
      if (options.beforeSubmit) {
        processedData = await options.beforeSubmit(data);
      }
      
      // Determinar el método HTTP basado en si es edición o creación
      const method = options.isEdit ? "put" : "post";
      
      // Nota: La URL del endpoint debe ser correcta sin concatenación adicional del ID
      // En EditUser ya se configura como `/users/${user.id}` por ejemplo
      
      // Enviar al servidor usando Inertia
      router.visit(options.endpoint, {
        method,
        data: processedData,
        onSuccess: (page: any) => {
          // Las notificaciones flash del servidor son manejadas por FlashMessages
          // Callback adicional para lógica del cliente si es necesario
          if (options.onSuccess) options.onSuccess(page);
        },
        onError: (errors) => {
          // Asignar errores del servidor a los campos correspondientes
          Object.keys(errors).forEach(key => {
            form.setError(key as any, { 
              type: "server", 
              message: errors[key] 
            });
          });
          
          // Notificación de error global si corresponde
          if ("_error" in errors) {
            toast.error(errors._error as string);
          }
          
          // Scroll al resumen de errores
          document.getElementById("form-error-summary")?.scrollIntoView({
            behavior: "smooth",
          });
        },
        onFinish: () => {
          setIsSubmitting(false);
        }
      });
    } catch (error) {
      setIsSubmitting(false);
      toast.error("Ocurrió un error al procesar el formulario");
      console.error(error);
    }
  };

  // Manejar la cancelación
  const handleCancel = () => {
    if (options.onCancel) {
      options.onCancel();
    } else if (options.actions?.cancel.href) {
      router.visit(options.actions.cancel.href);
    } else {
      // Redirigir al índice del módulo por defecto
      const parts = options.endpoint.split('/');
      const baseEndpoint = `/${parts[1] || ''}`;
      router.visit(baseEndpoint);
    }
  };

  // Verificar permisos
  const canSubmit = options.isEdit 
    ? can(options.permissions.edit || '') 
    : can(options.permissions.create || '');

  return (
    <FormContext.Provider value={{ form, isSubmitting, options }}>
      <FormRoot {...form}>
        <form onSubmit={form.handleSubmit(handleSubmit)} className="p-4 space-y-6">
          <FormHeader 
            title={options.title} 
            subtitle={options.subtitle}
            breadcrumbs={options.breadcrumbs}
          />
          
          <FormErrorSummary 
            errors={form.formState.errors} 
            labels={getFormLabels(options.moduleName)}
          />
          
          <div className="bg-card rounded-lg border p-6 shadow-sm">
            {children}
          </div>
          
          <FormActions 
            isSubmitting={isSubmitting}
            canSubmit={canSubmit}
            onCancel={handleCancel}
            saveConfig={options.actions?.save}
            cancelConfig={options.actions?.cancel}
          />
        </form>
      </FormRoot>
    </FormContext.Provider>
  );
}

// Función auxiliar para obtener las etiquetas de los campos según el módulo
// En un sistema real, esto se conectaría con tu sistema de traducciones
function getFormLabels(moduleName: string): Record<string, string> {
  const commonLabels = {
    name: "Nombre",
    email: "Correo electrónico",
    password: "Contraseña",
    password_confirmation: "Confirmar contraseña",
    active: "Estado activo",
    roles: "Roles",
  };

  const moduleSpecificLabels: Record<string, Record<string, string>> = {
    users: {
      ...commonLabels,
    },
    roles: {
      name: "Nombre del rol",
      permissions: "Permisos",
    },
  };

  return moduleSpecificLabels[moduleName] || commonLabels;
}
