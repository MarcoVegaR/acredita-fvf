"use client"

import * as React from "react"
import { Check, ChevronsUpDown } from "lucide-react"

import { cn } from "@/lib/utils"
import { Button } from "@/components/ui/button"
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/ui/popover"

export interface ComboboxOption {
  value: string
  label: string
}

interface ComboboxProps {
  options: ComboboxOption[]
  value: string
  onChange: (value: string) => void
  placeholder?: string
  searchPlaceholder?: string
  emptyMessage?: string
  disabled?: boolean
  className?: string
}

export function Combobox({
  options,
  value,
  onChange,
  placeholder = "Seleccionar...",
  searchPlaceholder = "Buscar...",
  emptyMessage = "No se encontraron resultados.",
  disabled = false,
  className,
}: ComboboxProps) {
  const [open, setOpen] = React.useState(false)
  const [searchValue, setSearchValue] = React.useState("")
  
  // Filtrar opciones según el texto de búsqueda
  const filteredOptions = React.useMemo(() => {
    if (!searchValue) return options;
    return options.filter((option) => 
      option.label.toLowerCase().includes(searchValue.toLowerCase())
    );
  }, [options, searchValue]);

  const handleSelect = (currentValue: string) => {
    console.log('[Combobox] handleSelect llamado con:', currentValue);
    onChange(currentValue === value ? "" : currentValue);
    console.log('[Combobox] Cerrando popover después de selección');
    setOpen(false);
  };

  return (
    <Popover open={open} onOpenChange={(openState) => {
      console.log('[Combobox] Popover onOpenChange llamado con:', openState);
      setOpen(openState);
    }} modal={true}>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          role="combobox"
          aria-expanded={open}
          className={cn("w-full justify-between", className)}
          disabled={disabled}
        >
          {options.find((option) => option.value === value)?.label || placeholder}
          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-[--radix-popover-trigger-width] p-0 z-50" align="start">
        <div className="flex flex-col">
          <div className="flex items-center border-b px-3">
            <input
              className="flex h-11 w-full rounded-md bg-transparent py-3 text-sm outline-none placeholder:text-muted-foreground disabled:cursor-not-allowed disabled:opacity-50"
              placeholder={searchPlaceholder}
              value={searchValue}
              onChange={(e) => setSearchValue(e.target.value)}
            />
          </div>
          <div className="max-h-[200px] overflow-y-auto p-1">
            {filteredOptions.length === 0 ? (
              <div className="py-6 text-center text-sm">{emptyMessage}</div>
            ) : (
              filteredOptions.map((option) => (
                <div
                  key={option.value}
                  role="option"
                  aria-selected={value === option.value}
                  onClick={(e) => {
                    console.log('[Combobox] onClick en opción:', option.label);
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Usar setTimeout para asegurar que este evento se complete antes de manejar la selección
                    setTimeout(() => {
                      handleSelect(option.value);
                    }, 0);
                  }}
                  
                  onPointerDown={(e) => {
                    console.log('[Combobox] onPointerDown en opción:', option.label);
                    e.preventDefault();
                    e.stopPropagation();
                    
                    // Ya no llamamos handleSelect aquí, lo dejamos para onClick
                  }}
                  className="relative flex cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground"
                >
                  <Check
                    className={cn(
                      "mr-2 h-4 w-4",
                      value === option.value ? "opacity-100" : "opacity-0"
                    )}
                  />
                  {option.label}
                </div>
              ))
            )}
          </div>
        </div>
      </PopoverContent>
    </Popover>
  )
}
