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
  AlertDialogTrigger,
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
  // Remove el estado y la función de limpieza ya que usaremos el patrón oficial
  
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
            <AlertDialog>
              <AlertDialogTrigger asChild>
                <DropdownMenuItem
                  onSelect={(e) => e.preventDefault()}
                  className="text-destructive focus:text-destructive"
                >
                  <Trash2 className="mr-2 h-4 w-4" />
                  <span>{actions.delete.label || "Eliminar"}</span>
                </DropdownMenuItem>
              </AlertDialogTrigger>
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
                  <AlertDialogCancel>Cancelar</AlertDialogCancel>
                  <AlertDialogAction
                    className="bg-destructive text-white hover:bg-destructive/90"
                    onClick={() => actions.delete?.handler(row)}
                  >
                    Eliminar
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
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
      {/* Eliminamos el diálogo externo ya que ahora está dentro del dropdown */}
    </>
  );
}
