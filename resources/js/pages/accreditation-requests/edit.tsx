import React from 'react';
import { useForm, Head, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

import { router } from '@inertiajs/react';
import { ArrowLeft, Save, Loader2 } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { AccreditationRequest } from './columns';
import { SharedData } from '@/types';

interface Event {
  id: number;
  name: string;
}

interface Employee {
  id: number;
  first_name: string;
  last_name: string;
}

interface Zone {
  id: number;
  name: string;
}

interface EditProps {
  request: AccreditationRequest;
  events?: Event[];
  employees?: Employee[];
  zones?: Zone[];
}

export default function Edit({ request, events = [], employees = [], zones = [] }: EditProps) {
  // Obtener información del usuario autenticado
  const { auth } = usePage<SharedData>().props;
  
  const { data, setData, put, processing, errors } = useForm({
    employee_id: request.employee_id,
    event_id: request.event_id,
    comments: request.comments || '',
    zones: request.zones?.map((z: Zone) => z.id) || [],
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    put(`/accreditation-requests/${request.uuid}`, {
      onSuccess: () => {
        // Redirigir de vuelta al índice con mensaje de éxito
        router.get('/accreditation-requests');
      },
    });
  };

  // Verificar si el usuario tiene permisos privilegiados
  const isPrivilegedUser = auth.user?.roles?.includes('admin') || auth.user?.roles?.includes('security_manager');
  
  // Solo mostrar mensaje restrictivo si no es borrador Y no es usuario privilegiado
  if (request.status !== 'draft' && !isPrivilegedUser) {
    const statusMessages = {
      'submitted': 'La solicitud ha sido enviada y está pendiente de revisión por el área correspondiente.',
      'under_review': 'La solicitud está siendo revisada por el administrador del sistema.',
      'approved': 'La solicitud ha sido aprobada y no requiere modificaciones.',
      'rejected': 'La solicitud ha sido rechazada. Puede crear una nueva solicitud si es necesario.',
      'returned': 'La solicitud ha sido devuelta para corrección. Debería estar en estado borrador para poder editarla.'
    };
    
    const currentStatusMessage = statusMessages[request.status as keyof typeof statusMessages] || 
      `La solicitud tiene estado "${request.status}" y no puede ser modificada.`;
    
    return (
      <AppLayout>
        <Head title={`Editar Solicitud - ${request.employee.first_name} ${request.employee.last_name}`} />
        <div className="max-w-4xl mx-auto py-6">
          <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
            <div className="flex">
              <div className="flex-shrink-0">
                <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                  <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                </svg>
              </div>
              <div className="ml-3">
                <h3 className="text-sm font-medium text-yellow-800">
                  No se puede editar esta solicitud
                </h3>
                <div className="mt-2 text-sm text-yellow-700">
                  <p><strong>Estado actual:</strong> {request.status}</p>
                  <p className="mt-1">{currentStatusMessage}</p>
                  <p className="mt-2 text-xs">
                    <strong>Nota:</strong> Solo las solicitudes en estado "borrador" pueden ser editadas.
                  </p>
                </div>
              </div>
            </div>
          </div>
          
          <div className="mt-6 flex space-x-3">
            <button
              type="button"
              onClick={() => router.get('/accreditation-requests')}
              className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Volver al listado
            </button>
            <button
              type="button"
              onClick={() => router.get(`/accreditation-requests/${request.uuid}`)}
              className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              Ver detalles
            </button>
          </div>
        </div>
      </AppLayout>
    );
  }

  return (
    <AppLayout>
      <Head title={`Editar Solicitud de Acreditación - ${request.employee.first_name} ${request.employee.last_name}`} />
      
      <div className="space-y-6">
        <div className="flex items-center gap-4">
          <Button 
            variant="outline" 
            size="sm" 
            onClick={() => router.get('/accreditation-requests')}
            disabled={processing}
          >
            <ArrowLeft className="h-4 w-4 mr-2" />
            Volver
          </Button>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">
              Editar Solicitud de Acreditación
            </h1>
            <p className="mt-1 text-sm text-gray-600">
              Modifique los datos de la solicitud de acreditación para {request.employee.first_name} {request.employee.last_name}
            </p>
          </div>
        </div>

        {request.status !== 'draft' && (
          <Alert className={isPrivilegedUser ? "border-blue-200 bg-blue-50" : "border-yellow-200 bg-yellow-50"}>
            <AlertDescription className={isPrivilegedUser ? "text-blue-800" : "text-yellow-800"}>
              {isPrivilegedUser ? (
                <>
                  <strong>Modo de edición privilegiada:</strong> Usted tiene permisos para editar solicitudes en cualquier estado. 
                  Esta solicitud está en estado: <strong>{request.status}</strong>
                </>
              ) : (
                <>
                  Solo se pueden editar solicitudes en estado borrador. Esta solicitud está en estado: <strong>{request.status}</strong>
                </>
              )}
            </AlertDescription>
          </Alert>
        )}

        <form onSubmit={handleSubmit}>
          <Card>
            <CardHeader>
              <CardTitle>Información de la Solicitud</CardTitle>
            </CardHeader>
            <CardContent className="space-y-6">
              {/* Campos editables */}
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {/* Empleado */}
                <div className="space-y-2">
                  <Label htmlFor="employee_id">Empleado</Label>
                  {employees.length > 0 ? (
                    <Select 
                      value={data.employee_id?.toString()} 
                      onValueChange={(value) => setData('employee_id', parseInt(value))}
                      disabled={processing || (request.status !== 'draft' && !isPrivilegedUser)}
                    >
                      <SelectTrigger className={errors.employee_id ? 'border-red-500' : ''}>
                        <SelectValue placeholder="Seleccione un empleado" />
                      </SelectTrigger>
                      <SelectContent>
                        {employees.map((employee) => (
                          <SelectItem key={employee.id} value={employee.id.toString()}>
                            {employee.first_name} {employee.last_name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  ) : (
                    <p className="mt-1 text-sm text-gray-900">
                      {request.employee.first_name} {request.employee.last_name}
                    </p>
                  )}
                  {errors.employee_id && (
                    <p className="text-sm text-red-600">{errors.employee_id}</p>
                  )}
                </div>

                {/* Evento */}
                <div className="space-y-2">
                  <Label htmlFor="event_id">Evento</Label>
                  {events.length > 0 ? (
                    <Select 
                      value={data.event_id?.toString()} 
                      onValueChange={(value) => setData('event_id', parseInt(value))}
                      disabled={processing || (request.status !== 'draft' && !isPrivilegedUser)}
                    >
                      <SelectTrigger className={errors.event_id ? 'border-red-500' : ''}>
                        <SelectValue placeholder="Seleccione un evento" />
                      </SelectTrigger>
                      <SelectContent>
                        {events.map((event) => (
                          <SelectItem key={event.id} value={event.id.toString()}>
                            {event.name}
                          </SelectItem>
                        ))}
                      </SelectContent>
                    </Select>
                  ) : (
                    <p className="mt-1 text-sm text-gray-900">
                      {request.event.name}
                    </p>
                  )}
                  {errors.event_id && (
                    <p className="text-sm text-red-600">{errors.event_id}</p>
                  )}
                </div>

                {/* Estado (solo lectura) */}
                <div>
                  <Label className="text-sm font-medium text-gray-700">Estado</Label>
                  <p className="mt-1 text-sm text-gray-900 capitalize">
                    {request.status === 'draft' ? 'Borrador' : request.status}
                  </p>
                </div>
              </div>

              {/* Zonas */}
              {zones.length > 0 && (
                <div className="space-y-2">
                  <Label>Zonas de Acreditación</Label>
                  <div className="text-sm text-gray-600 mb-2">
                    Seleccione las zonas donde el empleado tendrá acceso
                  </div>
                  <div className="grid grid-cols-2 md:grid-cols-3 gap-3 max-h-48 overflow-y-auto border rounded-md p-3">
                    {zones.map((zone) => {
                      const isChecked = data.zones.includes(zone.id);
                      return (
                        <div key={zone.id} className="flex items-center space-x-2">
                          <input
                            type="checkbox"
                            id={`zone-${zone.id}`}
                            checked={isChecked}
                            onChange={(e) => {
                              const checked = e.target.checked;
                              if (checked) {
                                setData('zones', [...data.zones, zone.id]);
                              } else {
                                setData('zones', data.zones.filter((id: number) => id !== zone.id));
                              }
                            }}
                            disabled={processing || (request.status !== 'draft' && !isPrivilegedUser)}
                            className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                          />
                          <Label 
                            htmlFor={`zone-${zone.id}`} 
                            className="text-sm font-normal cursor-pointer"
                          >
                            {zone.name}
                          </Label>
                        </div>
                      );
                    })}
                  </div>
                  {errors.zones && (
                    <p className="text-sm text-red-600">{errors.zones}</p>
                  )}
                </div>
              )}
              
              {/* Comentarios */}
              <div className="space-y-2">
                <Label htmlFor="comments">Comentarios</Label>
                <Textarea
                  id="comments"
                  placeholder="Agregue comentarios adicionales sobre la solicitud..."
                  value={data.comments}
                  onChange={(e) => setData('comments', e.target.value)}
                  disabled={processing || (request.status !== 'draft' && !isPrivilegedUser)}
                  rows={4}
                  className={errors.comments ? 'border-red-500' : ''}
                />
                {errors.comments && (
                  <p className="text-sm text-red-600">{errors.comments}</p>
                )}
              </div>
              
              {/* Botones de acción */}
              <div className="flex justify-end gap-3">
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => router.get('/accreditation-requests')}
                  disabled={processing}
                >
                  Cancelar
                </Button>
                <Button
                  type="submit"
                  disabled={processing || (request.status !== 'draft' && !isPrivilegedUser)}
                >
                  {processing ? (
                    <>
                      <Loader2 className="h-4 w-4 mr-2 animate-spin" />
                      Guardando...
                    </>
                  ) : (
                    <>
                      <Save className="h-4 w-4 mr-2" />
                      Guardar Cambios
                    </>
                  )}
                </Button>
              </div>
            </CardContent>
          </Card>
        </form>
      </div>
    </AppLayout>
  );
}
