import React, { useState, useRef } from 'react';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { getColumnLabel } from '@/utils/translations/column-labels';
import { ImageType } from '@/types';

interface ImageUploadModalProps {
  open: boolean;
  onClose: () => void;
  onUpload: (files: FileList, typeCode: string) => void;
  types: ImageType[];
  uploading: boolean;
  module: string;
}

export default function ImageUploadModal({ 
  open, 
  onClose, 
  onUpload, 
  types, 
  uploading, 
  module 
}: ImageUploadModalProps) {
  // Usamos el módulo para generar un ID único para los campos del formulario
  const formId = `image-upload-${module}-${Date.now()}`;
  const [selectedType, setSelectedType] = useState<string>('');
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [previewUrl, setPreviewUrl] = useState<string | null>(null);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files.length > 0) {
      const file = e.target.files[0];
      setSelectedFile(file);
      
      // Create preview URL
      const reader = new FileReader();
      reader.onloadend = () => {
        setPreviewUrl(reader.result as string);
      };
      reader.readAsDataURL(file);
    } else {
      setSelectedFile(null);
      setPreviewUrl(null);
    }
  };

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (selectedFile && selectedType && fileInputRef.current?.files) {
      onUpload(fileInputRef.current.files, selectedType);
      resetForm();
    }
  };

  const resetForm = () => {
    setSelectedType('');
    setSelectedFile(null);
    setPreviewUrl(null);
    if (fileInputRef.current) {
      fileInputRef.current.value = '';
    }
  };

  const handleClose = () => {
    resetForm();
    onClose();
  };

  return (
    <Dialog 
      open={open} 
      onOpenChange={handleClose}
    >
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{getColumnLabel('images', 'upload_image')}</DialogTitle>
          <DialogDescription>
            {getColumnLabel('images', 'upload_instructions')}
          </DialogDescription>
        </DialogHeader>
        <form id={formId} onSubmit={handleSubmit} className="space-y-4">
          <div className="grid gap-4 py-4">
            <div className="grid grid-cols-4 items-center gap-4">
              <Label htmlFor={`${formId}-image-type`} className="text-right">
                {getColumnLabel('images', 'image_type')}
              </Label>
              <div className="col-span-3">
                <Select 
                  value={selectedType} 
                  onValueChange={setSelectedType}
                  disabled={uploading}
                >
                  <SelectTrigger 
                    id={`${formId}-image-type`} 
                    onFocus={(e: React.FocusEvent<HTMLButtonElement>) => {
                      // Detener la propagación del evento para evitar la recursión infinita
                      e.stopPropagation();
                    }}
                  >
                    <SelectValue placeholder={getColumnLabel('images', 'select_type')} />
                  </SelectTrigger>
                  <SelectContent>

                    {types.map((type) => (
                      <SelectItem key={type.code} value={type.code}>
                        {type.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            </div>
            <div className="grid grid-cols-4 items-center gap-4">
              <Label htmlFor="image-file" className="text-right">
                {getColumnLabel('images', 'filename')}
              </Label>
              <div className="col-span-3">
                <input
                  ref={fileInputRef}
                  id="image-file"
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  onChange={handleFileChange}
                  className="w-full cursor-pointer rounded-md text-sm file:mr-4 file:rounded-l-md file:border-0 file:bg-primary file:px-4 file:py-2 file:text-primary-foreground hover:file:bg-primary/90"
                />
              </div>
            </div>
            {previewUrl && (
              <div className="mt-4 flex justify-center">
                <div className="relative h-48 w-auto overflow-hidden rounded-md border">
                  <img
                    src={previewUrl}
                    alt="Preview"
                    className="h-full w-full object-contain"
                  />
                </div>
              </div>
            )}
          </div>
          <DialogFooter>
            <Button
              type="button"
              variant="outline"
              onClick={handleClose}
              disabled={uploading}
            >
              Cancelar
            </Button>
            <Button
              type="submit"
              disabled={!selectedFile || !selectedType || uploading}
            >
              {uploading ? 'Subiendo...' : 'Subir'}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}
