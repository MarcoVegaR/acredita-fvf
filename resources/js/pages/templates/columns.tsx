import React from "react";
import { ColumnDef } from "@tanstack/react-table";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { ArrowUpDown, CheckCircle } from "lucide-react";
import { TableTemplate } from "./types";
import { formatDateTime } from "@/lib/utils";

// Configure columns with appropriate widths to avoid horizontal scrolling
export const columns: ColumnDef<TableTemplate>[] = [
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
    size: 60,
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
    cell: ({ row }) => (
      <div className="max-w-[150px] truncate" title={row.getValue("name")}>
        {row.getValue("name")}
      </div>
    ),
    enableSorting: true,
    size: 150,
  },
  {
    accessorKey: "event",
    header: "Evento",
    cell: ({ row }) => {
      const event = row.original.event;
      return (
        <div className="max-w-[120px] truncate" title={event?.name || "Sin evento"}>
          {event?.name || "Sin evento"}
        </div>
      );
    },
    size: 120,
  },
  {
    accessorKey: "is_default",
    header: "Predeterminada",
    cell: ({ row }) => {
      const isDefault = row.getValue("is_default");
      return isDefault ? (
        <Badge variant="outline" className="flex items-center gap-1 bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
          <CheckCircle className="h-3 w-3" />
          <span>Sí</span>
        </Badge>
      ) : (
        <span className="text-muted-foreground">No</span>
      );
    },
    size: 100,
  },
  {
    accessorKey: "version",
    header: "Versión",
    cell: ({ row }) => <div>v{row.getValue("version") || "1"}</div>,
    size: 60,
  },
  {
    accessorKey: "created_at",
    header: ({ column }) => (
      <Button
        variant="ghost"
        onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
        className="font-semibold"
      >
        Fecha creación
        <ArrowUpDown className="ml-2 h-4 w-4" />
      </Button>
    ),
    cell: ({ row }) => <div>{formatDateTime(row.getValue("created_at"))}</div>,
    enableSorting: true,
    size: 120,
  }
];
