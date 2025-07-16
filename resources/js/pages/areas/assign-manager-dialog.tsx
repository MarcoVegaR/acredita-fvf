import React, { useState, useEffect } from "react";
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter, DialogDescription } from "@/components/ui/dialog";
import { Button } from "@/components/ui/button";
import { Combobox } from "@/components/ui/combobox";
import { Loader2, UserCheck } from "lucide-react";
import { toast } from "sonner";
import axios from "axios";
import { Badge } from "@/components/ui/badge";
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

interface AssignManagerDialogProps {
  area: Area;
  isOpen: boolean;
  onClose: () => void;
  onSuccess: () => void;
}

export function AssignManagerDialog({ area, isOpen, onClose, onSuccess }: AssignManagerDialogProps) {
  const [loading, setLoading] = useState<boolean>(false);
  const [submitting, setSubmitting] = useState<boolean>(false);
  const [availableManagers, setAvailableManagers] = useState<User[]>([]);
  const [selectedManagerId, setSelectedManagerId] = useState<string | null>(
    area.manager_user_id ? area.manager_user_id.toString() : null
  );
  
  // Log cuando cambia la selección de gerente
  const handleManagerSelection = (value: string) => {
    console.log('[AssignManagerDialog] Intentando seleccionar gerente con ID:', value);
    setSelectedManagerId(value);
    console.log('[AssignManagerDialog] Estado selectedManagerId actualizado a:', value);
  };
  const [currentManager] = useState<User | null>(area.manager || null);

  // Cargar usuarios con rol de area_manager
  useEffect(() => {
    const fetchAvailableManagers = async () => {
      try {
        setLoading(true);
        const response = await axios.get('/area-managers/available', { 
          params: { except_area_id: area.id }
        });
        setAvailableManagers(response.data);
        setLoading(false);
      } catch (error: unknown) {
        console.error("Error al cargar gerentes disponibles:", error);
        toast.error("No se pudieron cargar los usuarios disponibles");
        setLoading(false);
      }
    };
    
    fetchAvailableManagers();
  }, [area.id]);

  // Manejar la asignación de gerente
  const handleAssignManager = async () => {
    if (!selectedManagerId) {
      toast.error("Debes seleccionar un gerente");
      return;
    }
    
    try {
      setSubmitting(true);
      
      // Realizar la solicitud AJAX
      await axios.post(`/areas/${area.id}/assign-manager`, {
        manager_user_id: parseInt(selectedManagerId)
      });
      
      // Mostrar notificación de éxito
      toast.success("Gerente asignado correctamente");
      
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
    } catch (error: unknown) { // Tipamos como unknown y verificamos la estructura
      console.error("Error al asignar gerente:", error);
      
      // Extract the specific error message from the API response
      let errorMessage = "No se pudo asignar el gerente al área";
      
      // Verificar si el error tiene la estructura esperada de un error de Axios
      if (error && typeof error === 'object' && 'response' in error && 
          error.response && typeof error.response === 'object' && 'data' in error.response) {
        const axiosError = error.response as { data?: { message?: string, error?: string } };
        // Use the message from the API if available
        if (axiosError.data?.message) {
          errorMessage = axiosError.data.message;
        } else if (axiosError.data?.error && typeof axiosError.data.error === 'string') {
          errorMessage = axiosError.data.error;
        }
      }
      
      toast.error(errorMessage);
      setSubmitting(false);
    }
  };

  // Establecer el título y la acción principal
  const dialogTitle = currentManager 
    ? "Cambiar gerente del área" 
    : "Asignar gerente al área";
    
  const actionButtonText = "Asignar gerente";
    
  // Mejorar manejo del evento onOpenChange para evitar problemas de foco
  const handleOpenChange = (open: boolean) => {
    console.log('[AssignManagerDialog] handleOpenChange llamado con:', open, 'document.activeElement:', document.activeElement);
    
    // Si es un click en el combobox, no cerrar el diálogo
    const activeElement = document.activeElement;
    const isComboboxOrChild = activeElement?.closest('[role="combobox"]') || 
                             activeElement?.closest('.popover-content') ||
                             activeElement?.closest('[role="option"]');
                             
    if (!open && isComboboxOrChild) {
      console.log('[AssignManagerDialog] Detectado clic en combobox, previniendo cierre', isComboboxOrChild);
      return; // No cerrar el diálogo si el clic fue en el combobox
    }
    
    if (!open) {
      console.log('[AssignManagerDialog] Cerrando diálogo');
      
      // Si el diálogo se cierra, desenfocar elementos activos
      if (document.activeElement instanceof HTMLElement) {
        document.activeElement.blur();
        console.log('[AssignManagerDialog] Elemento desenfocado');
      }
      
      // Llamar a onClose (sin navegación Inertia aquí)
      onClose();
      
      // Forzar el foco al cuerpo para evitar problemas, con un pequeño retraso
      // Este enfoque funciona porque no estamos recargando la página en este punto
      setTimeout(() => {
        document.body.focus();
        console.log('[AssignManagerDialog] Foco forzado al body');
      }, 50);
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={handleOpenChange}>
      <DialogContent className="sm:max-w-[550px] max-h-[90vh] overflow-y-auto" onInteractOutside={(e) => {
        // Prevenir que clicks en elementos del Combobox cierren el diálogo
        console.log('[AssignManagerDialog] onInteractOutside', e.target);
        if ((e.target as HTMLElement)?.closest('[role="combobox"]') ||
            (e.target as HTMLElement)?.closest('.popover-content') ||
            (e.target as HTMLElement)?.closest('[role="option"]')) {
          console.log('[AssignManagerDialog] Previniendo cierre por interacción con combobox');
          e.preventDefault();
        }
      }} onEscapeKeyDown={() => handleOpenChange(false)}>
        <DialogHeader>
          <DialogTitle className="text-xl">
            {dialogTitle}
          </DialogTitle>
          <DialogDescription>
            Área: <span className="font-medium">{area.name}</span> <Badge variant="outline">{area.code}</Badge>
          </DialogDescription>
        </DialogHeader>
        
        {/* Mostrar gerente actual si existe */}
        {currentManager && (
          <div className="mb-4">
            <h4 className="text-sm font-medium mb-2 flex items-center">
              <UserCheck className="h-4 w-4 mr-1.5 text-green-600" />
              Gerente actual
            </h4>
            <Card className="p-3">
              <div className="flex items-center gap-3">
                <div className="h-10 w-10 shrink-0 rounded-full bg-primary/10 flex items-center justify-center text-primary">
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
        )}
        
        {/* Selector de nuevo gerente */}
        <div className="py-2">
          <label className="text-sm font-medium mb-2 block">
            {currentManager ? "Seleccionar nuevo gerente" : "Seleccionar gerente"}
          </label>
            
          {loading ? (
            <div className="flex h-10 w-full items-center rounded-md border border-input bg-background px-3 py-2 text-sm">
              <Loader2 className="h-4 w-4 animate-spin mr-2" />
              <span>Cargando usuarios disponibles...</span>
            </div>
          ) : (
            <Combobox
              options={availableManagers.map(user => ({
                value: user.id.toString(),
                label: `${user.name} (${user.email})`
              }))}
              value={selectedManagerId || ""}
              onChange={handleManagerSelection}
              placeholder="Seleccione un usuario..."
              searchPlaceholder="Buscar usuario..."
              emptyMessage="No hay usuarios disponibles con rol de gerente"
              className="w-full"
            />
          )}
        </div>
        
        {/* Aviso informativo sobre la asignación de gerente */}
        <div className="bg-amber-50 border border-amber-200 text-amber-800 p-3 rounded-md my-4 text-wrap">
          <h5 className="font-medium mb-1">Información importante</h5>
          <p className="text-sm">
            Al asignar un gerente a un área, automáticamente se {currentManager ? "actualizará" : "creará"} un proveedor de tipo interno vinculado a este usuario. Este proveedor interno podrá gestionar los proveedores externos del área.
          </p>
        </div>

        <DialogFooter className="flex flex-col sm:flex-row gap-2 mt-4">
          <Button 
            variant="outline" 
            onClick={onClose}
            disabled={submitting}
            type="button"
            className="w-full sm:w-auto"
          >
            Cancelar
          </Button>
          
          <Button 
            onClick={handleAssignManager}
            // Habilitamos el botón solo si el gerente seleccionado es diferente del actual
            disabled={(selectedManagerId === (area.manager_user_id?.toString() || null) || !selectedManagerId) || submitting}
            type="button"
            className="w-full sm:w-auto"
          >
            {submitting ? (
              <>
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                Asignando...
              </>
            ) : (
              actionButtonText
            )}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
