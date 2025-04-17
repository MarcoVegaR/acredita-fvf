import * as React from "react";
import { MoreHorizontal, Edit, Trash2, Eye } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
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

interface DataTableRowActionsProps<TData> {
  row: TData;
  actions?: {
    view?: {
      enabled: boolean;
      label?: string;
      handler: (row: TData) => void;
    };
    edit?: {
      enabled: boolean;
      label?: string;
      handler: (row: TData) => void;
    };
    delete?: {
      enabled: boolean;
      label?: string;
      confirmMessage?: string;
      handler: (row: TData) => void;
    };
    custom?: Array<{
      label: string;
      icon?: React.ReactNode;
      handler: (row: TData) => void;
    }>;
  };
}

export function DataTableRowActions<TData extends { id?: number | string }>({
  row,
  actions = {},
}: DataTableRowActionsProps<TData>) {
  // Estado para controlar la visibilidad del diálogo de confirmación de eliminación
  const [showDeleteConfirm, setShowDeleteConfirm] = React.useState(false);
  
  // Remover los console.log de depuración que ya no necesitamos
  
  return (
    <>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" className="h-8 w-8 p-0">
            <span className="sr-only">Abrir menú</span>
            <MoreHorizontal className="h-4 w-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuLabel>Acciones</DropdownMenuLabel>
          
          {actions.view?.enabled && (
            <DropdownMenuItem
              onClick={() => actions.view?.handler(row)}
            >
              <Eye className="mr-2 h-4 w-4" />
              <span>{actions.view.label || "Ver detalles"}</span>
            </DropdownMenuItem>
          )}
          
          {actions.edit?.enabled && (
            <DropdownMenuItem
              onClick={() => actions.edit?.handler(row)}
            >
              <Edit className="mr-2 h-4 w-4" />
              <span>{actions.edit.label || "Editar"}</span>
            </DropdownMenuItem>
          )}
          
          {actions.delete?.enabled && (
            <DropdownMenuItem
              onClick={() => {
                // Cerrar el menú desplegable antes de mostrar el diálogo para evitar problemas de foco
                document.body.click(); // Esto cierra el DropdownMenu
                // Pequeño retraso para asegurar que el menú se cierre primero
                setTimeout(() => {
                  setShowDeleteConfirm(true);
                }, 100);
              }}
              className="text-destructive focus:text-destructive"
            >
              <Trash2 className="mr-2 h-4 w-4" />
              <span>{actions.delete.label || "Eliminar"}</span>
            </DropdownMenuItem>
          )}
          
          {actions.custom && actions.custom.length > 0 && (
            <>
              <DropdownMenuSeparator />
              {actions.custom.map((customAction, index) => (
                <DropdownMenuItem
                  key={index}
                  onClick={() => customAction.handler(row)}
                >
                  {customAction.icon && (
                    <span className="mr-2">{customAction.icon}</span>
                  )}
                  <span>{customAction.label}</span>
                </DropdownMenuItem>
              ))}
            </>
          )}
        </DropdownMenuContent>
      </DropdownMenu>

      {/* Diálogo de confirmación de eliminación SEPARADO del DropdownMenu */}
      {actions.delete?.enabled && (
        <AlertDialog 
          open={showDeleteConfirm} 
          onOpenChange={(open) => {
            setShowDeleteConfirm(open);
            // Si el diálogo se cierra, desenfocar elementos activos
            if (!open && document.activeElement instanceof HTMLElement) {
              document.activeElement.blur();
              // Forzar el foco al cuerpo para evitar problemas
              setTimeout(() => document.body.focus(), 50);
            }
          }}
        >
          {/* AlertDialogTrigger eliminado - lo manejamos manualmente */}
          <AlertDialogContent>
            <AlertDialogHeader>
              <AlertDialogTitle>¿Estás seguro?</AlertDialogTitle>
              <AlertDialogDescription>
                {actions.delete.confirmMessage || 
                `Esta acción no se puede deshacer. Se eliminará permanentemente este registro${
                  row.id ? ` (ID: ${row.id})` : ''
                } y no podrá ser recuperado.`}
              </AlertDialogDescription>
            </AlertDialogHeader>
            <AlertDialogFooter>
              <AlertDialogCancel 
                onClick={() => {
                  setShowDeleteConfirm(false);
                }}
              >
                Cancelar
              </AlertDialogCancel>
              <AlertDialogAction
                className="bg-destructive text-white hover:bg-destructive/90"
                onClick={() => {
                  try {
                    // Ejecutar manejador de eliminación
                    actions.delete?.handler(row);
                    
                    // Cerrar explícitamente el diálogo
                    setShowDeleteConfirm(false);
                    
                    // Medidas de seguridad para gestionar el foco
                    if (document.activeElement instanceof HTMLElement) {
                      document.activeElement.blur();
                    }
                    
                    // Dar tiempo al DOM para actualizarse
                    setTimeout(() => {
                      // Forzar el foco al cuerpo del documento
                      document.body.focus();
                    }, 50);
                  } catch (error) {
                    console.error('Error al eliminar:', error);
                  }
                }}
              >
                Eliminar
              </AlertDialogAction>
            </AlertDialogFooter>
          </AlertDialogContent>
        </AlertDialog>
      )}
    </>
  );
}
