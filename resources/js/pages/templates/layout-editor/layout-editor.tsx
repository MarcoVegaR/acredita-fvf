import React, { useState, useRef, useEffect } from "react";
import { 
  FormField, FormItem, FormLabel, FormControl, 
  FormMessage 
} from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Slider } from "@/components/ui/slider";
import { Select, SelectTrigger, SelectValue, SelectContent, SelectItem } from "@/components/ui/select";
import { Plus, Trash2, Move, ZoomIn, ZoomOut, Square, QrCode } from "lucide-react";
import { UseFormReturn } from "react-hook-form";
import { FormData } from "../helpers";
import { LayoutMeta } from "../types";

interface TextBlockItem {
  id: string;
  x?: number;
  y?: number;
  width?: number;
  height?: number;
}

interface LayoutEditorProps {
  form: UseFormReturn<FormData>; // Formulario de react-hook-form con el tipo FormData
  backgroundImage: string;
}

interface DraggableItemProps {
  id: string;
  x: number;
  y: number;
  width: number;
  height: number;
  color: string;
  icon?: React.ReactNode;
  label: string;
  onMove: (id: string, x: number, y: number) => void;
  onResize: (id: string, width: number, height: number) => void;
  onSelect: (id: string) => void;
  isSelected: boolean;
  scale: number;
}

const DraggableItem: React.FC<DraggableItemProps> = ({ 
  id, x, y, width, height, color, icon, label, 
  onMove, onResize, onSelect, isSelected, scale 
}) => {
  const itemRef = useRef<HTMLDivElement>(null);
  const [isDragging, setIsDragging] = useState(false);
  const [isResizing, setIsResizing] = useState(false);
  const [startPos, setStartPos] = useState({ x: 0, y: 0 });
  const [startSize, setStartSize] = useState({ width: 0, height: 0 });

  const handleMouseDown = (e: React.MouseEvent) => {
    e.preventDefault();
    onSelect(id);
    setIsDragging(true);
    setStartPos({ 
      x: e.clientX - x * scale, 
      y: e.clientY - y * scale 
    });
  };

  const handleResizeStart = (e: React.MouseEvent) => {
    e.preventDefault();
    e.stopPropagation();
    onSelect(id);
    setIsResizing(true);
    setStartPos({ 
      x: e.clientX, 
      y: e.clientY 
    });
    setStartSize({ 
      width, 
      height 
    });
  };

  useEffect(() => {
    const handleMouseMove = (e: MouseEvent) => {
      if (isDragging) {
        const newX = Math.max(0, (e.clientX - startPos.x) / scale);
        const newY = Math.max(0, (e.clientY - startPos.y) / scale);
        onMove(id, Math.round(newX), Math.round(newY));
      } else if (isResizing) {
        const dx = (e.clientX - startPos.x) / scale;
        const dy = (e.clientY - startPos.y) / scale;
        
        const newWidth = Math.max(20, startSize.width + dx);
        const newHeight = Math.max(20, startSize.height + dy);
        
        onResize(id, Math.round(newWidth), Math.round(newHeight));
      }
    };

    const handleMouseUp = () => {
      setIsDragging(false);
      setIsResizing(false);
    };

    if (isDragging || isResizing) {
      document.addEventListener('mousemove', handleMouseMove);
      document.addEventListener('mouseup', handleMouseUp);
    }

    return () => {
      document.removeEventListener('mousemove', handleMouseMove);
      document.removeEventListener('mouseup', handleMouseUp);
    };
  }, [isDragging, isResizing, startPos, id, onMove, onResize, startSize, scale]);

  return (
    <div 
      ref={itemRef}
      className={`absolute cursor-move ${isSelected ? 'ring-2 ring-primary ring-offset-2' : ''}`}
      style={{
        left: `${x * scale}px`,
        top: `${y * scale}px`,
        width: `${width * scale}px`,
        height: `${height * scale}px`,
        backgroundColor: `${color}40`,
        border: `2px solid ${color}`,
        borderRadius: '4px',
        zIndex: isSelected ? 10 : 1
      }}
      onMouseDown={handleMouseDown}
    >
      <div className="flex items-center justify-center h-full">
        {icon && <div className="mr-1">{icon}</div>}
        <span style={{ fontSize: `${Math.max(12, Math.min(width * scale / 8, 16))}px` }}>
          {label}
        </span>
      </div>
      {isSelected && (
        <div 
          className="absolute bottom-0 right-0 w-4 h-4 bg-primary cursor-se-resize" 
          style={{ transform: 'translate(50%, 50%)' }}
          onMouseDown={handleResizeStart}
        />
      )}
    </div>
  );
};

export function LayoutEditor({ form, backgroundImage }: LayoutEditorProps) {
  const canvasRef = useRef<HTMLDivElement>(null);
  const [scale, setScale] = useState(1);
  const [selectedItem, setSelectedItem] = useState<string | null>(null);
  const [image, setImage] = useState<HTMLImageElement | null>(null);
  // Definición del tamaño de la imagen cargada - usado en setImage.onload
  const [, setImageSize] = useState({ width: 0, height: 0 });
  // const [nextTextBlockId, setNextTextBlockId] = useState(1); // Commented out as not currently used

  // Obtener valores del formulario
  const layoutMeta = form.watch("layout_meta") as LayoutMeta;
  const fold_mm = layoutMeta?.fold_mm;
  const rect_photo = layoutMeta?.rect_photo;
  const rect_qr = layoutMeta?.rect_qr;
  const text_blocks = layoutMeta?.text_blocks || [];
  
  useEffect(() => {
    if (backgroundImage) {
      const img = new Image();
      img.src = backgroundImage;
      img.onload = () => {
        setImage(img);
        setImageSize({
          width: img.width,
          height: img.height
        });
        
        // Ajustar escala basada en el tamaño del contenedor
        if (canvasRef.current) {
          const containerWidth = canvasRef.current.clientWidth;
          const scale = containerWidth / img.width;
          setScale(Math.min(1, scale));
        }
      };
    }
  }, [backgroundImage]);

  const handleZoom = (factor: number) => {
    setScale(prev => Math.min(Math.max(0.2, prev * factor), 2));
  };

  const handleItemMove = (id: string, x: number | string, y: number | string) => {
    console.log(`Moviendo item ${id} a posición (${x}, ${y})`);
    
    // Asegurar que x e y sean números
    const numX = Number(x);
    const numY = Number(y);
    
    console.log('[DEBUG] Antes de actualizar - Form sucio:', form.formState.isDirty);
    console.log('[DEBUG] Antes de actualizar - Campos sucios:', form.formState.dirtyFields);

    if (isNaN(numX) || isNaN(numY)) {
      console.error('Valores inválidos para mover:', { id, x, y });
      return;
    }

    // Forzar la conversión numérica de layout_meta.fold_mm
    const currentFoldMm = form.getValues('layout_meta.fold_mm');
    if (currentFoldMm && typeof currentFoldMm === 'string') {
      const numFoldMm = Number(currentFoldMm);
      console.log('[LayoutEditor] Convirtiendo fold_mm de', currentFoldMm, 'a', numFoldMm);
      form.setValue('layout_meta.fold_mm', numFoldMm, { shouldDirty: true, shouldValidate: true });
    }

    if (id === 'photo') {
      const updatedPhoto = {
        x: numX,
        y: numY,
        width: Number(rect_photo?.width || 0),
        height: Number(rect_photo?.height || 0),
      };
      form.setValue("layout_meta.rect_photo", updatedPhoto, { shouldDirty: true, shouldValidate: true });
      console.log('[LayoutEditor] rect_photo setValue', updatedPhoto);
      console.log('Actualizado rect_photo:', updatedPhoto);
    } else if (id === 'qr') {
      const updatedQR = {
        x: numX,
        y: numY,
        width: Number(rect_qr?.width || 0),
        height: Number(rect_qr?.height || 0),
      };
      form.setValue("layout_meta.rect_qr", updatedQR, { shouldDirty: true, shouldValidate: true });
      console.log('[LayoutEditor] rect_qr setValue', updatedQR);
      console.log('Actualizado rect_qr:', updatedQR);
    } else {
      const index = text_blocks?.findIndex((block: TextBlockItem) => block.id === id) ?? -1;
      if (index !== -1 && Array.isArray(text_blocks)) {
        const newBlocks = [...text_blocks];
        const block = newBlocks[index];
        newBlocks[index] = {
          ...block,
          x: numX,
          y: numY,
          width: Number(block.width),
          height: Number(block.height),
        };
        form.setValue("layout_meta.text_blocks", newBlocks, { shouldDirty: true, shouldValidate: true });
        console.log('[LayoutEditor] text_blocks setValue', newBlocks);
        console.log('Actualizado text_block:', newBlocks[index]);
      }
    }
  };

  const handleItemResize = (id: string, width: number | string, height: number | string) => {
    console.log(`Redimensionando item ${id} a tamaño (${width}, ${height})`);
    
    // Asegurar que width y height sean números
    const numWidth = typeof width === 'string' ? parseFloat(width) : width;
    const numHeight = typeof height === 'string' ? parseFloat(height) : height;

    console.log('[DEBUG] Antes de actualizar - Form sucio:', form.formState.isDirty);
    console.log('[DEBUG] Antes de actualizar - Campos sucios:', form.formState.dirtyFields);

    if (isNaN(numWidth) || isNaN(numHeight)) {
      console.error('Valores inválidos para redimensionar:', { id, width, height });
      return;
    }

    if (id === 'photo') {
      const updatedPhoto = {
        x: Number(rect_photo?.x || 0),
        y: Number(rect_photo?.y || 0),
        width: numWidth,
        height: numHeight,
      };
      form.setValue("layout_meta.rect_photo", updatedPhoto, { shouldDirty: true, shouldValidate: true });
      console.log('[LayoutEditor] rect_photo setValue', updatedPhoto);
    } else if (id === 'qr') {
      const updatedQR = {
        x: Number(rect_qr?.x || 0),
        y: Number(rect_qr?.y || 0),
        width: numWidth,
        height: numHeight,
      };
      form.setValue("layout_meta.rect_qr", updatedQR, { shouldDirty: true, shouldValidate: true });
      console.log('[LayoutEditor] rect_qr setValue', updatedQR);
    } else {
      const index = text_blocks?.findIndex((block: TextBlockItem) => block.id === id) ?? -1;
      if (index !== -1 && Array.isArray(text_blocks)) {
        const newBlocks = [...text_blocks];
        const block = newBlocks[index];
        newBlocks[index] = {
          ...block,
          x: Number(block.x),
          y: Number(block.y),
          width: numWidth,
          height: numHeight,
        };
        form.setValue("layout_meta.text_blocks", newBlocks, { shouldDirty: true, shouldValidate: true });
        console.log('[LayoutEditor] text_blocks setValue', newBlocks);
      }
    }
  };

  // Inicializar campos predeterminados si no existen
  const initializeDefaultFields = () => {
    const defaultFields = [
      {
        id: 'cedula',
        x: 50,
        y: 80,
        width: 150,
        height: 18,
        font_size: 10,
        alignment: "left" as "left" | "center" | "right"
      },
      {
        id: 'nombre',
        x: 150,
        y: 120,
        width: 200,
        height: 25,
        font_size: 14,
        alignment: "center" as "left" | "center" | "right"
      },
      {
        id: 'rol',
        x: 150,
        y: 150,
        width: 200,
        height: 20,
        font_size: 12,
        alignment: "center" as "left" | "center" | "right"
      },
      {
        id: 'zona',
        x: 50,
        y: 280,
        width: 300,
        height: 35,
        font_size: 10,
        alignment: "left" as "left" | "center" | "right"
      }
    ];

    // Solo agregar campos que no existen
    const existingIds = text_blocks.map(block => block.id);
    const newFields = defaultFields.filter(field => !existingIds.includes(field.id));
    
    if (newFields.length > 0) {
      form.setValue("layout_meta.text_blocks", [...text_blocks, ...newFields]);
    }
  };

  const addTextBlock = () => {
    // Siempre inicializar campos por defecto primero
    initializeDefaultFields();
    
    // Si hay campos personalizados por agregar
    const customId = prompt('ID personalizado (opcional, deje vacío para solo inicializar campos estándar):');
    
    if (customId && customId.trim()) {
      if (!/^[a-zA-Z_][a-zA-Z0-9_]*$/.test(customId)) {
        alert('ID inválido. Debe comenzar con letra o guión bajo y contener solo letras, números y guiones bajos.');
        return;
      }
      
      const usedIds = form.getValues("layout_meta.text_blocks")?.map(block => block.id) || [];
      if (usedIds.includes(customId)) {
        alert('Este ID ya está en uso. Elija uno diferente.');
        return;
      }
      
      const newBlock = {
        id: customId,
        x: 50,
        y: 50,
        width: 100,
        height: 30,
        font_size: 12,
        alignment: "left" as "left" | "center" | "right"
      };
      
      const currentBlocks = form.getValues("layout_meta.text_blocks") || [];
      form.setValue("layout_meta.text_blocks", [...currentBlocks, newBlock]);
      setSelectedItem(newBlock.id);
    }
  };

  const removeTextBlock = (id: string) => {
    const index = text_blocks.findIndex((block: TextBlockItem) => block.id === id);
    if (index !== -1) {
      const newBlocks = [...text_blocks];
      newBlocks.splice(index, 1);
      form.setValue("layout_meta.text_blocks", newBlocks, { shouldDirty: true, shouldValidate: true });
      setSelectedItem(null);
    }
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <div className="lg:col-span-2">
        <Card>
          <CardHeader className="pb-2">
            <div className="flex justify-between items-center">
              <CardTitle>Editor de Layout</CardTitle>
              <div className="flex items-center gap-2">
                {/* Botón de depuración eliminado */}
                <Button 
                  size="icon" 
                  variant="outline" 
                  onClick={() => handleZoom(1.2)}
                  title="Acercar"
                >
                  <ZoomIn className="h-4 w-4" />
                </Button>
                <Button 
                  size="icon" 
                  variant="outline" 
                  onClick={() => handleZoom(0.8)}
                  title="Alejar"
                >
                  <ZoomOut className="h-4 w-4" />
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div className="relative mb-4">
              <FormLabel>Línea de pliegue (mm desde el borde izquierdo)</FormLabel>
              <div className="flex items-center gap-4 mt-1">
                <div className="flex-grow">
                  <FormField
                    control={form.control}
                    name="layout_meta.fold_mm"
                    render={({ field }) => (
                      <Slider
                        defaultValue={[field.value || 50]}
                        min={0}
                        max={image ? image.width : 300}
                        step={1}
                        onValueChange={(values) => field.onChange(values[0])}
                      />
                    )}
                  />
                </div>
                <FormField
                  control={form.control}
                  name="layout_meta.fold_mm"
                  render={({ field }) => (
                    <Input
                      type="number"
                      value={field.value || 50}
                      onChange={(e) => {
                        // Forzar conversión a número
                        const numValue = parseInt(e.target.value);
                        console.log('[LayoutEditor] fold_mm convertido de', e.target.value, 'a', numValue);
                        field.onChange(numValue);
                        // Forzar actualización directa en form.setValue
                        form.setValue('layout_meta.fold_mm', numValue, { 
                          shouldDirty: true,
                          shouldValidate: true 
                        });
                      }}
                      min={0}
                      className="w-20"
                    />
                  )}
                />
              </div>
            </div>
            
            <div 
              ref={canvasRef} 
              className="relative overflow-hidden border rounded-lg"
              style={{ 
                height: image ? `${image.height * scale}px` : '400px',
                width: '100%'
              }}
            >
              {/* Imagen de fondo */}
              {image && (
                <img 
                  src={backgroundImage} 
                  alt="Template background" 
                  style={{
                    width: `${image.width * scale}px`,
                    height: `${image.height * scale}px`,
                    objectFit: 'contain'
                  }}
                />
              )}
              
              {/* Línea de pliegue (vertical para díptico) */}
              {image && (
                <div 
                  className="absolute top-0 bottom-0 border-l-2 border-dashed border-blue-500"
                  style={{ 
                    left: `${((fold_mm || 0) / image.width) * image.width * scale}px`,
                    height: `${image.height * scale}px` 
                  }}
                />
              )}
              
              {/* Rectángulo de foto */}
              {rect_photo && (
                <DraggableItem
                  id="photo"
                  x={Number(rect_photo.x)}
                  y={Number(rect_photo.y)}
                  width={Number(rect_photo.width)}
                  height={Number(rect_photo.height)}
                  color="#3b82f6" // blue
                  icon={<Square className="h-4 w-4" />}
                  label="Foto"
                  onMove={handleItemMove}
                  onResize={handleItemResize}
                  onSelect={setSelectedItem}
                  isSelected={selectedItem === 'photo'}
                  scale={scale}
                />
              )}
              
              {/* Rectángulo de QR */}
              {rect_qr && (
                <DraggableItem
                  id="qr"
                  x={Number(rect_qr.x)}
                  y={Number(rect_qr.y)}
                  width={Number(rect_qr.width)}
                  height={Number(rect_qr.height)}
                  color="#10b981" // green
                  icon={<QrCode className="h-4 w-4" />}
                  label="QR"
                  onMove={handleItemMove}
                  onResize={handleItemResize}
                  onSelect={setSelectedItem}
                  isSelected={selectedItem === 'qr'}
                  scale={scale}
                />
              )}
              
              {/* Bloques de texto */}
              {text_blocks && text_blocks.map((block: TextBlockItem) => (
                <DraggableItem
                  key={block.id}
                  id={block.id}
                  x={Number(block.x)}
                  y={Number(block.y)}
                  width={Number(block.width)}
                  height={Number(block.height)}
                  color="#f97316" // orange
                  label={block.id}
                  onMove={handleItemMove}
                  onResize={handleItemResize}
                  onSelect={setSelectedItem}
                  isSelected={selectedItem === block.id}
                  scale={scale}
                />
              ))}
            </div>
          </CardContent>
        </Card>
        
        {/* Botón para agregar bloque de texto */}
        <div className="mt-4 flex justify-end">
          <Button 
            type="button"
            variant="outline" 
            className="gap-1"
            onClick={(e) => {
              e.preventDefault();
              addTextBlock();
            }}
          >
            <Plus className="h-4 w-4" />
            Inicializar campos estándar
          </Button>
        </div>
      </div>
      
      {/* Panel de control lateral */}
      <div className="lg:col-span-1">
        <Card>
          <CardHeader>
            <CardTitle>
              {selectedItem === 'photo' ? 'Editar área de foto' : 
              selectedItem === 'qr' ? 'Editar área de QR' : 
              selectedItem ? `Editar ${selectedItem}` : 'Panel de control'}
            </CardTitle>
          </CardHeader>
          <CardContent>
            {!selectedItem && (
              <div className="text-center py-6 text-muted-foreground">
                <Move className="h-8 w-8 mx-auto mb-2" />
                <p>Seleccione un elemento en el canvas para editarlo.</p>
              </div>
            )}
            
            {selectedItem === 'photo' && (
              <div className="space-y-4">
                <FormField
                  control={form.control}
                  name="layout_meta.rect_photo.x"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Posición X</FormLabel>
                      <FormControl>
                        <Input type="number" {...field} onChange={e => field.onChange(parseInt(e.target.value))} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                
                <FormField
                  control={form.control}
                  name="layout_meta.rect_photo.y"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Posición Y</FormLabel>
                      <FormControl>
                        <Input type="number" {...field} onChange={e => field.onChange(parseInt(e.target.value))} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                
                <FormField
                  control={form.control}
                  name="layout_meta.rect_photo.width"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Ancho</FormLabel>
                      <FormControl>
                        <Input type="number" {...field} onChange={e => field.onChange(parseInt(e.target.value))} min={1} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                
                <FormField
                  control={form.control}
                  name="layout_meta.rect_photo.height"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Alto</FormLabel>
                      <FormControl>
                        <Input type="number" {...field} onChange={e => field.onChange(parseInt(e.target.value))} min={1} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
            )}
            
            {selectedItem === 'qr' && (
              <div className="space-y-4">
                <FormField
                  control={form.control}
                  name="layout_meta.rect_qr.x"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Posición X</FormLabel>
                      <FormControl>
                        <Input type="number" {...field} onChange={e => field.onChange(parseInt(e.target.value))} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                
                <FormField
                  control={form.control}
                  name="layout_meta.rect_qr.y"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Posición Y</FormLabel>
                      <FormControl>
                        <Input type="number" {...field} onChange={e => field.onChange(parseInt(e.target.value))} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                
                <FormField
                  control={form.control}
                  name="layout_meta.rect_qr.width"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Ancho</FormLabel>
                      <FormControl>
                        <Input type="number" {...field} onChange={e => field.onChange(parseInt(e.target.value))} min={1} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
                
                <FormField
                  control={form.control}
                  name="layout_meta.rect_qr.height"
                  render={({ field }) => (
                    <FormItem>
                      <FormLabel>Alto</FormLabel>
                      <FormControl>
                        <Input type="number" {...field} onChange={e => field.onChange(parseInt(e.target.value))} min={1} />
                      </FormControl>
                      <FormMessage />
                    </FormItem>
                  )}
                />
              </div>
            )}
            
            {selectedItem && selectedItem !== 'photo' && selectedItem !== 'qr' && (
              <>
                <div className="space-y-4">
                  {text_blocks.map((block: TextBlockItem, index: number) => {
                    if (block.id === selectedItem) {
                      return (
                        <React.Fragment key={block.id}>
                          <FormField
                            control={form.control}
                            name={`layout_meta.text_blocks.${index}.x`}
                            render={({ field }) => (
                              <FormItem>
                                <FormLabel>Posición X</FormLabel>
                                <FormControl>
                                  <Input type="number" {...field} onChange={e => field.onChange(parseInt(e.target.value))} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />
                          
                          <FormField
                            control={form.control}
                            name={`layout_meta.text_blocks.${index}.y`}
                            render={({ field }) => (
                              <FormItem>
                                <FormLabel>Posición Y</FormLabel>
                                <FormControl>
                                  <Input type="number" {...field} onChange={e => field.onChange(parseInt(e.target.value))} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />
                          
                          <FormField
                            control={form.control}
                            name={`layout_meta.text_blocks.${index}.width`}
                            render={({ field }) => (
                              <FormItem>
                                <FormLabel>Ancho</FormLabel>
                                <FormControl>
                                  <Input type="number" {...field} onChange={e => field.onChange(parseInt(e.target.value))} min={1} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />
                          
                          <FormField
                            control={form.control}
                            name={`layout_meta.text_blocks.${index}.height`}
                            render={({ field }) => (
                              <FormItem>
                                <FormLabel>Alto</FormLabel>
                                <FormControl>
                                  <Input type="number" {...field} onChange={e => field.onChange(parseInt(e.target.value))} min={1} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />
                          
                          <FormField
                            control={form.control}
                            name={`layout_meta.text_blocks.${index}.font_size`}
                            render={({ field }) => (
                              <FormItem>
                                <FormLabel>Tamaño de fuente</FormLabel>
                                <FormControl>
                                  <Input type="number" {...field} onChange={e => field.onChange(parseInt(e.target.value))} min={1} />
                                </FormControl>
                                <FormMessage />
                              </FormItem>
                            )}
                          />
                          
                          <FormField
                            control={form.control}
                            name={`layout_meta.text_blocks.${index}.alignment`}
                            render={({ field }) => (
                              <FormItem>
                                <FormLabel>Alineación</FormLabel>
                                <Select 
                                  onValueChange={field.onChange}
                                  defaultValue={field.value}
                                >
                                  <FormControl>
                                    <SelectTrigger>
                                      <SelectValue placeholder="Seleccione alineación" />
                                    </SelectTrigger>
                                  </FormControl>
                                  <SelectContent>
                                    <SelectItem value="left">Izquierda</SelectItem>
                                    <SelectItem value="center">Centro</SelectItem>
                                    <SelectItem value="right">Derecha</SelectItem>
                                  </SelectContent>
                                </Select>
                                <FormMessage />
                              </FormItem>
                            )}
                          />
                          
                          <div className="pt-4">
                            <Button 
                              variant="destructive" 
                              size="sm"
                              onClick={() => removeTextBlock(block.id)}
                              className="w-full flex items-center gap-2"
                            >
                              <Trash2 className="h-4 w-4" />
                              Eliminar bloque de texto
                            </Button>
                          </div>
                        </React.Fragment>
                      );
                    }
                    return null;
                  })}
                </div>
              </>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
