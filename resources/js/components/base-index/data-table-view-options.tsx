import * as React from "react";
import { Table } from "@tanstack/react-table";
import { ViewIcon } from "lucide-react";
import { getColumnLabel } from "@/utils/translations/column-labels";

import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

interface DataTableViewOptionsProps<TData> {
  table: Table<TData>;
  translationPrefix?: string;
  /**
   * Nombre del módulo al que pertenece esta tabla (ej: "users", "roles")
   * Se utiliza para obtener las traducciones de columnas adecuadas
   */
  moduleName?: string;
}

export function DataTableViewOptions<TData>({
  table,
  translationPrefix = "",
  moduleName = "",
}: DataTableViewOptionsProps<TData>) {
  // Helper function to translate column headers using our translation system
  const getTranslatedColumnHeader = (columnId: string, defaultHeader: string): string => {
    // Si tenemos un nombre de módulo definido, usar la utilidad de traducciones centralizada
    if (moduleName) {
      return getColumnLabel(moduleName, columnId, defaultHeader);
    }
    
    // Si no hay módulo pero hay un prefijo de traducción, mantener compatibilidad
    if (translationPrefix) {
      // Aquí se podría implementar otro método de traducción en el futuro
      return defaultHeader;
    }
    
    return defaultHeader;
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="outline" size="sm" className="ml-auto">
          <ViewIcon className="mr-2 h-4 w-4" />
          Ver columnas
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuLabel>Columnas visibles</DropdownMenuLabel>
        <DropdownMenuSeparator />
        {table
          .getAllColumns()
          .filter(
            (column) =>
              typeof column.accessorFn !== "undefined" && column.getCanHide()
          )
          .map((column) => {
            const headerText = typeof column.columnDef.header === 'string' 
              ? column.columnDef.header 
              : column.id;
              
            return (
              <DropdownMenuCheckboxItem
                key={column.id}
                className="capitalize"
                checked={column.getIsVisible()}
                onCheckedChange={(value) => column.toggleVisibility(!!value)}
              >
                {getTranslatedColumnHeader(column.id, headerText)}
              </DropdownMenuCheckboxItem>
            );
          })}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
