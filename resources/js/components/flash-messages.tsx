import { useEffect } from "react";
import { usePage } from "@inertiajs/react";
import { toast } from "sonner";

interface PageProps {
  flash?: {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
    [key: string]: string | undefined;
  };
  errors?: Record<string, string[]>;
  [key: string]: unknown; // Permite índices adicionales requeridos por Inertia
}

export function FlashMessages() {
  const props = usePage<PageProps>().props;
  
  // Handle flash messages from Laravel session via Inertia
  useEffect(() => {
    // Verificamos que flash exista para evitar errores
    const flash = props.flash || {};
    const errors = props.errors || {};

    // Process success message
    if (flash.success) {
      toast.success(flash.success, {
        description: "Operación completada correctamente",
      });
    }

    // Process error message
    if (flash.error) {
      toast.error(flash.error, {
        description: "Ocurrió un error durante la operación",
      });
    }

    // Process warning message
    if (flash.warning) {
      toast.warning(flash.warning, {
        description: "Advertencia del sistema",
      });
    }

    // Process info message
    if (flash.info) {
      toast.info(flash.info, {
        description: "Información del sistema",
      });
    }

    // Process validation errors
    if (Object.keys(errors).length > 0) {
      const errorMessages = Object.values(errors).flat();
      if (errorMessages.length > 0) {
        toast.error("Error de validación", {
          description: errorMessages.join(". "),
        });
      }
    }
  }, [props]);

  // No need to render anything as Sonner uses the Toaster component
  return null;
}
