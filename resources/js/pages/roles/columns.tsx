// resources/js/pages/roles/columns.tsx
import { ColumnDef } from "@tanstack/react-table";
import { Button } from "@/components/ui/button";
import { ArrowUpDown } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { Entity } from "@/components/base-index/base-index-page";

// Define the Role type, extending Entity
export interface Role extends Entity {
  id: number;
  name: string;
  guard_name: string;
  permissions_count?: number; // Optional: Count of permissions
  created_at: string;
  updated_at: string;
  // Needed for Entity compatibility
  [key: string]: unknown;
}

export const columns: ColumnDef<Role>[] = [
  {
    accessorKey: "id",
    header: ({ column }) => (
      <Button
        variant="ghost"
        onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
        className="font-semibold px-0 hover:bg-transparent"
      >
        ID
        <ArrowUpDown className="ml-2 h-4 w-4" />
      </Button>
    ),
    cell: ({ row }) => <div className="font-medium">{row.getValue("id")}</div>,
    enableSorting: true
  },
  {
    accessorKey: "name",
    header: () => <div className="font-semibold">Nombre</div>,
    cell: ({ row }) => {
        const isProtected = row.original.name === 'admin';
        return (
            <div className="flex items-center">
                {row.getValue("name")}
                {isProtected && <Badge variant="destructive" className="ml-2">Protegido</Badge>}
            </div>
        );
    },
    enableSorting: false
  },
  {
    accessorKey: "permissions",
    header: () => <div className="font-semibold">Permisos</div>,
     cell: ({ row }) => {
       // Obtener permisos del rol
       const permissions = row.getValue("permissions");
       
       // Si ya es un string (transformado por el backend), usarlo directamente
       if (typeof permissions === 'string') {
         return <div className="max-w-md truncate" title={permissions}>{permissions}</div>;
       }
       
       // Si es un array, extraer los nombres para mostrarlos
       if (Array.isArray(permissions) && permissions.length > 0) {
         const permissionNames = permissions
           .map(p => typeof p === 'object' && p !== null ? (p.nameshow || p.name) : '')
           .filter(Boolean)
           .join(', ');
         
         return <div className="max-w-md truncate" title={permissionNames}>{permissionNames || "Sin permisos"}</div>;
       }
       
       // Si hay un contador de permisos pero no hay texto, mostrar el contador
       if (row.original.permissions_count) {
         return <div>{`${row.original.permissions_count} permisos`}</div>;
       }
       
       // Si nada de lo anterior funciona, mostrar "Sin permisos"
       return <div>Sin permisos</div>;
     },
     enableSorting: false // Might not be sortable directly depending on backend query
  },
  {
    accessorKey: "created_at",
    header: () => <div className="font-semibold">Fecha de Creación</div>,
    cell: ({ row }) => {
        const date = new Date(row.getValue("created_at"));
        return date.toLocaleDateString('es-ES', { day: '2-digit', month: '2-digit', year: 'numeric' });
    },
    enableSorting: false
  },
  // The actions column will be added dynamically by BaseIndexPage based on options
];
