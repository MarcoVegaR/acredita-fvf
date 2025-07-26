import React, { useState } from "react";
import { useFormContext } from "react-hook-form";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Badge } from "@/components/ui/badge";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Checkbox } from "@/components/ui/checkbox";
import { FormField, FormItem, FormControl, FormMessage } from "@/components/ui/form";
import { Filter, Users, CheckSquare, Square, MapPin } from "lucide-react";
import { useInitials } from "@/hooks/useInitials";
import { Event, EmployeeWithZones, Zone, EmployeeFilters } from "./bulk-schema";

// Interfaces para los props de cada paso
interface BulkStep1FormProps {
  events: Event[];
}

interface BulkStep2FormProps {
  event: Event;
  employees: EmployeeWithZones[];
}

interface BulkStep3FormProps {
  event: Event;
  selectedEmployees: EmployeeWithZones[];
  zones: Zone[];
}

interface BulkStep4FormProps {
  event: Event;
  employeesWithZones: EmployeeWithZones[];
  zones: Zone[];
}

// Paso 1: Selección de evento
export function BulkStep1Form({ events }: BulkStep1FormProps) {
  const { control } = useFormContext();

  return (
    <div className="space-y-6">
      <div className="text-center">
        <h3 className="text-lg font-medium mb-2">Seleccionar Evento</h3>
        <p className="text-sm text-muted-foreground">
          Seleccione el evento para el cual desea crear solicitudes masivas de acreditación
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Eventos Disponibles</CardTitle>
        </CardHeader>
        <CardContent>
          <FormField
            control={control}
            name="event_id"
            render={({ field }) => (
              <FormItem>
                <FormControl>
                  <div className="space-y-2">
                    {events.map((event) => (
                      <div
                        key={event.id}
                        className={`p-4 border rounded-lg cursor-pointer transition-colors ${
                          field.value === event.id.toString()
                            ? 'border-primary bg-primary/5'
                            : 'border-border hover:bg-muted/50'
                        }`}
                        onClick={() => field.onChange(event.id.toString())}
                      >
                        <div className="flex items-center space-x-2">
                          <input
                            type="radio"
                            checked={field.value === event.id.toString()}
                            onChange={() => field.onChange(event.id.toString())}
                            className="text-primary"
                          />
                          <div className="flex-1">
                            <h4 className="font-medium">{event.name}</h4>
                            <p className="text-sm text-muted-foreground">
                              {event.start_date} - {event.end_date}
                            </p>
                            {event.location && (
                              <p className="text-sm text-muted-foreground">{event.location}</p>
                            )}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                </FormControl>
                <FormMessage />
              </FormItem>
            )}
          />
        </CardContent>
      </Card>
    </div>
  );
}

// Paso 2: Selección masiva de empleados
export function BulkStep2Form({ event, employees }: BulkStep2FormProps) {
  const { control, setValue } = useFormContext();
  const getInitials = useInitials();
  
  // Debug logs removed for production
  
  const [filters, setFilters] = useState<EmployeeFilters>({});
  const [selectedIds, setSelectedIds] = useState<string[]>([]);

  // Filtrar empleados solo por búsqueda (nombre o documento)
  const filteredEmployees = employees.filter(employee => {
    if (filters.search) {
      const searchLower = filters.search.toLowerCase();
      return (
        employee.name?.toLowerCase().includes(searchLower) ||
        employee.document_id?.toLowerCase().includes(searchLower)
      );
    }
    return true;
  });

  const handleSelectAll = (selectAll: boolean) => {
    const newSelection = selectAll 
      ? filteredEmployees.map(emp => emp.id.toString())
      : [];
    
    setSelectedIds(newSelection);
    setValue('employee_ids', newSelection);
  };

  const handleSelectEmployee = (employeeId: string, checked: boolean) => {
    const newSelection = checked 
      ? [...selectedIds, employeeId]
      : selectedIds.filter(id => id !== employeeId);
    
    setSelectedIds(newSelection);
    setValue('employee_ids', newSelection);
  };

  return (
    <div className="space-y-6 max-w-full">
      <div className="text-center">
        <h3 className="text-lg font-medium mb-2">Seleccionar Empleados</h3>
        <p className="text-sm text-muted-foreground">
          Seleccione los empleados para los cuales desea solicitar acreditación para <strong>{event?.name}</strong>
        </p>
      </div>

      {/* Filtros */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Filter className="h-4 w-4" />
            Filtros
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="max-w-md">
            <div>
              <label className="text-sm font-medium">Buscar empleado</label>
              <Input
                placeholder="Buscar por nombre o documento..."
                value={filters.search || ''}
                onChange={(e) => setFilters(prev => ({ ...prev, search: e.target.value }))}
                className="mt-1"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Lista de empleados */}
      <Card>
        <CardHeader>
          <div className="flex items-center justify-between">
            <CardTitle className="flex items-center gap-2">
              <Users className="h-4 w-4" />
              Empleados ({filteredEmployees.length})
            </CardTitle>
            <div className="flex items-center gap-4">
              <span className="text-sm text-muted-foreground">
                {selectedIds.length} seleccionados
              </span>
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => handleSelectAll(selectedIds.length !== filteredEmployees.length)}
              >
                {selectedIds.length === filteredEmployees.length ? (
                  <>
                    <Square className="h-4 w-4 mr-2" />
                    Deseleccionar todos
                  </>
                ) : (
                  <>
                    <CheckSquare className="h-4 w-4 mr-2" />
                    Seleccionar todos
                  </>
                )}
              </Button>
            </div>
          </div>
        </CardHeader>
        <CardContent className="p-4">
          <div className="space-y-2 max-h-96 overflow-y-auto pr-2">
            {filteredEmployees.map((employee) => {
              // Validar que employee.id sea un número válido
              const employeeId = employee.id && !isNaN(Number(employee.id)) ? employee.id.toString() : `temp_${Math.random()}`;
              
              return (
                <div
                  key={employeeId}
                  className="flex items-center space-x-3 p-3 rounded-lg border hover:bg-muted/50 min-w-0"
                >
                  <Checkbox
                    checked={selectedIds.includes(employeeId)}
                    onCheckedChange={(checked) => 
                      handleSelectEmployee(employeeId, checked as boolean)
                    }
                  />
                  <Avatar className="h-10 w-10">
                    <AvatarImage src={employee.photo_url} alt={employee.name} />
                    <AvatarFallback>{getInitials(employee.name)}</AvatarFallback>
                  </Avatar>
                  <div className="flex-1 min-w-0">
                    <div className="font-medium truncate">{employee.name}</div>
                    <div className="text-sm text-muted-foreground truncate">
                      {employee.document_id}
                      {employee.position && ` • ${employee.position}`}
                      {employee.department && ` • ${employee.department}`}
                    </div>
                    {employee.provider && (
                      <Badge variant="outline" className="mt-1 max-w-fit">
                        <span className="truncate">{employee.provider.name}</span>
                      </Badge>
                    )}
                  </div>
                </div>
              );
            })}
          </div>
        </CardContent>
      </Card>

      <FormField
        control={control}
        name="event_id"
        render={({ field }) => (
          <FormItem className="hidden">
            <FormControl>
              <input {...field} value={event?.id?.toString() || ''} />
            </FormControl>
            <FormMessage />
          </FormItem>
        )}
      />
      
      <FormField
        control={control}
        name="employee_ids"
        render={({ field }) => (
          <FormItem className="hidden">
            <FormControl>
              <input {...field} />
            </FormControl>
            <FormMessage />
          </FormItem>
        )}
      />
    </div>
  );
}

// Paso 3: Configuración de zonas por empleado
export function BulkStep3Form({ event, selectedEmployees, zones }: BulkStep3FormProps) {
  const { control, setValue } = useFormContext();
  const getInitials = useInitials();
  
  const [employeeZones, setEmployeeZones] = useState<Record<string, string[]>>({});



  const applyZonesToAll = (zoneIds: string[]) => {
    const newEmployeeZones = { ...employeeZones }; // Preservar selecciones anteriores
    selectedEmployees.forEach(employee => {
      const employeeId = employee.id.toString();
      const existingZones = newEmployeeZones[employeeId] || [];
      
      // Combinar zonas existentes con las nuevas (sin duplicados)
      const combinedZones = [...new Set([...existingZones, ...zoneIds])];
      newEmployeeZones[employeeId] = combinedZones;
    });
    setEmployeeZones(newEmployeeZones);
    setValue('employee_zones', newEmployeeZones);
  };

  const updateEmployeeZones = (employeeId: string, zoneIds: string[]) => {
    const newEmployeeZones = {
      ...employeeZones,
      [employeeId]: zoneIds
    };
    setEmployeeZones(newEmployeeZones);
    setValue('employee_zones', newEmployeeZones);
  };

  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-lg font-medium mb-2">Configurar Zonas de Acceso</h3>
        <p className="text-sm text-muted-foreground">
          Configure las zonas de acceso para cada empleado seleccionado para <strong>{event?.name}</strong>
        </p>
      </div>

      {/* Aplicar a todos */}
      <Card>
        <CardHeader>
          <CardTitle>Aplicar a Todos</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="flex flex-wrap gap-2">
            {zones.map(zone => (
              <Button
                key={zone.id}
                type="button"
                variant="outline"
                size="sm"
                onClick={() => applyZonesToAll([zone.id.toString()])}
              >
                <MapPin className="h-3 w-3 mr-1" />
                {zone.name}
              </Button>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Lista de empleados con zonas */}
      <div className="space-y-4">
        {selectedEmployees.map(employee => (
          <Card key={employee.id}>
            <CardHeader>
              <div className="flex items-center gap-3">
                <Avatar className="h-10 w-10">
                  <AvatarImage src={employee.photo_url} alt={employee.name} />
                  <AvatarFallback>{getInitials(employee.name)}</AvatarFallback>
                </Avatar>
                <div>
                  <h4 className="font-medium">{employee.name}</h4>
                  <p className="text-sm text-muted-foreground">{employee.document_id}</p>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="flex flex-wrap gap-2">
                {zones.map(zone => {
                  const isSelected = employeeZones[employee.id.toString()]?.includes(zone.id.toString()) || false;
                  return (
                    <Button
                      key={zone.id}
                      type="button"
                      variant={isSelected ? "default" : "outline"}
                      size="sm"
                      onClick={() => {
                        const employeeId = employee.id.toString();
                        const currentZones = employeeZones[employeeId] || [];
                        const zoneId = zone.id.toString();
                        const newZones = isSelected
                          ? currentZones.filter(id => id !== zoneId)
                          : [...currentZones, zoneId];
                        updateEmployeeZones(employeeId, newZones);
                      }}
                    >
                      <MapPin className="h-3 w-3 mr-1" />
                      {zone.name}
                    </Button>
                  );
                })}
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <FormField
        control={control}
        name="event_id"
        render={({ field }) => (
          <FormItem className="hidden">
            <FormControl>
              <input {...field} value={event?.id?.toString() || ''} />
            </FormControl>
            <FormMessage />
          </FormItem>
        )}
      />
      
      <FormField
        control={control}
        name="employee_zones"
        render={({ field }) => (
          <FormItem className="hidden">
            <FormControl>
              <input {...field} />
            </FormControl>
            <FormMessage />
          </FormItem>
        )}
      />
    </div>
  );
}

// Paso 4: Confirmación y creación
export function BulkStep4Form({ event, employeesWithZones, zones }: BulkStep4FormProps) {
  const { control } = useFormContext();
  const getInitials = useInitials();

  return (
    <div className="space-y-6">
      <div>
        <h3 className="text-lg font-medium mb-2">Confirmar Solicitudes</h3>
        <p className="text-sm text-muted-foreground">
          Revise la información antes de crear las solicitudes de acreditación para <strong>{event?.name}</strong>
        </p>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Resumen</CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 gap-4">
            <div>
              <p className="text-sm font-medium">Evento</p>
              <p className="text-sm text-muted-foreground">{event?.name}</p>
            </div>
            <div>
              <p className="text-sm font-medium">Total de Solicitudes</p>
              <p className="text-sm text-muted-foreground">{employeesWithZones.length}</p>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Lista de empleados con sus zonas */}
      <div className="space-y-4">
        {employeesWithZones.map(employee => (
          <Card key={employee.id}>
            <CardContent className="pt-6">
              <div className="flex items-start gap-3">
                <Avatar className="h-10 w-10">
                  <AvatarImage src={employee.photo_url} alt={employee.name} />
                  <AvatarFallback>{getInitials(employee.name)}</AvatarFallback>
                </Avatar>
                <div className="flex-1">
                  <div className="flex items-center justify-between">
                    <div>
                      <h4 className="font-medium">{employee.name}</h4>
                      <p className="text-sm text-muted-foreground">{employee.document_id}</p>
                    </div>
                  </div>
                  
                  <div className="mt-2">
                    <p className="text-sm font-medium mb-1">Zonas asignadas:</p>
                    <div className="flex flex-wrap gap-1">
                      {(employee.zones || []).map((zoneId: number) => {
                        const zone = zones.find(z => z.id === zoneId);
                        return zone ? (
                          <Badge key={zoneId} variant="outline">
                            <MapPin className="h-3 w-3 mr-1" />
                            {zone.name}
                          </Badge>
                        ) : null;
                      })}
                    </div>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      <FormField
        control={control}
        name="event_id"
        render={({ field }) => (
          <FormItem className="hidden">
            <FormControl>
              <input {...field} value={event?.id?.toString() || ''} />
            </FormControl>
            <FormMessage />
          </FormItem>
        )}
      />
      
      <FormField
        control={control}
        name="employee_zones"
        render={({ field }) => (
          <FormItem className="hidden">
            <FormControl>
              <input {...field} />
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
              <label className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70">
                Confirmo que la información es correcta
              </label>
              <p className="text-xs text-muted-foreground">
                Al marcar esta casilla, confirmo que he revisado toda la información y deseo crear las solicitudes de acreditación.
              </p>
            </div>
            <FormMessage />
          </FormItem>
        )}
      />
      
      <FormField
        control={control}
        name="notes"
        render={({ field }) => (
          <FormItem>
            <label className="text-sm font-medium">Notas adicionales (opcional)</label>
            <FormControl>
              <textarea
                {...field}
                placeholder="Ingrese cualquier nota adicional para estas solicitudes..."
                className="min-h-[100px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
              />
            </FormControl>
            <FormMessage />
          </FormItem>
        )}
      />
    </div>
  );
}
