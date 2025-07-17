import { ColumnDef } from "@tanstack/react-table";
import { Button } from "@/components/ui/button";
import { ArrowUpDown } from "lucide-react";
import { Entity } from "@/components/base-index/base-index-page";

// Employee data interface definition
export interface Employee extends Entity {
  id: number;
  uuid: string;
  provider_id: number;
  provider_name?: string;
  document_type: string;
  document_number: string;
  first_name: string;
  last_name: string;
  function: string;
  photo_path: string | null;
  active: boolean;
  created_at: string;
  updated_at: string;
  deleted_at: string | null;
  // Añadimos la propiedad [key: string]: unknown para cumplir con Entity
  [key: string]: unknown;
}

// Column definitions
export const columns: ColumnDef<Employee>[] = [
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
    accessorKey: "provider_name",
    header: () => <div className="font-semibold">Proveedor</div>,
    cell: ({ row }) => <div>{row.getValue("provider_name") || "—"}</div>,
    enableSorting: false,
  },
  {
    accessorKey: "document_type",
    header: () => <div className="font-semibold">Tipo Doc.</div>,
    cell: ({ row }) => {
      const documentType = row.getValue("document_type") as string;
      let label = "";
      
      switch (documentType) {
        case "V":
          label = "Venezolano";
          break;
        case "E":
          label = "Extranjero";
          break;
        case "P":
          label = "Pasaporte";
          break;
        default:
          label = documentType;
      }
      
      return <div>{label}</div>;
    },
    enableSorting: false,
  },
  {
    accessorKey: "document_number",
    header: () => <div className="font-semibold">Número Doc.</div>,
    cell: ({ row }) => <div>{row.getValue("document_number")}</div>,
    enableSorting: false,
  },
  {
    accessorKey: "first_name",
    header: () => <div className="font-semibold">Nombre</div>,
    cell: ({ row }) => <div>{row.getValue("first_name")}</div>,
    enableSorting: false,
  },
  {
    accessorKey: "last_name",
    header: () => <div className="font-semibold">Apellido</div>,
    cell: ({ row }) => <div>{row.getValue("last_name")}</div>,
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
