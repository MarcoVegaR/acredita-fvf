import React from 'react';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { getColumnLabel } from '@/utils/translations/column-labels';
import { Image as ImageType } from '@/types';
import { formatBytes } from '@/utils/format-helpers';

interface ImagePreviewModalProps {
  open: boolean;
  onClose: () => void;
  image: ImageType | null;
  previewUrl: string;
}

export default function ImagePreviewModal({
  open,
  onClose,
  image,
  previewUrl
}: ImagePreviewModalProps) {
  if (!image) return null;

  const dimensions = image.width && image.height 
    ? `${image.width} × ${image.height}px` 
    : 'Dimensiones no disponibles';

  return (
    <Dialog open={open} onOpenChange={onClose}>
      <DialogContent className="sm:max-w-3xl">
        <DialogHeader>
          <DialogTitle>{image.name}</DialogTitle>
          <DialogDescription>
            Vista previa de la imagen con detalles adicionales
          </DialogDescription>
        </DialogHeader>
        <div className="flex flex-col items-center justify-center">
          <div className="relative max-h-[60vh] overflow-hidden rounded-md border">
            <img
              src={previewUrl}
              alt={image.name}
              className="h-full w-auto max-w-full object-contain"
            />
          </div>
          <div className="mt-4 grid w-full grid-cols-2 gap-4 text-sm">
            <div>
              <span className="font-semibold">{getColumnLabel('images', 'mime_type')}:</span>{' '}
              {image.mime_type}
            </div>
            <div>
              <span className="font-semibold">{getColumnLabel('images', 'size')}:</span>{' '}
              {formatBytes(image.size)}
            </div>
            <div>
              <span className="font-semibold">{getColumnLabel('images', 'dimensions')}:</span>{' '}
              {dimensions}
            </div>
            <div>
              <span className="font-semibold">{getColumnLabel('images', 'created_at')}:</span>{' '}
              {new Date(image.created_at).toLocaleString()}
            </div>
          </div>
        </div>
        <DialogFooter className="sm:justify-between">
          <div className="text-sm text-muted-foreground">
            {image.created_by ? `Subido por ${image.created_by}` : ''}
          </div>
          <Button
            variant="outline"
            onClick={() => window.open(previewUrl, '_blank')}
          >
            Ver tamaño completo
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
