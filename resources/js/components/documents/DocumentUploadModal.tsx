import React, { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { getColumnLabel } from '@/utils/translations/column-labels';
import { UploadIcon, FileIcon } from 'lucide-react';
import { DocumentType } from '@/types';
import { toast } from 'sonner';

interface DocumentUploadModalProps {
  types: DocumentType[];
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
  onUpload: (file: File, typeId: string) => Promise<void>;
  isUploading: boolean;
}

export function DocumentUploadModal({
  types,
  isOpen,
  onOpenChange,
  onUpload,
  isUploading
}: DocumentUploadModalProps) {
  const [file, setFile] = useState<File | null>(null);
  const [selectedType, setSelectedType] = useState<string>('');
  const [dragActive, setDragActive] = useState(false);
  
  const reset = () => {
    setFile(null);
    setSelectedType('');
  };

  const handleClose = () => {
    if (!isUploading) {
      reset();
      onOpenChange(false);
    }
  };

  const handleDrag = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  };

  const handleDrop = (e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);
    
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      const droppedFile = e.dataTransfer.files[0];
      validateAndSetFile(droppedFile);
    }
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      validateAndSetFile(e.target.files[0]);
    }
  };

  const validateAndSetFile = (file: File) => {
    // Check if it's a PDF
    if (file.type !== 'application/pdf') {
      toast.error('Solo se permiten archivos PDF');
      return;
    }
    
    // Check file size (10MB max by default)
    const maxSize = 10 * 1024 * 1024; // 10MB in bytes
    if (file.size > maxSize) {
      toast.error('El archivo excede el tamaño máximo permitido (10MB)');
      return;
    }
    
    setFile(file);
  };

  const handleSubmit = async () => {
    if (!file) {
      toast.error('Seleccione un archivo');
      return;
    }
    
    if (!selectedType) {
      toast.error('Seleccione un tipo de documento');
      return;
    }
    
    try {
      await onUpload(file, selectedType);
      handleClose();
    } catch {
      // Error handling is done in the onUpload function
    }
  };

  return (
    <Dialog open={isOpen} onOpenChange={handleClose}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{getColumnLabel('documents', 'upload')}</DialogTitle>
          <DialogDescription>
            Seleccione un archivo PDF y un tipo de documento
          </DialogDescription>
        </DialogHeader>

        <div className="grid gap-4 py-4">
          <div className="grid gap-2">
            <Label htmlFor="document-type">
              {getColumnLabel('documents', 'type')}
            </Label>
            <Select
              value={selectedType}
              onValueChange={setSelectedType}
              disabled={isUploading}
            >
              <SelectTrigger 
                id="document-type"
                // Prevent focus recursion by preventing event propagation
                onFocus={(e: React.FocusEvent<HTMLButtonElement>) => {
                  // Stop the event from propagating up to parent FocusScope components
                  e.stopPropagation();
                }}
              >
                <SelectValue placeholder="Seleccione un tipo" />
              </SelectTrigger>
              <SelectContent>
                {types.map((type) => (
                  <SelectItem key={type.id} value={type.id.toString()}>
                    {type.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          <div className="grid gap-2">
            <Label htmlFor="file">{getColumnLabel('documents', 'filename')}</Label>
            <div
              className={`flex min-h-[120px] cursor-pointer flex-col items-center justify-center rounded-md border border-dashed p-4 transition-colors ${
                dragActive
                  ? 'border-primary bg-primary/10'
                  : 'border-muted-foreground/20'
              }`}
              onDragEnter={handleDrag}
              onDragLeave={handleDrag}
              onDragOver={handleDrag}
              onDrop={handleDrop}
              onClick={() => {
                if (!isUploading) {
                  document.getElementById('file-upload')?.click();
                }
              }}
            >
              <input
                id="file-upload"
                type="file"
                accept="application/pdf"
                className="hidden"
                onChange={handleFileChange}
                disabled={isUploading}
              />
              
              {file ? (
                <div className="flex flex-col items-center gap-2 text-center">
                  <FileIcon className="h-10 w-10 text-primary" />
                  <div>
                    <p className="text-sm font-medium">{file.name}</p>
                    <p className="text-xs text-muted-foreground">
                      {(file.size / 1024 / 1024).toFixed(2)} MB
                    </p>
                  </div>
                </div>
              ) : (
                <div className="flex flex-col items-center gap-2 text-center">
                  <UploadIcon className="h-10 w-10 text-muted-foreground" />
                  <div className="space-y-1">
                    <p className="text-sm font-medium">
                      Arrastre y suelte o haga clic para seleccionar
                    </p>
                    <p className="text-xs text-muted-foreground">
                      Solo archivos PDF (máx. 10MB)
                    </p>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={handleClose} disabled={isUploading}>
            Cancelar
          </Button>
          <Button 
            onClick={handleSubmit} 
            disabled={!file || !selectedType || isUploading}
            className="gap-2"
          >
            {isUploading && (
              <svg className="h-4 w-4 animate-spin" viewBox="0 0 24 24">
                <circle
                  className="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  strokeWidth="4"
                  fill="none"
                />
                <path
                  className="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                />
              </svg>
            )}
            Subir
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
