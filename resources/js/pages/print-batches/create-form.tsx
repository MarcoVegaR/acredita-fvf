import React, { useState, useEffect } from "react";
import { useFormContext } from "react-hook-form";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Label } from "@/components/ui/label";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Checkbox } from "@/components/ui/checkbox";
import { Badge } from "@/components/ui/badge";

import { Separator } from "@/components/ui/separator";
import { Alert, AlertDescription } from "@/components/ui/alert";
import { 
  CalendarIcon, 
  BuildingIcon, 
  PrinterIcon,
  FileTextIcon,
  UsersIcon,
  AlertCircleIcon,
  CheckCircleIcon,
  LoaderIcon,
  EyeIcon
} from "lucide-react";
import { FilterData, BatchPreview, CreateBatchFormData } from "./types";


interface CreateBatchFormProps {
  filtersData: FilterData;
  preview: BatchPreview | null;
  isLoadingPreview: boolean;
  previewError: string | null;
  onPreview: (formData: CreateBatchFormData) => void;
}

export function CreateBatchForm({ 
  filtersData, 
  preview, 
  isLoadingPreview, 
  previewError, 
  onPreview 
}: CreateBatchFormProps) {
  const { watch, setValue, getValues, formState: { errors } } = useFormContext<CreateBatchFormData>();
  const [filteredProviders, setFilteredProviders] = useState(filtersData.providers);

  // Watch form values
  const eventId = watch("event_id");
  const areaId = watch("area_id");
  const providerId = watch("provider_id");
  const onlyUnprinted = watch("only_unprinted");

  // Filter providers by selected areas
  useEffect(() => {
    if (areaId && areaId.length > 0) {
      const filtered = filtersData.providers.filter(provider => 
        areaId.includes(provider.area_id)
      );
      setFilteredProviders(filtered);
      
      // Clear provider selection if current providers are not in filtered list
      if (providerId && providerId.length > 0) {
        const validProviders = providerId.filter(id => 
          filtered.some(provider => provider.id === id)
        );
        if (validProviders.length !== providerId.length) {
          setValue("provider_id", validProviders);
        }
      }
    } else {
      setFilteredProviders(filtersData.providers);
    }
  }, [areaId, filtersData.providers, providerId, setValue]);

  // Función para manejar cambios y disparar preview
  const handleFormChange = (customEventId?: number) => {
    const currentEventId = customEventId || eventId;
    if (currentEventId && currentEventId > 0) {
      console.log('[CREATE-FORM.TSX] Form changed, calling preview with eventId:', currentEventId);
      
      // Usar valores actuales del form
      const currentValues = getValues();
      const formData = {
        event_id: currentEventId,
        area_id: currentValues.area_id,
        provider_id: currentValues.provider_id,
        only_unprinted: currentValues.only_unprinted
      };
      console.log('[CREATE-FORM.TSX] Calling onPreview with formData:', formData);
      onPreview(formData);
    }
  };

  // Todos los cambios ahora se manejan directamente en los onChange de cada campo
  // No necesitamos useEffect para el preview

  // Convertir opciones para MultiSelect
  const areaOptions = filtersData.areas.map(area => ({
    value: area.id.toString(),
    label: area.name
  }));

  const providerOptions = filteredProviders.map(provider => ({
    value: provider.id.toString(),
    label: provider.name
  }));

  // Debug logs
  console.log('[CREATE-FORM.TSX] DEBUG - filtersData.areas:', filtersData.areas);
  console.log('[CREATE-FORM.TSX] DEBUG - areaOptions:', areaOptions);
  console.log('[CREATE-FORM.TSX] DEBUG - filteredProviders:', filteredProviders);
  console.log('[CREATE-FORM.TSX] DEBUG - providerOptions:', providerOptions);
  console.log('[CREATE-FORM.TSX] DEBUG - current values:', { eventId, areaId, providerId, onlyUnprinted });

  return (
    <div className="space-y-6">
      {/* Tab: Filtros de Credenciales */}
      <div data-tab="filters" className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center space-x-2">
              <CalendarIcon className="h-5 w-5" />
              <span>Evento (Obligatorio)</span>
            </CardTitle>
            <CardDescription>
              Seleccione el evento para el cual generar el lote de impresión
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-2">
              <Label htmlFor="event_id">Evento</Label>
              <Select
                value={eventId?.toString() || ""}
                onValueChange={(value) => {
                  const newEventId = parseInt(value);
                  console.log('[CREATE-FORM.TSX] Event selected:', newEventId);
                  setValue("event_id", newEventId);
                  
                  // Usar setTimeout para asegurar que setValue haya terminado
                  setTimeout(() => {
                    handleFormChange(newEventId);
                  }, 0);
                }}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Seleccionar evento" />
                </SelectTrigger>
                <SelectContent>
                  {filtersData.events.map((event) => (
                    <SelectItem key={event.id} value={event.id.toString()}>
                      {event.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {errors.event_id && (
                <p className="text-sm text-red-600">{errors.event_id.message}</p>
              )}
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center space-x-2">
              <BuildingIcon className="h-5 w-5" />
              <span>Filtros Organizacionales (Opcional)</span>
            </CardTitle>
            <CardDescription>
              Filtre las credenciales por área y/o proveedor específico
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="area_id">Áreas (TEST - Select simple)</Label>
              <Select
                value={(areaId && areaId[0]) ? areaId[0].toString() : "all"}
                onValueChange={(value) => {
                  const newAreaId = (value && value !== 'all') ? [parseInt(value)] : [];
                  console.log('[CREATE-FORM.TSX] Area changed (single):', newAreaId);
                  setValue("area_id", newAreaId);
                  handleFormChange();
                }}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Seleccionar área (prueba)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todas las áreas</SelectItem>
                  {filtersData.areas.map((area) => (
                    <SelectItem key={area.id} value={area.id.toString()}>
                      {area.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <p className="text-xs text-muted-foreground">
                Modo prueba: solo una área. Si no selecciona, se incluirán todas.
              </p>
            </div>

            <div className="space-y-2">
              <Label htmlFor="provider_id">Proveedores (TEST - Select simple)</Label>
              <Select
                value={(providerId && providerId[0]) ? providerId[0].toString() : "all"}
                onValueChange={(value) => {
                  const newProviderId = (value && value !== 'all') ? [parseInt(value)] : [];
                  console.log('[CREATE-FORM.TSX] Provider changed (single):', newProviderId);
                  setValue("provider_id", newProviderId);
                  handleFormChange();
                }}
              >
                <SelectTrigger>
                  <SelectValue placeholder="Seleccionar proveedor (prueba)" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">Todos los proveedores</SelectItem>
                  {filteredProviders.map((provider) => (
                    <SelectItem key={provider.id} value={provider.id.toString()}>
                      {provider.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <p className="text-xs text-muted-foreground">
                Modo prueba: solo un proveedor. {areaId && areaId.length > 0 
                  ? "Se muestran proveedores de las áreas seleccionadas"
                  : "Si no selecciona, se incluirán todos los proveedores"
                }
              </p>
            </div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle className="flex items-center space-x-2">
              <PrinterIcon className="h-5 w-5" />
              <span>Opciones de Impresión</span>
            </CardTitle>
            <CardDescription>
              Configure qué credenciales incluir en el lote
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex items-center space-x-2">
              <Checkbox
                id="only_unprinted"
                checked={onlyUnprinted}
                onCheckedChange={(checked) => {
                  const newValue = !!checked;
                  console.log('[CREATE-FORM.TSX] Only unprinted changed:', newValue);
                  setValue("only_unprinted", newValue);
                  handleFormChange();
                }}
              />
              <Label 
                htmlFor="only_unprinted" 
                className="text-sm font-medium leading-none peer-disabled:cursor-not-allowed peer-disabled:opacity-70"
              >
                Solo credenciales no impresas previamente
              </Label>
            </div>
            <p className="text-xs text-muted-foreground mt-2">
              Recomendado activar para evitar duplicados en la impresión
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Tab: Vista Previa */}
      <div data-tab="preview" className="space-y-6">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center space-x-2">
              <EyeIcon className="h-5 w-5" />
              <span>Vista Previa del Lote</span>
            </CardTitle>
            <CardDescription>
              Revise la cantidad de credenciales que se incluirán en el lote
            </CardDescription>
          </CardHeader>
          <CardContent>
            {isLoadingPreview && (
              <div className="flex items-center justify-center py-8">
                <LoaderIcon className="h-6 w-6 animate-spin mr-2" />
                <span>Calculando credenciales...</span>
              </div>
            )}

            {previewError && (
              <Alert variant="destructive">
                <AlertCircleIcon className="h-4 w-4" />
                <AlertDescription>{previewError}</AlertDescription>
              </Alert>
            )}

            {preview && !isLoadingPreview && !previewError && (
              <div className="space-y-4">
                {preview.can_create ? (
                  <Alert>
                    <CheckCircleIcon className="h-4 w-4" />
                    <AlertDescription>
                      Lote listo para crear con las siguientes especificaciones:
                    </AlertDescription>
                  </Alert>
                ) : (
                  <Alert variant="destructive">
                    <AlertCircleIcon className="h-4 w-4" />
                    <AlertDescription>
                      No se encontraron credenciales con los filtros especificados
                    </AlertDescription>
                  </Alert>
                )}

                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <Card>
                    <CardContent className="pt-6">
                      <div className="flex items-center space-x-2">
                        <UsersIcon className="h-8 w-8 text-blue-500" />
                        <div>
                          <p className="text-2xl font-bold">{preview.credentials_count}</p>
                          <p className="text-xs text-muted-foreground">Credenciales</p>
                        </div>
                      </div>
                    </CardContent>
                  </Card>

                  <Card>
                    <CardContent className="pt-6">
                      <div className="flex items-center space-x-2">
                        <FileTextIcon className="h-8 w-8 text-green-500" />
                        <div>
                          <p className="text-2xl font-bold">{preview.estimated_pages}</p>
                          <p className="text-xs text-muted-foreground">Páginas estimadas</p>
                        </div>
                      </div>
                    </CardContent>
                  </Card>

                  <Card>
                    <CardContent className="pt-6">
                      <div className="flex items-center space-x-2">
                        <PrinterIcon className="h-8 w-8 text-purple-500" />
                        <div>
                          <p className="text-2xl font-bold">1</p>
                          <p className="text-xs text-muted-foreground">Credencial/página</p>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                </div>

                <Separator />

                <div className="space-y-2">
                  <h4 className="text-sm font-medium">Resumen de Filtros Aplicados:</h4>
                  <div className="flex flex-wrap gap-2">
                    <Badge variant="outline">
                      Evento: {filtersData.events.find(e => e.id === eventId)?.name}
                    </Badge>
                    {areaId && areaId.length > 0 && (
                      <Badge variant="outline">
                        Áreas: {areaId.length} seleccionada(s)
                      </Badge>
                    )}
                    {providerId && providerId.length > 0 && (
                      <Badge variant="outline">
                        Proveedores: {providerId.length} seleccionado(s)
                      </Badge>
                    )}
                    <Badge variant={onlyUnprinted ? "default" : "secondary"}>
                      {onlyUnprinted ? "Solo no impresas" : "Todas las credenciales"}
                    </Badge>
                  </div>
                </div>
              </div>
            )}

            {!eventId && (
              <div className="text-center py-8 text-muted-foreground">
                <CalendarIcon className="h-12 w-12 mx-auto mb-4 opacity-50" />
                <p>Seleccione un evento para ver la vista previa</p>
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
