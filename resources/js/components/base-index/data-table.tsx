import * as React from "react";
import {
  ColumnDef,
  ColumnFiltersState,
  SortingState,
  VisibilityState,
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
  Row,
} from "@tanstack/react-table";
import { ChevronUp, ChevronDown, ChevronsUpDown } from "lucide-react";

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";
import { DataTablePagination } from "@/components/base-index/data-table-pagination";
import { DataTableToolbar } from "@/components/base-index/data-table-toolbar";
import { FilterConfig } from "@/components/base-index/filter-toolbar";

interface DataTableProps<TData, TValue> {
  columns: ColumnDef<TData, TValue>[];
  data: TData[];
  serverSide?: {
    totalRecords: number;
    pageCount: number;
    currentPage?: number;
    perPage?: number;
    onPaginationChange: (pagination: { pageIndex: number; pageSize: number }) => void;
    onSortingChange?: (sorting: SortingState) => void;
    onGlobalFilterChange?: (filter: string) => void;
  };
  toolbarProps?: {
    showNewButton?: boolean;
    newButtonProps?: {
      label?: string;
      href?: string;
      onClick?: () => void;
    };
    filterConfig?: FilterConfig;
    filterEmptyMessage?: string;
    filters?: Record<string, unknown>;
    endpoint?: string;
  };
  exportOptions?: {
    enabled: boolean;
    fileName?: string;
    exportTypes?: ("excel" | "csv" | "print" | "copy")[];
  };
  /**
   * Columnas que pueden filtrarse con el filtro de columna individual
   */
  filterableColumns?: string[];
  
  /**
   * Columnas que se incluirán en la búsqueda global
   * Si no se especifica, la búsqueda global buscará en todas las columnas
   */
  searchableColumns?: string[];
  defaultSorting?: { id: string; desc: boolean }[];
  translationPrefix?: string;
  /**
   * Nombre del módulo al que pertenece esta tabla (ej: "users", "roles")
   * Se utiliza para obtener traducciones adecuadas para las columnas
   */
  moduleName?: string;
  
  /**
   * Texto placeholder para el campo de búsqueda global
   */
  searchPlaceholder?: string;
  onRowClick?: (row: TData) => void;
  rowActions?: React.ReactNode;
  renderRowActions?: (row: TData) => React.ReactNode;
  loading?: boolean;
  loadingMessage?: string;
  emptyMessage?: string;
}

export function DataTable<TData, TValue>({
  columns,
  data,
  serverSide,
  toolbarProps,
  exportOptions = { enabled: true, exportTypes: ["excel", "csv", "print", "copy"] },
  filterableColumns = [],
  searchableColumns,
  defaultSorting = [{ id: "id", desc: true }],
  translationPrefix = "",
  moduleName = "",
  searchPlaceholder = "Buscar...",
  onRowClick,
  rowActions,
  renderRowActions,
  /* Variables no utilizadas comentadas para evitar errores de linting
  loading = false,
  loadingMessage = "Cargando datos...",
  emptyMessage = "No hay datos disponibles",
  */
}: DataTableProps<TData, TValue>) {
  const [sorting, setSorting] = React.useState<SortingState>(defaultSorting);
  const [columnFilters, setColumnFilters] = React.useState<ColumnFiltersState>([]);
  const [columnVisibility, setColumnVisibility] = React.useState<VisibilityState>({});
  const [rowSelection, setRowSelection] = React.useState({});
  const [globalFilter, setGlobalFilter] = React.useState("");

  // Handle server-side pagination, sorting and filtering if configured
  const handleSortingChange = React.useCallback(
    (updaterOrValue: SortingState | ((old: SortingState) => SortingState)) => {
      // Si es una función, la ejecutamos para obtener el nuevo valor
      const newSorting = typeof updaterOrValue === 'function' 
        ? updaterOrValue(sorting) 
        : updaterOrValue;
      
      setSorting(newSorting);
      if (serverSide && serverSide.onSortingChange) {
        serverSide.onSortingChange(newSorting);
      }
    },
    [serverSide, sorting]
  );

  const handleGlobalFilterChange = React.useCallback(
    (value: string) => {
      setGlobalFilter(value);
      if (serverSide && serverSide.onGlobalFilterChange) {
        serverSide.onGlobalFilterChange(value);
      }
    },
    [serverSide]
  );

  // Calculamos el estado inicial de paginación para que sea consistente
  const initialPagination = React.useMemo(() => ({
    pageIndex: serverSide?.currentPage ? serverSide.currentPage - 1 : 0,
    pageSize: serverSide?.perPage || 10,
  }), [serverSide?.currentPage, serverSide?.perPage]);

  // Mantenemos un estado local de paginación
  const [{ pageIndex, pageSize }, setPagination] = React.useState(initialPagination);

  // Definimos función de filtrado personalizada que respeta los campos configurados
  const globalFilterFn = React.useCallback(
    (row: Row<TData>, columnId: string, filterValue: string) => {
      // Si es serverSide, no aplicamos filtro aquí (lo maneja el servidor)
      if (serverSide) return true;
      
      // Si hay campos de búsqueda configurados, solo buscamos en esos campos
      if (searchableColumns && searchableColumns.length > 0) {
        // Si no estamos en un campo permitido, no filtramos
        if (!searchableColumns.includes(columnId)) return true;
      }
      
      const value = row.getValue(columnId);
      // Si no hay valor para buscar o no hay valor en la celda, no coincide
      if (!filterValue || value === undefined || value === null) return true;
      
      // Convertimos a string y buscamos coincidencia parcial
      return String(value)
        .toLowerCase()
        .includes(String(filterValue).toLowerCase());
    },
    [serverSide, searchableColumns]
  );
  
  const table = useReactTable({
    data,
    columns,
    state: {
      sorting,
      columnFilters,
      globalFilter,
      columnVisibility,
      rowSelection,
      pagination: {
        pageIndex,
        pageSize,
      },
    },
    enableRowSelection: true,
    onRowSelectionChange: setRowSelection,
    onSortingChange: handleSortingChange,
    onColumnFiltersChange: setColumnFilters,
    onGlobalFilterChange: handleGlobalFilterChange,
    onColumnVisibilityChange: setColumnVisibility,
    onPaginationChange: setPagination,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: serverSide ? undefined : getFilteredRowModel(),
    getSortedRowModel: serverSide ? undefined : getSortedRowModel(),
    getPaginationRowModel: serverSide ? undefined : getPaginationRowModel(),
    globalFilterFn: globalFilterFn, // Aplicar nuestra función personalizada
    manualPagination: !!serverSide,
    manualSorting: !!serverSide,
    manualFiltering: !!serverSide,
    pageCount: serverSide?.pageCount || Math.ceil(data.length / pageSize),
  });

  return (
    <div className="space-y-4">
      {/* Contenedor con position relative para evitar superposición con la barra lateral */}
      <div className="relative z-0 px-1.5">
        <DataTableToolbar
          table={table}
          filterableColumns={filterableColumns}
          exportOptions={exportOptions}
          moduleName={moduleName}
          translationPrefix={translationPrefix}
          searchPlaceholder={searchPlaceholder}
          showNewButton={toolbarProps?.showNewButton}
          newButtonProps={toolbarProps?.newButtonProps}
          filterConfig={toolbarProps?.filterConfig}
          filterEmptyMessage={toolbarProps?.filterEmptyMessage}
          filters={toolbarProps?.filters}
          endpoint={toolbarProps?.endpoint}
        />
      <div className="mx-1 rounded-md border overflow-hidden">
        <Table className="w-full">
          <TableHeader>
            {table.getHeaderGroups().map((headerGroup) => (
              <TableRow key={headerGroup.id}>
                {headerGroup.headers.map((header) => (
                  <TableHead 
                    key={header.id} 
                    className={`px-4 py-2.5 ${header.column.getCanSort() ? "cursor-pointer select-none" : ""}`}
                  >
                    {header.isPlaceholder ? null : (
                      <div
                        className="flex items-center gap-1"
                        onClick={header.column.getCanSort() ? header.column.getToggleSortingHandler() : undefined}
                      >
                        {flexRender(
                          header.column.columnDef.header,
                          header.getContext()
                        )}
                        {header.column.getCanSort() && (
                          <div className="ml-2">
                            {header.column.getIsSorted() === "asc" ? (
                              <ChevronUp className="h-4 w-4" />
                            ) : header.column.getIsSorted() === "desc" ? (
                              <ChevronDown className="h-4 w-4" />
                            ) : (
                              <ChevronsUpDown className="h-4 w-4 opacity-50" />
                            )}
                          </div>
                        )}
                      </div>
                    )}
                  </TableHead>
                ))}
                {(rowActions || renderRowActions) && (
                  <TableHead className="w-[100px] px-4 py-2.5 text-right">Acciones</TableHead>
                )}
              </TableRow>
            ))}
          </TableHeader>
          <TableBody>
            {table.getRowModel().rows?.length ? (
              table.getRowModel().rows.map((row) => (
                <TableRow
                  key={row.id}
                  data-state={row.getIsSelected() && "selected"}
                  className={onRowClick ? "cursor-pointer" : ""}
                  onClick={onRowClick ? () => onRowClick(row.original) : undefined}
                >
                  {row.getVisibleCells().map((cell) => (
                    <TableCell key={cell.id} className="px-4 py-2 border-b">
                      {flexRender(
                        cell.column.columnDef.cell,
                        cell.getContext()
                      )}
                    </TableCell>
                  ))}
                  {(rowActions || renderRowActions) && (
                    <TableCell className="px-4 py-2 border-b text-right">
                      {renderRowActions ? renderRowActions(row.original) : rowActions}
                    </TableCell>
                  )}
                </TableRow>
              ))
            ) : (
              <TableRow>
                <TableCell
                  colSpan={columns.length + (rowActions || renderRowActions ? 1 : 0)}
                  className="h-24 text-center"
                >
                  No se encontraron resultados.
                </TableCell>
              </TableRow>
            )}
          </TableBody>
        </Table>
      </div>
        <DataTablePagination 
          table={table} 
          serverSide={serverSide} 
        />
      </div>
    </div>
  );
}
