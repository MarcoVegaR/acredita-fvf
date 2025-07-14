import React, { useState, useEffect } from "react";
import { FormField, FormItem, FormLabel, FormControl, FormMessage, FormDescription } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Card, CardContent } from "@/components/ui/card";
import { LayoutEditor } from "./layout-editor/layout-editor";
import { EventOption, LayoutMeta } from "./types";
import { Checkbox } from "@/components/ui/checkbox";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";

// Eliminada función no utilizada

interface FileUploadProps {
  onChange: (file: File | null) => void;
  accept: string;
  maxSize: number;
  label?: string;
  description?: string;
  error?: string;
}

const FileUpload: React.FC<FileUploadProps> = ({
  onChange,
  accept,
  maxSize,
  label = "Arrastre un archivo aquí o haga clic para seleccionar",
  description,
  error,
}) => {
  const [dragActive, setDragActive] = useState(false);
  const [fileError, setFileError] = useState<string | null>(null);
  const [selectedFile, setSelectedFile] = useState<File | null>(null);

  const validateFile = (file: File): string | null => {
    console.log('Validando archivo:', { 
      nombre: file.name,
      tipo: file.type, 
      tamaño: file.size,
      tiposAceptados: accept
    });
    
    // Validar tipo de archivo correctamente dividiendo el string 'accept' en un array
    const acceptedTypes = accept.split(',').map(type => type.trim().toLowerCase());
    const fileType = file.type.toLowerCase();
    const fileExt = file.name.split('.').pop()?.toLowerCase();
    
    // Verificar si el tipo MIME está en la lista de tipos aceptados
    const isValidType = acceptedTypes.some(type => {
      // Verificar por tipo MIME exacto
      if (fileType === type) return true;
      
      // Si el tipo comienza con 'image/' o 'application/', verificar la coincidencia parcial
      if (type.includes('/') && fileType.startsWith(type.split('/')[0])) {
        const acceptedSubtype = type.split('/')[1];
        // Si el subtipo es un wildcard '*', aceptar cualquier subtipo de ese tipo
        if (acceptedSubtype === '*') return true;
      }
      
      // Verificar por extensión si el tipo aceptado comienza con '.'
      if (type.startsWith('.') && fileExt === type.substring(1)) return true;
      
      return false;
    });
    
    if (!isValidType) {
      console.error('Archivo rechazado: tipo no permitido', {
        tipoArchivo: fileType,
        extensión: fileExt,
        tiposAceptados: acceptedTypes
      });
      return "Tipo de archivo no permitido";
    }
    
    if (file.size > maxSize) {
      console.error('Archivo rechazado: tamaño excedido', {
        tamaño: file.size,
        máximo: maxSize
      });
      return `El archivo excede el tamaño máximo de ${maxSize / 1024 / 1024}MB`;
    }
    
    console.log('Archivo validado correctamente');
    return null;
  };

  const handleFileChange = (file: File | null) => {
    if (!file) {
      setSelectedFile(null);
      onChange(null);
      setFileError(null);
      return;
    }

    const validationError = validateFile(file);
    if (validationError) {
      setFileError(validationError);
      return;
    }

    setSelectedFile(file);
    setFileError(null);
    onChange(file);
  };

  const handleDrag = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    
    if (e.type === "dragenter" || e.type === "dragover") {
      setDragActive(true);
    } else if (e.type === "dragleave") {
      setDragActive(false);
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFileChange(e.dataTransfer.files[0]);
    }
  };

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      handleFileChange(e.target.files[0]);
    }
  };

  return (
    <div className="space-y-2">
      <div
        className={`border-2 border-dashed rounded-lg p-6 text-center transition-all
          ${dragActive ? "border-primary bg-primary/10" : "border-muted-foreground/25"}
          ${fileError ? "border-red-500 bg-red-50" : ""}
          ${selectedFile ? "border-green-500 bg-green-50" : ""}`}
        onDragEnter={handleDrag}
        onDragLeave={handleDrag}
        onDragOver={handleDrag}
        onDrop={handleDrop}
      >
        <input
          type="file"
          id="file-upload"
          className="hidden"
          accept={accept}
          onChange={handleFileSelect}
        />
        <label
          htmlFor="file-upload"
          className="flex flex-col items-center justify-center gap-2 cursor-pointer"
        >
          {selectedFile ? (
            <>
              <div className="text-green-600 font-medium">Archivo seleccionado:</div>
              <div className="text-sm">{selectedFile.name}</div>
              <div className="text-xs text-muted-foreground">
                ({Math.round(selectedFile.size / 1024)} KB)
              </div>
            </>
          ) : (
            <>
              <div className="text-lg font-medium">{label}</div>
              {description && <div className="text-sm text-muted-foreground">{description}</div>}
            </>
          )}
        </label>
      </div>
      
      {(fileError || error) && (
        <div className="text-sm text-red-500">
          {fileError || error}
        </div>
      )}
    </div>
  );
};

// Implementación del componente ImagePreview para mostrar una vista previa de la imagen
const ImagePreview: React.FC<{ src: string; alt: string }> = ({ src, alt }) => {
  return (
    <div className="border rounded-md overflow-hidden">
      <div className="aspect-[1.414/1] w-full relative">
        <img 
          src={src} 
          alt={alt} 
          className="object-contain w-full h-full"
        />
      </div>
    </div>
  );
};

// Importamos useFormContext del componente base-form
import { BaseFormOptions, useFormContext } from "@/components/base-form/base-form";
import { FormData } from "./helpers";

// Props específicas para el formulario de plantillas
interface TemplateSpecificOptions {
  events?: Array<{ id: number; name: string }>;
  templateFile?: string;
  isUpdate?: boolean;
  defaultLayout?: Partial<LayoutMeta>;
}

// Props del formulario - seguimos el patrón esperado por BaseFormPage
export function TemplateForm({ options }: { options: BaseFormOptions<FormData> & Partial<TemplateSpecificOptions> }) {
  // Obtenemos el formulario del contexto
  const { form } = useFormContext();
  
  // Extrayendo propiedades específicas con tipos seguros
  const events = options.events || [];
  const templateFile = options.templateFile || null;
  const isUpdate = options.isUpdate || false;
  
  // Estado para el archivo seleccionado para previsualización
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(templateFile);
  
  // Cuando se selecciona un archivo
  const handleFileChange = (file: File | null) => {
    setSelectedFile(file);
    if (file) {
      form.setValue("template_file", file);
      
      // Generar URL para previsualización
      const fileUrl = URL.createObjectURL(file);
      setPreviewUrl(fileUrl);
    } else {
      form.setValue("template_file", undefined);
      if (!isUpdate) {
        setPreviewUrl(null);
      }
    }
  };
  
  // Seleccionar la pestaña activa dependiendo si hay un archivo cargado
  const [activeTab, setActiveTab] = useState<string>("info");
  
  useEffect(() => {
    if (previewUrl && activeTab === "info") {
      setActiveTab("layout");
    }
  }, [previewUrl, activeTab]);

  // Limpieza de URLs de objeto cuando se desmonta el componente
  useEffect(() => {
    return () => {
      if (selectedFile && previewUrl && !templateFile) {
        URL.revokeObjectURL(previewUrl);
      }
    };
  }, [selectedFile, previewUrl, templateFile]);
  
  return (
    <div className="space-y-6">
      <Tabs value={activeTab} onValueChange={setActiveTab} className="w-full">
        <TabsList className="grid w-full grid-cols-2">
          <TabsTrigger value="info">Información básica</TabsTrigger>
          <TabsTrigger value="layout" disabled={!previewUrl}>Editor de Layout</TabsTrigger>
        </TabsList>
        
        {/* Pestaña de información básica */}
        <TabsContent value="info" className="space-y-6 pt-4">
          {/* Campo de Evento */}
          <FormField
            control={form.control}
            name="event_id"
            render={({ field }) => (
              <FormItem>
                <FormLabel>Evento</FormLabel>
                <Select 
                  onValueChange={(value) => field.onChange(parseInt(value))}
                  defaultValue={field.value ? field.value.toString() : ""}
                  disabled={isUpdate}
                >
                  <FormControl>
                    <SelectTrigger>
                      <SelectValue placeholder="Seleccione un evento" />
                    </SelectTrigger>
                  </FormControl>
                  <SelectContent>
                    {events.map((event: EventOption) => (
                      <SelectItem key={event.id} value={event.id.toString()}>
                        {event.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <FormDescription>
                  Seleccione el evento para el cual se utilizará esta plantilla.
                </FormDescription>
                <FormMessage />
              </FormItem>
            )}
          />
          
          {/* Campo de Nombre */}
          <FormField
            control={form.control}
            name="name"
            render={({ field }) => (
              <FormItem>
                <FormLabel>Nombre de la plantilla</FormLabel>
                <FormControl>
                  <Input placeholder="Ej. Plantilla básica" {...field} />
                </FormControl>
                <FormDescription>
                  Ingrese un nombre descriptivo para identificar esta plantilla.
                </FormDescription>
                <FormMessage />
              </FormItem>
            )}
          />
          
          {/* Campo de Archivo */}
          <FormField
            control={form.control}
            name="template_file"
            render={({ fieldState: { error } }) => (
              <FormItem>
                <FormLabel>Archivo de plantilla</FormLabel>
                <FormControl>
                  <FileUpload 
                    onChange={handleFileChange}
                    accept="image/jpeg,image/jpg,image/png,image/gif,image/bmp,application/pdf"
                    maxSize={5242880} // 5MB
                    label="Arrastre una imagen (JPEG, PNG, GIF, BMP) o PDF aquí"
                    description="Máximo 5MB"
                    error={error?.message}
                  />
                </FormControl>
                <FormDescription>
                  {isUpdate ? 
                    "Suba un nuevo archivo solo si desea reemplazar el actual." :
                    "Suba una imagen (JPEG, PNG, GIF, BMP) o PDF (máx. 5MB). Esta será la imagen de fondo para la plantilla."
                  }
                </FormDescription>
                <FormMessage />
              </FormItem>
            )}
          />
          
          {/* Vista previa del archivo */}
          {previewUrl && (
            <Card>
              <CardContent className="pt-4">
                <ImagePreview src={previewUrl} alt="Vista previa de plantilla" />
                <p className="text-sm text-muted-foreground mt-2">
                  Vista previa de la plantilla. Haga clic en "Editor de Layout" para configurar las zonas.
                </p>
              </CardContent>
            </Card>
          )}
          
          {/* Campo predeterminada */}
          <FormField
            control={form.control}
            name="is_default"
            render={({ field }) => (
              <FormItem className="flex flex-row items-start space-x-3 space-y-0 mt-6">
                <FormControl>
                  <Checkbox 
                    checked={field.value}
                    onCheckedChange={field.onChange}
                  />
                </FormControl>
                <div className="space-y-1 leading-none">
                  <FormLabel>Establecer como predeterminada</FormLabel>
                  <FormDescription>
                    Si marca esta opción, esta plantilla se usará como predeterminada para el evento seleccionado.
                  </FormDescription>
                </div>
                <FormMessage />
              </FormItem>
            )}
          />
        </TabsContent>
        
        {/* Pestaña de editor de layout */}
        <TabsContent value="layout" className="space-y-6 pt-4">
          {previewUrl ? (
            <LayoutEditor 
              form={form} 
              backgroundImage={previewUrl} 
            />
          ) : (
            <p className="text-center text-muted-foreground py-12">
              Primero debe cargar un archivo de plantilla en la pestaña "Información básica".
            </p>
          )}
        </TabsContent>
      </Tabs>
    </div>
  );
}
