import * as React from "react";
import { Table } from "@tanstack/react-table";
import { Download, Copy, Printer } from "lucide-react";
import { getColumnLabel, formatExportValue } from "@/utils/translations/column-labels";

import { Button } from "@/components/ui/button";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

interface DataTableExportProps<TData> {
  table: Table<TData>;
  exportTypes?: ("excel" | "csv" | "print" | "copy")[];
  fileName?: string;
  /**
   * Módulo al que pertenece esta tabla (ej: "users", "roles")
   * Se utiliza para obtener las traducciones de columnas adecuadas
   */
  moduleName?: string;
}

export function DataTableExport<TData>({
  table,
  exportTypes = ["excel", "csv", "print", "copy"],
  fileName = "exported-data",
  moduleName = "",
}: DataTableExportProps<TData>) {
  // Helper function to get visible data for export
  const getExportData = () => {
    const headers = table.getHeaderGroups().flatMap(headerGroup =>
      headerGroup.headers
        .filter(header => header.column.getIsVisible() && !header.isPlaceholder)
        .map(header => {
              // Obtener el ID de la columna
          const columnId = header.column.id;
          
          // Usar la utilidad de traducciones para obtener la etiqueta
          // 1. Intentar obtener de las traducciones del módulo
          // 2. Si hay un header definido y es un string, usarlo como valor por defecto
          // 3. Caer en el ID de la columna como último recurso
          const headerText = typeof header.column.columnDef.header === 'string'
            ? header.column.columnDef.header
            : undefined;
          
          const headerValue = moduleName 
            ? getColumnLabel(moduleName, columnId, headerText)
            : (headerText || columnId);
            
          return headerValue;
        })
    );

    const rows = table.getRowModel().rows.map(row =>
      row.getVisibleCells().map(cell => {
        // Obtener el valor de la celda
        const value = cell.getValue();
        
        // Utilizar la función de formateo de valores para exportación
        // Esta función maneja null, undefined, fechas, booleanos, etc.
        return formatExportValue(value);
      })
    );

    return { headers, rows };
  };

  const exportToCSV = () => {
    const { headers, rows } = getExportData();
    
    // Create CSV content
    const csvContent = [
      headers.join(","),
      ...rows.map(row => row.join(","))
    ].join("\n");
    
    // Create download link
    const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", `${fileName}.csv`);
    link.style.visibility = "hidden";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const exportToExcel = () => {
    const { headers, rows } = getExportData();
    
    // For actual implementation, you would use a library like xlsx
    // For this demo, we'll just create a CSV which Excel can open
    const csvContent = [
      headers.join(","),
      ...rows.map(row => row.join(","))
    ].join("\n");
    
    const blob = new Blob([csvContent], { type: "application/vnd.ms-excel" });
    const url = URL.createObjectURL(blob);
    const link = document.createElement("a");
    link.setAttribute("href", url);
    link.setAttribute("download", `${fileName}.xlsx`);
    link.style.visibility = "hidden";
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const copyToClipboard = () => {
    const { headers, rows } = getExportData();
    
    // Format data for clipboard
    const clipboardData = [
      headers,
      ...rows
    ].map(row => row.join("\t")).join("\n");
    
    navigator.clipboard.writeText(clipboardData)
      .then(() => {
        // Show success notification
        console.log("Data copied to clipboard");
      })
      .catch(err => {
        console.error("Could not copy data: ", err);
      });
  };

  const printData = () => {
    const { headers, rows } = getExportData();
    
    // Create a printable HTML table
    const printWindow = window.open('', '_blank');
    
    if (printWindow) {
      printWindow.document.write(`
        <html>
          <head>
            <title>${fileName}</title>
            <style>
              table { border-collapse: collapse; width: 100%; }
              th, td { border: 1px solid #ddd; padding: 8px; }
              th { background-color: #f2f2f2; }
              tr:nth-child(even) { background-color: #f9f9f9; }
            </style>
          </head>
          <body>
            <h1>${fileName}</h1>
            <table>
              <thead>
                <tr>${headers.map(header => `<th>${header}</th>`).join('')}</tr>
              </thead>
              <tbody>
                ${rows.map(row => `
                  <tr>${row.map(cell => `<td>${cell}</td>`).join('')}</tr>
                `).join('')}
              </tbody>
            </table>
          </body>
        </html>
      `);
      
      printWindow.document.close();
      printWindow.focus();
      printWindow.print();
      printWindow.close();
    }
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="outline" size="sm">
          <Download className="mr-2 h-4 w-4" />
          Exportar
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuLabel>Exportar datos</DropdownMenuLabel>
        <DropdownMenuSeparator />
        
        {exportTypes.includes("excel") && (
          <DropdownMenuItem onClick={exportToExcel}>
            <Download className="mr-2 h-4 w-4" />
            <span>Exportar a Excel</span>
          </DropdownMenuItem>
        )}
        
        {exportTypes.includes("csv") && (
          <DropdownMenuItem onClick={exportToCSV}>
            <Download className="mr-2 h-4 w-4" />
            <span>Exportar a CSV</span>
          </DropdownMenuItem>
        )}
        
        {exportTypes.includes("print") && (
          <DropdownMenuItem onClick={printData}>
            <Printer className="mr-2 h-4 w-4" />
            <span>Imprimir</span>
          </DropdownMenuItem>
        )}
        
        {exportTypes.includes("copy") && (
          <DropdownMenuItem onClick={copyToClipboard}>
            <Copy className="mr-2 h-4 w-4" />
            <span>Copiar al portapapeles</span>
          </DropdownMenuItem>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
