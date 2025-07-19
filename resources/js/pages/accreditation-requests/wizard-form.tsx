import React from "react";
import { FormField, FormItem, FormLabel, FormControl, FormMessage } from "@/components/ui/form";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { Textarea } from "@/components/ui/textarea";
import { Card, CardContent } from "@/components/ui/card";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { useInitials } from "@/hooks/use-initials";
import { Event, Employee, Zone } from "./schema";
import { useFormContext } from "react-hook-form";
import { Badge } from "@/components/ui/badge";
import { CheckIcon } from "lucide-react";

// Interfaces para las props del formulario de cada paso
interface Step1FormProps {
  events: Event[];
}

interface Step2FormProps {
  event?: Event;
  employees: Employee[];
}

interface Step3FormProps {
  event?: Event;
  employee?: Employee;
  zones: Zone[];
}

interface Step4FormProps {
  event?: Event;
  employee?: Employee;
  selectedZones: Zone[];
}

// Componente para el paso 1 (selección de evento)
export function Step1Form({ events }: Step1FormProps) {
  const { control } = useFormContext();

  return (
    <FormField
      control={control}
      name="event_id"
      render={({ field }) => (
        <FormItem>
          <FormLabel>Evento</FormLabel>
          <Select onValueChange={field.onChange} defaultValue={field.value}>
            <FormControl>
              <SelectTrigger>
                <SelectValue placeholder="Seleccione un evento" />
              </SelectTrigger>
            </FormControl>
            <SelectContent>
              {events.map((event) => (
                <SelectItem key={event.id} value={event.id.toString()}>
                  {event.name} - {event.date} - {event.venue}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <FormMessage />
        </FormItem>
      )}
    />
  );
}

// Componente para el paso 2 (selección de empleado)
export function Step2Form({ event, employees }: Step2FormProps) {
  const { control } = useFormContext();
  const getInitials = useInitials();

  return (
    <>
      {event && (
        <div className="mb-4">
          <p className="text-sm font-medium">Evento seleccionado:</p>
          <Badge variant="secondary" className="mt-1">
            {event.name} - {event.date}
          </Badge>
        </div>
      )}

      <FormField
        control={control}
        name="employee_id"
        render={({ field }) => (
          <FormItem>
            <FormLabel>Empleado</FormLabel>
            <Select onValueChange={field.onChange} defaultValue={field.value}>
              <FormControl>
                <SelectTrigger>
                  <SelectValue placeholder="Seleccione un empleado" />
                </SelectTrigger>
              </FormControl>
              <SelectContent>
                {employees.map((employee) => (
                  <SelectItem
                    key={employee.id}
                    value={employee.id.toString()}
                    className="flex items-center gap-2"
                  >
                    <div className="flex items-center gap-2">
                      <Avatar className="h-6 w-6">
                        <AvatarImage src={employee.photo_url} alt={employee.name} />
                        <AvatarFallback>{getInitials(employee.name)}</AvatarFallback>
                      </Avatar>
                      <span>
                        {employee.name} - {employee.document_id}
                      </span>
                    </div>
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <FormMessage />
          </FormItem>
        )}
      />
    </>
  );
}

// Componente para el paso 3 (selección de zonas)
export function Step3Form({ event, employee, zones }: Step3FormProps) {
  const { control } = useFormContext();
  const getInitials = useInitials();

  return (
    <>
      {event && employee && (
        <div className="mb-4 space-y-2">
          <div>
            <p className="text-sm font-medium">Evento:</p>
            <Badge variant="secondary" className="mt-1">
              {event.name} - {event.date}
            </Badge>
          </div>
          <div className="flex items-center gap-2">
            <p className="text-sm font-medium">Empleado:</p>
            <div className="flex items-center gap-2">
              <Avatar className="h-6 w-6">
                <AvatarImage src={employee.photo_url} alt={employee.name} />
                <AvatarFallback>{getInitials(employee.name)}</AvatarFallback>
              </Avatar>
              <span className="text-sm">{employee.name}</span>
            </div>
          </div>
        </div>
      )}

      <FormField
        control={control}
        name="zones"
        render={({ field }) => {
          // Asegurar que field.value sea siempre un array
          const selectedZones = field.value || [];
          
          return (
          <FormItem>
            <FormLabel>Zonas</FormLabel>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 mt-2">
              {zones.map((zone) => {
                // Asegurar que estemos comparando el mismo tipo de datos
                const zoneId = String(zone.id);
                const isSelected = selectedZones.includes(zoneId);
                
                return (
                <Card 
                  key={zone.id}
                  className={`cursor-pointer transition-all ${
                    isSelected ? "border-primary" : "border-border hover:border-primary/30"
                  }`}
                  onClick={() => {
                    if (isSelected) {
                      field.onChange(selectedZones.filter((id: string) => id !== zoneId));
                    } else {
                      field.onChange([...selectedZones, zoneId]);
                    }
                  }}
                >
                  <CardContent className="p-4 flex items-center justify-between">
                    <div>
                      <p className="font-medium">{zone.name}</p>
                      {zone.description && <p className="text-sm text-muted-foreground">{zone.description}</p>}
                    </div>
                    {isSelected && (
                      <CheckIcon className="h-5 w-5 text-primary" />
                    )}
                  </CardContent>
                </Card>
                );
              })}
            </div>
            {/* Mensaje de error explícito */}
            {selectedZones.length === 0 && (
              <p className="text-sm text-red-500 mt-1">Debe seleccionar al menos una zona</p>
            )}
            <FormMessage />
          </FormItem>
          );
        }}
      />
    </>
  );
}

// Componente para el paso 4 (confirmación)
export function Step4Form({ event, employee, selectedZones }: Step4FormProps) {
  const { control } = useFormContext();
  const getInitials = useInitials();

  return (
    <>
      {/* Resumen de la solicitud */}
      <div className="mb-6 space-y-6">
        <div className="border rounded-lg p-4 space-y-4">
          <div>
            <h4 className="text-sm font-medium text-muted-foreground mb-2">Evento</h4>
            <p className="font-medium">{event?.name}</p>
            <p className="text-sm">
              {event?.date} - {event?.venue}
            </p>
          </div>

          <div>
            <h4 className="text-sm font-medium text-muted-foreground mb-2">Empleado</h4>
            <div className="flex items-center gap-2">
              <Avatar>
                <AvatarImage src={employee?.photo_url} alt={employee?.name} />
                <AvatarFallback>{employee && getInitials(employee.name)}</AvatarFallback>
              </Avatar>
              <div>
                <p className="font-medium">{employee?.name}</p>
                <p className="text-sm">{employee?.document_id}</p>
              </div>
            </div>
          </div>

          <div>
            <h4 className="text-sm font-medium text-muted-foreground mb-2">Zonas seleccionadas</h4>
            <div className="flex flex-wrap gap-2">
              {(selectedZones || []).map((zone) => (
                <Badge key={zone.id} variant="outline">
                  {zone.name}
                </Badge>
              ))}
            </div>
          </div>
        </div>

        <FormField
          control={control}
          name="notes"
          render={({ field }) => (
            <FormItem>
              <FormLabel>Notas adicionales</FormLabel>
              <FormControl>
                <Textarea
                  placeholder="Ingrese cualquier nota adicional sobre esta solicitud"
                  {...field}
                />
              </FormControl>
              <FormMessage />
            </FormItem>
          )}
        />

        <FormField
          control={control}
          name="confirm"
          render={({ field }) => (
            <FormItem className="flex flex-row items-start space-x-3 space-y-0">
              <FormControl>
                <Checkbox
                  checked={field.value}
                  onCheckedChange={field.onChange}
                />
              </FormControl>
              <div className="space-y-1 leading-none">
                <FormLabel>Confirmo que la información proporcionada es correcta</FormLabel>
              </div>
              <FormMessage />
            </FormItem>
          )}
        />
      </div>
    </>
  );
}
