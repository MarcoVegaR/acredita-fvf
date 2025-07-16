import { ColumnDef } from "@tanstack/react-table";
import { Button } from "@/components/ui/button";
import { ArrowUpDown, Check, X } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Entity } from "@/types";

// Definición de la interfaz User para el gerente
export interface User {
  id: number;
  name: string;
  email: string;
}

// Definición de la interfaz Area
export interface Area extends Entity {
  id: number;
  uuid: string;
  code: string;
  name: string;
  description: string | null;
  manager_user_id: number | null;
  manager?: User;
  active: boolean;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  [key: string]: unknown;
}

// Definición de las columnas para la tabla de áreas
export const columns: ColumnDef<Area>[] = [
  {
    accessorKey: "id",
    header: ({ column }) => (
      <Button
        variant="ghost"
        onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
        className="font-semibold"
      >
        ID
        <ArrowUpDown className="ml-2 h-4 w-4" />
      </Button>
    ),
    cell: ({ row }) => <div className="font-medium">{row.getValue("id")}</div>,
    enableSorting: true,
  },
  {
    accessorKey: "code",
    header: ({ column }) => (
      <Button
        variant="ghost"
        onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
        className="font-semibold"
      >
        Código
        <ArrowUpDown className="ml-2 h-4 w-4" />
      </Button>
    ),
    cell: ({ row }) => <div>{row.getValue("code")}</div>,
    enableSorting: true,
  },
  {
    accessorKey: "name",
    header: ({ column }) => (
      <Button
        variant="ghost"
        onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
        className="font-semibold"
      >
        Nombre
        <ArrowUpDown className="ml-2 h-4 w-4" />
      </Button>
    ),
    cell: ({ row }) => <div>{row.getValue("name")}</div>,
    enableSorting: true,
  },

  {
    accessorKey: "active",
    header: "Estado",
    cell: ({ row }) => {
      const active = row.getValue("active") as boolean;
      return active ? (
        <Badge variant="outline" className="flex items-center gap-1 bg-green-100 text-green-800 border-green-200">
          <Check className="h-3 w-3" />
          <span>Activo</span>
        </Badge>
      ) : (
        <Badge variant="destructive" className="flex items-center gap-1">
          <X className="h-3 w-3" />
          <span>Inactivo</span>
        </Badge>
      );
    },
    enableSorting: true,
  },
  {
    id: "manager",
    header: () => <div className="text-left">Gerente</div>,
    cell: ({ row }) => {
      const area = row.original;
      return (
        <div>
          {area.manager ? (
            <div className="flex items-center gap-2">
              <span className="inline-flex h-6 w-6 items-center justify-center rounded-full bg-primary/10 text-xs font-medium text-primary">
                {area.manager.name.charAt(0).toUpperCase()}
              </span>
              <div className="flex flex-col">
                <span className="text-sm font-medium truncate max-w-[140px]" title={area.manager.name}>
                  {area.manager.name}
                </span>
                <span className="text-xs text-muted-foreground truncate max-w-[140px]" title={area.manager.email}>
                  {area.manager.email}
                </span>
              </div>
            </div>
          ) : (
            <Badge variant="outline" className="text-xs text-muted-foreground">
              Sin gerente
            </Badge>
          )}
        </div>
      );
    },
  },
  {
    accessorKey: "created_at",
    header: ({ column }) => (
      <Button
        variant="ghost"
        onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
        className="font-semibold"
      >
        Creación
        <ArrowUpDown className="ml-2 h-4 w-4" />
      </Button>
    ),
    cell: ({ row }) => {
      const date = new Date(row.getValue("created_at"));
      return <div>{date.toLocaleDateString()}</div>;
    },
    enableSorting: true,
  },
];
