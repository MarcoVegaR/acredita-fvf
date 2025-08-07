import React, { useState } from "react";
import { Button } from "@/components/ui/button";
import { X, ChevronDown } from "lucide-react";
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuTrigger 
} from "@/components/ui/dropdown-menu";
import { usePermissions } from "@/hooks/usePermissions";
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from "@/components/ui/alert-dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Textarea } from "@/components/ui/textarea";

export interface BulkAction<T> {
  label: string;
  icon?: React.ReactNode;
  permission?: string | string[];
  showCondition?: (selectedRows: T[]) => boolean;
  confirmMessage?: string | ((selectedRows: T[]) => string);
  confirmTitle?: string;
  requiresReason?: boolean;
  reasonLabel?: string;
  reasonPlaceholder?: string;
  handler: (selectedRows: T[], reason?: string) => void;
}

interface BulkActionsBarProps<T> {
  selectedRows: T[];
  bulkActions: BulkAction<T>[];
  onClearSelection: () => void;
}

export function BulkActionsBar<T>({ 
  selectedRows, 
  bulkActions, 
  onClearSelection 
}: BulkActionsBarProps<T>) {
  const { can } = usePermissions();
  const [confirmDialog, setConfirmDialog] = useState<{
    isOpen: boolean;
    action: BulkAction<T> | null;
  }>({
    isOpen: false,
    action: null,
  });
  const [reason, setReason] = useState("");

  // Filtrar acciones disponibles basadas en permisos y condiciones
  const availableActions = bulkActions.filter(action => {
    // Verificar permisos
    if (action.permission) {
      if (Array.isArray(action.permission)) {
        if (!action.permission.some(permission => can(permission))) {
          return false;
        }
      } else {
        if (!can(action.permission)) {
          return false;
        }
      }
    }
    
    // Verificar condiciones de mostrado
    if (action.showCondition && !action.showCondition(selectedRows)) {
      return false;
    }
    
    return true;
  });

  const handleActionClick = (action: BulkAction<T>) => {
    // Cerrar el menú desplegable antes de ejecutar la acción
    document.body.click();
    
    // Pequeño retraso para asegurar que el menú se cierre primero
    setTimeout(() => {
      if (action.confirmMessage || action.requiresReason) {
        // Mostrar diálogo de confirmación
        setConfirmDialog({
          isOpen: true,
          action: action
        });
      } else {
        // Ejecutar directamente
        action.handler(selectedRows);
        onClearSelection();
      }
    }, 100);
  };

  const handleConfirm = () => {
    if (!confirmDialog.action) return;

    // Ejecutar la acción
    confirmDialog.action.handler(selectedRows, reason || undefined);
    
    // Limpiar estado
    setConfirmDialog({ isOpen: false, action: null });
    setReason("");
    onClearSelection();
  };

  const handleCancel = () => {
    setConfirmDialog({ isOpen: false, action: null });
    setReason("");
  };

  if (selectedRows.length === 0) {
    return null;
  }

  return (
    <>
      <div className="flex items-center justify-between p-3 bg-blue-50 border border-blue-200 rounded-md">
        <div className="flex items-center gap-3">
          <div className="text-sm font-medium text-blue-900">
            {selectedRows.length} elemento{selectedRows.length !== 1 ? 's' : ''} seleccionado{selectedRows.length !== 1 ? 's' : ''}
          </div>
          
          {availableActions.length > 0 && (
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="default" size="sm" className="flex items-center gap-2">
                  Acciones
                  <ChevronDown className="h-4 w-4" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="start" className="w-56">
                {availableActions.map((action, index) => (
                  <DropdownMenuItem
                    key={index}
                    onClick={() => handleActionClick(action)}
                    className="flex items-center gap-2 cursor-pointer"
                  >
                    {action.icon}
                    <span>{action.label}</span>
                  </DropdownMenuItem>
                ))}
              </DropdownMenuContent>
            </DropdownMenu>
          )}
        </div>
        
        <Button
          variant="ghost"
          size="sm"
          onClick={onClearSelection}
          className="h-8 w-8 p-0"
        >
          <X className="h-4 w-4" />
        </Button>
      </div>

      {/* Diálogo de confirmación para acciones masivas */}
      <AlertDialog open={confirmDialog.isOpen} onOpenChange={(open) => {
        if (!open) {
          handleCancel();
          // Si el diálogo se cierra, desenfocar elementos activos
          if (document.activeElement instanceof HTMLElement) {
            document.activeElement.blur();
            // Forzar el foco al cuerpo para evitar problemas
            setTimeout(() => document.body.focus(), 50);
          }
        }
      }}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {confirmDialog.action?.confirmTitle || confirmDialog.action?.label}
            </AlertDialogTitle>
            <AlertDialogDescription asChild>
              <div className="space-y-4">
                <p>
                  {typeof confirmDialog.action?.confirmMessage === 'function' 
                    ? confirmDialog.action.confirmMessage(selectedRows)
                    : confirmDialog.action?.confirmMessage || 
                      `¿Está seguro que desea ejecutar esta acción en ${selectedRows.length} elemento${selectedRows.length !== 1 ? 's' : ''}?`
                  }
                </p>
                
                {confirmDialog.action?.requiresReason && (
                  <div className="space-y-2">
                    <Label htmlFor="bulk-reason">
                      {confirmDialog.action.reasonLabel || 'Motivo'}
                    </Label>
                    <Textarea
                      id="bulk-reason"
                      placeholder={confirmDialog.action.reasonPlaceholder || 'Ingrese el motivo (opcional)'}
                      value={reason}
                      onChange={(e) => setReason(e.target.value)}
                      className="min-h-[80px]"
                    />
                  </div>
                )}
              </div>
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={handleCancel}>
              Cancelar
            </AlertDialogCancel>
            <AlertDialogAction onClick={handleConfirm}>
              {confirmDialog.action?.label || 'Confirmar'}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  );
}
