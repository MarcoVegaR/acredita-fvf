import React, { useState } from "react";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle
} from "@/components/ui/alert-dialog";
import { UserMinus, Loader2 } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import axios from "axios";
import { toast } from "sonner";
import { Card } from "@/components/ui/card";
import { router } from "@inertiajs/react";

interface User {
  id: number;
  name: string;
  email: string;
}

interface Area {
  id: number;
  name: string;
  code: string;
  manager_user_id: number | null;
  manager?: User;
}

interface RemoveManagerAlertProps {
  area: Area;
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

export function RemoveManagerAlert({ area, isOpen, onClose, onSuccess }: RemoveManagerAlertProps) {
  const [submitting, setSubmitting] = useState<boolean>(false);
  const currentManager = area.manager;
  
  // Asegurarnos de que tenemos un gerente actual
  if (!currentManager) {
    return null;
  }

  // Manejar la eliminación del gerente
  const handleRemoveManager = async () => {
    try {
      setSubmitting(true);
      
      // Realizar la solicitud AJAX
      await axios.post(`/areas/${area.id}/assign-manager`, {
        manager_user_id: null // Pasar null para quitar el gerente
      });
      
      // Mostrar notificación de éxito
      toast.success("Gerente removido correctamente");
      
      // IMPORTANTE: Navegamos con Inertia ANTES de cerrar el diálogo
      // para mantener el foco dentro del trap de focus mientras se recarga
      router.visit(window.location.pathname, { 
        preserveScroll: true,
        preserveState: true,
        only: ['areas'],
        onFinish: () => {
          // Llamamos a onSuccess después de completar la navegación
          onSuccess();
        }
      });
    } catch (error: unknown) {
      console.error("Error al quitar gerente:", error);
      
      let errorMessage = "No se pudo quitar el gerente del área";
      
      // Tipar error como un objeto de error de axios
      if (error && typeof error === 'object' && 'response' in error && 
          error.response && typeof error.response === 'object' && 'data' in error.response) {
        const axiosError = error.response as { data?: { message?: string } };
        if (axiosError.data?.message) {
          errorMessage = axiosError.data.message;
        } else if (axiosError.data && 'error' in axiosError.data && 
                   typeof axiosError.data.error === 'string') {
          errorMessage = axiosError.data.error;
        }
      }
      
      toast.error(errorMessage);
      setSubmitting(false);
    }
  };

  // Mejorar manejo del evento onOpenChange para evitar problemas de foco
  const handleOpenChange = (open: boolean) => {
    console.log('[RemoveManagerAlert] handleOpenChange llamado con:', open);
    console.log('[RemoveManagerAlert] Elemento con foco actual:', document.activeElement);
    
    if (!open) {
      console.log('[RemoveManagerAlert] Cerrando alert dialog');
      
      // Si el diálogo se cierra, desenfocar elementos activos
      if (document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
        console.log('[RemoveManagerAlert] Elemento desenfocado');
      }
      
      // Llamar a onClose (sin navegación Inertia aquí)
      onClose();
      
      // Forzar el foco al cuerpo para evitar problemas, con un pequeño retraso
      // Este enfoque funciona porque no estamos recargando la página en este punto
      setTimeout(() => {
        document.body.focus();
        console.log('[RemoveManagerAlert] Foco forzado al body');
      }, 50);
    }
  };

  return (
    <AlertDialog open={isOpen} onOpenChange={handleOpenChange}>
      <AlertDialogContent className="sm:max-w-[500px]" onEscapeKeyDown={onClose}>
        <AlertDialogHeader>
          <AlertDialogTitle className="flex items-center text-destructive">
            <UserMinus className="h-5 w-5 mr-2" />
            Quitar gerente del área
          </AlertDialogTitle>
          {/* Usar componentes separados en lugar de anidar div dentro de p */}
          <AlertDialogDescription asChild>
            <p className="text-muted-foreground text-sm">
              Esta acción no puede deshacerse. ¿Confirmas que deseas quitar el gerente?
            </p>
          </AlertDialogDescription>
          
          <div className="mt-2 text-muted-foreground text-sm">
            Área: <span className="font-medium">{area.name}</span> <Badge variant="outline">{area.code}</Badge>
          </div>
        </AlertDialogHeader>
        
        {/* Mostrar gerente actual */}
        <div className="my-4">
          <h4 className="text-sm font-medium mb-2">Gerente a remover:</h4>
          <Card className="p-3 bg-muted/50">
            <div className="flex items-center gap-3">
              <div className="h-10 w-10 shrink-0 rounded-full bg-destructive/10 flex items-center justify-center text-destructive">
                {currentManager.name.charAt(0).toUpperCase()}
              </div>
              <div className="flex flex-col w-full overflow-hidden">
                <span className="text-sm font-medium truncate" title={currentManager.name}>
                  {currentManager.name}
                </span>
                <span className="text-xs text-muted-foreground truncate" title={currentManager.email}>
                  {currentManager.email}
                </span>
              </div>
            </div>
          </Card>
        </div>
        
        {/* Aviso de advertencia */}
        <div className="p-3 rounded-md bg-red-50 border border-red-200 text-red-800 text-sm mb-4">
          <p className="font-medium mb-1">Advertencia</p>
          <p className="text-xs">
            Al quitar el gerente de esta área, se eliminará también el proveedor interno asociado.
            El área quedará sin gerente asignado hasta que se asigne uno nuevo.
          </p>
        </div>

        <AlertDialogFooter>
          <AlertDialogCancel 
            onClick={onClose}
            disabled={submitting}
          >
            Cancelar
          </AlertDialogCancel>
          <AlertDialogAction 
            onClick={handleRemoveManager}
            disabled={submitting}
            className="bg-destructive hover:bg-destructive/90"
          >
            {submitting ? (
              <>
                <Loader2 className="h-4 w-4 animate-spin mr-2" />
                Procesando...
              </>
            ) : (
              <>
                <UserMinus className="h-4 w-4 mr-2" />
                Confirmar y quitar gerente
              </>
            )}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
