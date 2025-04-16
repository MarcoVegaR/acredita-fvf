import { ColumnDef } from "@tanstack/react-table";
import { Button } from "@/components/ui/button";
import { ArrowUpDown } from "lucide-react";
import { Entity } from "@/components/base-index/base-index-page";

// User data interface definition
export interface User extends Entity {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
  // Añadimos la propiedad [key: string]: unknown para cumplir con Entity
  [key: string]: unknown;
}

// Column definitions
export const columns: ColumnDef<User>[] = [
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
    accessorKey: "name",
    header: () => <div className="font-semibold">Nombre</div>,
    cell: ({ row }) => <div>{row.getValue("name")}</div>,
    enableSorting: false,
  },
  {
    accessorKey: "email",
    header: () => <div className="font-semibold">Correo Electrónico</div>,
    cell: ({ row }) => <div>{row.getValue("email")}</div>,
    enableSorting: false,
  },
  {
    accessorKey: "email_verified_at",
    header: () => <div className="font-semibold">Verificado</div>,
    cell: ({ row }) => {
      const value = row.getValue("email_verified_at");
      return value ? (
        <div className="inline-flex items-center justify-center whitespace-nowrap rounded-md px-2.5 py-0.5 text-xs font-medium bg-green-50 text-green-700">
          Sí
        </div>
      ) : (
        <div className="inline-flex items-center justify-center whitespace-nowrap rounded-md px-2.5 py-0.5 text-xs font-medium bg-amber-50 text-amber-700">
          No
        </div>
      );
    },
    enableSorting: false,
  },
  {
    accessorKey: "created_at",
    header: () => <div className="font-semibold">Fecha de Registro</div>,
    cell: ({ row }) => {
      const value = row.getValue("created_at") as string;
      // Format date
      const formattedDate = new Date(value).toLocaleDateString('es-ES', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
      });
      return <div>{formattedDate}</div>;
    },
    enableSorting: false,
  },
];
