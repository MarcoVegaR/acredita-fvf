import { ColumnDef } from "@tanstack/react-table";
import { Button } from "@/components/ui/button";
import { ArrowUpDown } from "lucide-react";
import { Entity } from "@/components/base-index/base-index-page";

// Provider data interface definition
export interface Provider extends Entity {
  uuid: string;
  name: string;
  area: {
    id: number;
    name: string;
  };
  rif: string;
  user: {
    id: number;
    name: string;
    email: string;
  };
  type: "internal" | "external";
  active: boolean;
  phone?: string;
  created_at: string;
  updated_at: string;
  // Propiedad necesaria para cumplir con Entity
  [key: string]: unknown;
}

// Column definitions
export const columns: ColumnDef<Provider>[] = [
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
    cell: ({ row }) => <div className="font-medium">{row.getValue("name")}</div>,
    enableSorting: true,
  },
  {
    accessorKey: "area.name",
    header: () => <div className="font-semibold">Área</div>,
    cell: ({ row }) => {
      const provider = row.original;
      return <div>{provider.area?.name || "—"}</div>;
    },
    enableSorting: false,
  },
  {
    accessorKey: "rif",
    header: () => <div className="font-semibold">RIF</div>,
    cell: ({ row }) => <div>{row.getValue("rif")}</div>,
    enableSorting: false,
  },
  {
    accessorKey: "user.email",
    header: () => <div className="font-semibold">Email administrador</div>,
    cell: ({ row }) => {
      const provider = row.original;
      return <div className="max-w-[180px] truncate" title={provider.user?.email || ""}>{provider.user?.email || "—"}</div>;
    },
    enableSorting: false,
  },
  {
    accessorKey: "type",
    header: () => <div className="font-semibold">Tipo</div>,
    cell: ({ row }) => {
      const type = row.getValue("type") as "internal" | "external";
      return (
        <div className={`inline-flex items-center justify-center whitespace-nowrap rounded-md px-2.5 py-0.5 text-xs font-medium ${
          type === "internal" 
            ? "bg-blue-50 text-blue-700" 
            : "bg-purple-50 text-purple-700"
        }`}>
          {type === "internal" ? "Interno" : "Externo"}
        </div>
      );
    },
    enableSorting: false,
  },
  {
    accessorKey: "active",
    header: () => <div className="font-semibold">Estado</div>,
    cell: ({ row }) => {
      const value = row.getValue("active");
      return value ? (
        <div className="inline-flex items-center justify-center whitespace-nowrap rounded-md px-2.5 py-0.5 text-xs font-medium bg-green-50 text-green-700">
          Activo
        </div>
      ) : (
        <div className="inline-flex items-center justify-center whitespace-nowrap rounded-md px-2.5 py-0.5 text-xs font-medium bg-red-50 text-red-700">
          Inactivo
        </div>
      );
    },
    enableSorting: false,
  },

];
