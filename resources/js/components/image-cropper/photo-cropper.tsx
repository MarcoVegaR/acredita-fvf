import React, { useState, useCallback, useRef } from "react";
import Cropper from "react-easy-crop";
import { Button } from "@/components/ui/button";
import { 
  Dialog, 
  DialogContent, 
  DialogHeader, 
  DialogTitle, 
  DialogFooter 
} from "@/components/ui/dialog";
import { Image as ImageIcon, Upload, Trash, Camera } from "lucide-react";
import { Label } from "@/components/ui/label";

interface PhotoCropperProps {
  onChange: (croppedImage: string | null) => void;
  value?: string | null;
  aspectRatio?: number;
  cropShape?: "rect" | "round";
  className?: string;
}

const PhotoCropper = ({ 
  onChange, 
  value = null, 
  aspectRatio = 3/4, // Proporción 3x4 cm por defecto
  cropShape = "rect",
  className = "",
}: PhotoCropperProps) => {
  const [image, setImage] = useState<string | null>(value);
  const [crop, setCrop] = useState({ x: 0, y: 0 });
  const [zoom, setZoom] = useState(1);
  const [rotation, setRotation] = useState(0);
  
  // Definimos interfaces para las áreas de recorte
  interface CropArea {
    x: number;
    y: number;
    width: number;
    height: number;
  }
  
  const [croppedAreaPixels, setCroppedAreaPixels] = useState<CropArea | null>(null);
  const [isDialogOpen, setIsDialogOpen] = useState(false);
  const [tempImage, setTempImage] = useState<string | null>(null);
  
  const inputRef = useRef<HTMLInputElement>(null);

  // La interfaz CropArea ya está definida arriba

  const onCropComplete = useCallback((croppedArea: CropArea, croppedAreaPixels: CropArea) => {
    setCroppedAreaPixels(croppedAreaPixels);
  }, []);

  const createImage = (url: string): Promise<HTMLImageElement> =>
    new Promise((resolve, reject) => {
      const image = new Image();
      image.addEventListener('load', () => resolve(image));
      image.addEventListener('error', (error) => reject(error));
      image.setAttribute('crossOrigin', 'anonymous');
      image.src = url;
    });

  const getCroppedImg = async (
    imageSrc: string,
    pixelCrop: CropArea,
    rotation = 0
  ): Promise<string> => {
    const image = await createImage(imageSrc);
    const canvas = document.createElement('canvas');
    const ctx = canvas.getContext('2d');

    if (!ctx) {
      throw new Error('No se pudo obtener el contexto 2d del canvas');
    }

    // Establecer dimensiones del canvas para mantener la proporción 3:4
    const maxWidth = 300;  // ancho máximo
    // Altura calculada basada en la proporción, no necesitamos una constante separada
    
    canvas.width = pixelCrop.width < maxWidth ? pixelCrop.width : maxWidth;
    canvas.height = (canvas.width * 4) / 3; // Mantener proporción 3:4
    
    // Rotar y dibujar la imagen en el canvas
    ctx.save();
    ctx.translate(canvas.width / 2, canvas.height / 2);
    ctx.rotate((rotation * Math.PI) / 180);
    ctx.translate(-canvas.width / 2, -canvas.height / 2);
    
    // Calculamos la escala implícitamente al dibujar la imagen
    
    ctx.drawImage(
      image,
      pixelCrop.x,
      pixelCrop.y,
      pixelCrop.width,
      pixelCrop.height,
      0,
      0,
      canvas.width,
      canvas.height
    );
    
    ctx.restore();
    
    // Convertir a base64
    return canvas.toDataURL('image/jpeg', 0.9);
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files.length > 0) {
      const reader = new FileReader();
      reader.onload = () => {
        setTempImage(reader.result as string);
        setIsDialogOpen(true);
      };
      reader.readAsDataURL(e.target.files[0]);
      // Reset input value so the same file can be selected again if needed
      if (inputRef.current) inputRef.current.value = '';
    }
  };

  const handleSaveCrop = async () => {
    if (tempImage && croppedAreaPixels) {
      try {
        const croppedImage = await getCroppedImg(
          tempImage,
          croppedAreaPixels,
          rotation
        );
        setImage(croppedImage);
        onChange(croppedImage);
        setIsDialogOpen(false);
      } catch (e) {
        console.error(e);
      }
    }
  };

  const handleRemoveImage = () => {
    setImage(null);
    onChange(null);
    if (inputRef.current) inputRef.current.value = '';
  };

  const triggerFileInput = () => {
    if (inputRef.current) inputRef.current.click();
  };

  return (
    <div className={`photo-cropper ${className}`}>
      <div className="mb-2">
        <Label htmlFor="photo-upload">Fotografía (proporción 3×4 cm)</Label>
      </div>
      
      <div className="flex items-center gap-4">
        {/* Vista previa de la imagen */}
        <div 
          className={`w-24 h-32 border border-dashed rounded-md overflow-hidden flex items-center justify-center bg-muted/30 relative`}
          style={{ aspectRatio: `${aspectRatio}` }}
        >
          {image ? (
            <img 
              src={image} 
              alt="Foto de empleado" 
              className="w-full h-full object-cover" 
            />
          ) : (
            <ImageIcon className="h-8 w-8 text-muted-foreground" />
          )}
        </div>
        
        {/* Botones de acción */}
        <div className="flex flex-col gap-2">
          <Button 
            type="button" 
            size="sm" 
            variant="outline"
            onClick={triggerFileInput}
          >
            <Upload className="h-4 w-4 mr-2" />
            {image ? "Cambiar foto" : "Subir foto"}
          </Button>
          
          {image && (
            <Button 
              type="button" 
              size="sm" 
              variant="destructive" 
              onClick={handleRemoveImage}
            >
              <Trash className="h-4 w-4 mr-2" />
              Eliminar
            </Button>
          )}
          
          <input
            ref={inputRef}
            type="file"
            id="photo-upload"
            accept="image/*"
            onChange={handleFileChange}
            className="hidden"
          />
        </div>
      </div>

      {/* Diálogo de recorte de imagen */}
      <Dialog open={isDialogOpen} onOpenChange={setIsDialogOpen}>
        <DialogContent className="sm:max-w-[500px]">
          <DialogHeader>
            <DialogTitle>Recorta tu fotografía (proporción 3×4)</DialogTitle>
          </DialogHeader>
          
          <div className="relative w-full h-[350px] mt-4">
            {tempImage && (
              <Cropper
                image={tempImage}
                crop={crop}
                zoom={zoom}
                rotation={rotation}
                aspect={aspectRatio}
                cropShape={cropShape}
                onCropChange={setCrop}
                onCropComplete={onCropComplete}
                onZoomChange={setZoom}
              />
            )}
          </div>
          
          <div className="flex items-center justify-center gap-2 mt-4">
            <label className="text-sm">Zoom:</label>
            <input
              type="range"
              min={1}
              max={3}
              step={0.1}
              value={zoom}
              onChange={(e) => setZoom(Number(e.target.value))}
              className="w-1/3"
            />
            
            <label className="text-sm ml-4">Rotación:</label>
            <input
              type="range"
              min={0}
              max={360}
              step={1}
              value={rotation}
              onChange={(e) => setRotation(Number(e.target.value))}
              className="w-1/3"
            />
          </div>
          
          <DialogFooter>
            <Button 
              variant="outline" 
              onClick={() => setIsDialogOpen(false)}
            >
              Cancelar
            </Button>
            <Button 
              onClick={handleSaveCrop}
            >
              <Camera className="h-4 w-4 mr-2" />
              Aplicar recorte
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
};

export default PhotoCropper;
