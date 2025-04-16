import * as React from "react";
import { Table } from "@tanstack/react-table";
import { ChevronLeftIcon, ChevronRightIcon, ChevronsLeftIcon, ChevronsRightIcon } from "lucide-react";

import { Button } from "@/components/ui/button";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select";

interface DataTablePaginationProps<TData> {
  table: Table<TData>;
  serverSide?: {
    totalRecords: number;
    pageCount: number;
    onPaginationChange: (pagination: { pageIndex: number; pageSize: number }) => void;
  };
}

export function DataTablePagination<TData>({
  table,
  serverSide,
}: DataTablePaginationProps<TData>) {
  const totalRecords = serverSide?.totalRecords || table.getCoreRowModel().rows.length;
  const pageCount = serverSide?.pageCount || table.getPageCount();
  
  // Handle server-side pagination change
  const handlePageIndexChange = React.useCallback(
    (newPageIndex: number) => {
      // Siempre actualizamos el estado de la tabla para reflejar el cambio
      table.setPageIndex(newPageIndex);
      
      // Si hay paginación del lado del servidor, llamamos al callback
      if (serverSide) {
        serverSide.onPaginationChange({
          pageIndex: newPageIndex,
          pageSize: table.getState().pagination.pageSize,
        });
      }
    },
    [table, serverSide]
  );

  const handlePageSizeChange = React.useCallback(
    (newPageSize: number) => {
      // Siempre actualizamos el estado de la tabla para reflejar el cambio
      table.setPageSize(newPageSize);
      
      // Si hay paginación del lado del servidor, llamamos al callback
      if (serverSide) {
        serverSide.onPaginationChange({
          pageIndex: 0, // Reset to first page when changing page size
          pageSize: newPageSize,
        });
      }
    },
    [table, serverSide]
  );
  
  return (
    <div className="flex flex-col-reverse items-center justify-between gap-4 px-2 py-4 sm:flex-row">
      <div className="flex-1 text-sm text-muted-foreground">
        {totalRecords > 0 ? (
          <p className="text-sm text-muted-foreground">
            Mostrando <span className="font-medium">{table.getState().pagination.pageIndex * table.getState().pagination.pageSize + 1}</span> a{" "}
            <span className="font-medium">
              {Math.min(
                (table.getState().pagination.pageIndex + 1) * table.getState().pagination.pageSize,
                totalRecords
              )}
            </span>{" "}
            de <span className="font-medium">{totalRecords}</span> registros
          </p>
        ) : (
          <p className="text-sm text-muted-foreground">No hay registros</p>
        )}
      </div>
      <div className="flex items-center space-x-6 lg:space-x-8">
        <div className="flex items-center space-x-2">
          <p className="text-sm font-medium">Registros por página</p>
          <Select
            value={`${table.getState().pagination.pageSize}`}
            onValueChange={(value) => {
              handlePageSizeChange(Number(value));
            }}
          >
            <SelectTrigger className="h-8 w-[70px]">
              <SelectValue placeholder={table.getState().pagination.pageSize} />
            </SelectTrigger>
            <SelectContent side="top">
              {[10, 20, 30, 40, 50].map((pageSize) => (
                <SelectItem key={pageSize} value={`${pageSize}`}>
                  {pageSize}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="flex w-[100px] items-center justify-center text-sm font-medium">
          Página {table.getState().pagination.pageIndex + 1} de{" "}
          {pageCount || 1}
        </div>
        <div className="flex items-center space-x-2">
          <Button
            variant="outline"
            className="hidden h-8 w-8 p-0 lg:flex"
            onClick={() => handlePageIndexChange(0)}
            disabled={table.getState().pagination.pageIndex === 0}
            aria-label="Primera página"
          >
            <span className="sr-only">Primera página</span>
            <ChevronsLeftIcon className="h-4 w-4" />
          </Button>
          <Button
            variant="outline"
            className="h-8 w-8 p-0"
            onClick={() => handlePageIndexChange(table.getState().pagination.pageIndex - 1)}
            disabled={table.getState().pagination.pageIndex === 0}
            aria-label="Página anterior"
          >
            <span className="sr-only">Página anterior</span>
            <ChevronLeftIcon className="h-4 w-4" />
          </Button>
          <Button
            variant="outline"
            className="h-8 w-8 p-0"
            onClick={() => handlePageIndexChange(table.getState().pagination.pageIndex + 1)}
            disabled={table.getState().pagination.pageIndex + 1 === pageCount}
            aria-label="Página siguiente"
          >
            <span className="sr-only">Página siguiente</span>
            <ChevronRightIcon className="h-4 w-4" />
          </Button>
          <Button
            variant="outline"
            className="hidden h-8 w-8 p-0 lg:flex"
            onClick={() => handlePageIndexChange(pageCount - 1)}
            disabled={table.getState().pagination.pageIndex + 1 === pageCount}
            aria-label="Última página"
          >
            <span className="sr-only">Última página</span>
            <ChevronsRightIcon className="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>
  );
}
