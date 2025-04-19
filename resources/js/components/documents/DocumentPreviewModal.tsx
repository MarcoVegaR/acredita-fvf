import React from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { getColumnLabel } from '@/utils/translations/column-labels';
import { FileIcon } from 'lucide-react';
import { Document } from '@/types';

interface DocumentPreviewModalProps {
  document: Document | null;
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
}

export function DocumentPreviewModal({
  document,
  isOpen,
  onOpenChange
}: DocumentPreviewModalProps) {
  if (!document) return null;

  return (
    <Dialog open={isOpen} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-4xl">
        <DialogHeader>
          <DialogTitle className="flex items-center gap-2">
            <FileIcon className="h-5 w-5" />
            {document.original_filename}
          </DialogTitle>
          <DialogDescription>
            {document.type.label} - {new Date(document.created_at).toLocaleDateString()}
          </DialogDescription>
        </DialogHeader>

        <div className="h-[600px] w-full overflow-hidden rounded border bg-muted">
          {document.mime_type === 'application/pdf' ? (
            <object
              data={`/documents/${document.uuid}/download`}
              type="application/pdf"
              width="100%"
              height="100%"
              aria-label={document.original_filename}
              className="h-full w-full"
            >
              <p>
                Tu navegador no puede mostrar este PDF.{' '}
                <a
                  href={`/documents/${document.uuid}/download`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-primary underline"
                >
                  Descárgalo aquí
                </a>
              </p>
            </object>
          ) : (
            <div className="flex h-full w-full items-center justify-center">
              <div className="text-center">
                <FileIcon className="mx-auto h-16 w-16 text-muted-foreground" />
                <p className="mt-2 text-sm text-muted-foreground">
                  La previsualización no está disponible para este tipo de archivo
                </p>
                <a
                  href={`/documents/${document.uuid}/download`}
                  className="mt-4 inline-block text-primary underline"
                  target="_blank"
                  rel="noopener noreferrer"
                >
                  Descargar archivo
                </a>
              </div>
            </div>
          )}
        </div>

        <DialogFooter className="sm:justify-between">
          <div className="text-sm text-muted-foreground">
            Subido por {document.user.name}
          </div>
          <Button
            variant="outline"
            onClick={() => window.open(`/documents/${document.uuid}/download`, '_blank')}
          >
            {getColumnLabel('documents', 'download')}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
