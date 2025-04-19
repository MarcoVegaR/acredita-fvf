import React, { useEffect, useState } from 'react';
import { useDocuments } from '@/hooks/useDocuments';
import { DocumentPreviewModal } from './DocumentPreviewModal';
import { DocumentUploadModal } from './DocumentUploadModal';
import { Button } from '@/components/ui/button';
import { 
  Table, 
  TableHeader, 
  TableBody, 
  TableRow, 
  TableHead, 
  TableCell 
} from '@/components/ui/table';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { getColumnLabel } from '@/utils/translations/column-labels';
import { Document, DocumentType } from '@/types';
import { FileIcon, FileTextIcon, DownloadIcon, EyeIcon, TrashIcon, UploadIcon } from 'lucide-react';

interface DocumentsSectionProps {
  module: string;
  entityId: number;
  types: DocumentType[];
  permissions: string[];
  readOnly?: boolean; // Modo solo lectura para vista de detalles (show)
}

export function DocumentsSection({
  module,
  entityId,
  types,
  permissions,
  readOnly = false // Por defecto no es modo solo lectura
}: DocumentsSectionProps) {
  const { 
    documents, 
    loading, 
    error, 
    list, 
    upload, 
    remove, 
    previewUrl 
  } = useDocuments(module, entityId);

  const [selectedDocument, setSelectedDocument] = useState<Document | null>(null);
  const [isPreviewModalOpen, setIsPreviewModalOpen] = useState(false);
  const [isUploadModalOpen, setIsUploadModalOpen] = useState(false);
  const [documentToDelete, setDocumentToDelete] = useState<Document | null>(null);
  const [isAlertDialogOpen, setIsAlertDialogOpen] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const [isUploading, setIsUploading] = useState(false);



  // Implementación correcta de verificación de permisos
  // Creamos una función auxiliar para verificar permisos de forma más robusta
  const hasDocumentPermission = (action: string, moduleSpecific: boolean = true): boolean => {
    const genericPermission = `documents.${action}`;
    const modulePermission = `documents.${action}.${module}`;
    

    
    // Verificar el permiso específico del módulo primero si se requiere
    if (moduleSpecific && permissions.includes(modulePermission)) {
      return true;
    }
    
    // Verificar el permiso genérico como fallback
    return permissions.includes(genericPermission);
  };
  
  // Para desarrollo en entorno local, permitimos operaciones con documentos para facilitar pruebas
  // En producción, esto se eliminaría y se usaría solo la lógica de permisos
  const isDevelopment = process.env.NODE_ENV === 'development' || window.location.hostname === 'localhost';
  
  // FIJAR LA VISTA - Detectar si estamos en la vista dedicada de documentos
  // La vista dedicada de documentos siempre termina con /documents
  const isDocumentsPage = window.location.pathname.endsWith('/documents');

  // Comprobar permisos para diferentes acciones
  // En la vista dedicada de documentos, siempre permitir subir (para usuarios con permisos)
  const canUpload = (isDocumentsPage || !readOnly) && (isDevelopment || hasDocumentPermission('upload'));
  const canDelete = !readOnly && (isDevelopment || hasDocumentPermission('delete'));
  const canDownload = isDevelopment || hasDocumentPermission('download');
  


  useEffect(() => {
    list();
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [entityId]);

  const handlePreview = (document: Document) => {
    setSelectedDocument(document);
    setIsPreviewModalOpen(true);
  };

  const handleDelete = (document: Document) => {
    setDocumentToDelete(document);
    setIsAlertDialogOpen(true);
  };

  const handleConfirmDelete = async () => {
    if (!documentToDelete) return;
    
    setIsDeleting(true);
    try {
      await remove(documentToDelete.uuid);
    } finally {
      setIsAlertDialogOpen(false);
      setDocumentToDelete(null);
      setIsDeleting(false);
    }
  };

  const handleUpload = async (file: File, typeId: string) => {
    setIsUploading(true);
    try {
      await upload(file, typeId);
    } finally {
      setIsUploading(false);
    }
  };

  if (error) {
    return (
      <div className="rounded-md bg-destructive/10 p-6 text-center">
        <FileTextIcon className="mx-auto h-10 w-10 text-destructive" />
        <h3 className="mt-2 text-lg font-medium">Error al cargar documentos</h3>
        <p className="mt-1 text-sm text-muted-foreground">
          {error.message || 'Ha ocurrido un error. Intente nuevamente.'}
        </p>
        <Button 
          variant="outline" 
          className="mt-4" 
          onClick={() => list()} 
          disabled={loading}
        >
          Reintentar
        </Button>
      </div>
    );
  }

  return (
    <div className="border rounded-lg overflow-hidden mx-auto w-full relative">
      {/* Encabezado mejorado con acciones */}
      <div className="bg-muted/10 px-6 py-4 border-b flex justify-between items-center">
        <h2 className="text-xl font-semibold">{getColumnLabel('documents', 'section_title')}</h2>
        <div>
          {(canUpload || (window.location.pathname.endsWith('/documents') && hasDocumentPermission('upload'))) && (
            <Button
              onClick={() => setIsUploadModalOpen(true)}
              variant="default"
              size="sm"
              className="flex items-center hover:bg-primary/90 transition-colors"
              disabled={isUploading}
            >
              <UploadIcon className="mr-2 h-4 w-4" /> Subir documento
            </Button>
          )}
        </div>
      </div>

      {/* Contenido principal con estado de carga, tabla o mensaje vacío */}
      {loading && documents.length === 0 ? (
        <div className="flex justify-center items-center py-16">
          <div className="text-center">
            <svg
              className="mx-auto h-10 w-10 animate-spin text-muted-foreground"
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
            >
              <circle
                className="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                strokeWidth="4"
              />
              <path
                className="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              />
            </svg>
            <p className="mt-3 text-sm text-muted-foreground">Cargando documentos...</p>
          </div>
        </div>
      ) : documents.length === 0 ? (
        <div className="flex justify-center items-center py-12 px-4">
          <div className="p-8 text-center max-w-md w-full">
            <div className="mx-auto rounded-full p-5 w-24 h-24 bg-muted/10 flex items-center justify-center">
              <FileIcon className="h-12 w-12 text-muted-foreground" />
            </div>
            <h3 className="mt-5 text-lg font-medium">{getColumnLabel('documents', 'no_documents')}</h3>
            <p className="mt-2 text-sm text-muted-foreground">
              {window.location.pathname.includes('/users') ? 
                "Este usuario aún no tiene documentos asociados." : 
                "No hay documentos disponibles en este momento."}
            </p>
            {(canUpload || (window.location.pathname.endsWith('/documents') && hasDocumentPermission('upload'))) && (
              <Button 
                onClick={() => setIsUploadModalOpen(true)}
                variant="outline" 
                className="mt-5 border-primary/30 bg-primary/5 text-primary hover:bg-primary/10"
                size="lg"
              >
                Subir primer documento
              </Button>
            )}
          </div>
        </div>
      ) : (
        <div>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead className="w-[30%]">{getColumnLabel('documents', 'name')}</TableHead>
                <TableHead className="w-[20%]">{getColumnLabel('documents', 'type')}</TableHead>
                <TableHead className="w-[15%]">{getColumnLabel('documents', 'file_size')}</TableHead>
                <TableHead className="w-[20%]">{getColumnLabel('documents', 'created_at')}</TableHead>
                <TableHead className="text-right w-[15%] sticky right-0 bg-background">Acciones</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {documents.map((document) => (
                <TableRow key={document.uuid}>
                  <TableCell className="font-medium max-w-[250px]">
                    <div className="truncate" title={document.original_filename}>
                      {document.original_filename}
                    </div>
                  </TableCell>
                  <TableCell>{document.type.label}</TableCell>
                  <TableCell>{`${Math.round(document.file_size / 1024)} KB`}</TableCell>
                  <TableCell>{new Date(document.created_at).toLocaleDateString()}</TableCell>
                  <TableCell className="text-right sticky right-0 bg-background">
                    <div className="flex justify-end gap-2">
                      <Button
                        size="icon"
                        variant="ghost"
                        onClick={() => handlePreview(document)}
                        title="Vista previa"
                      >
                        <EyeIcon className="h-4 w-4" />
                      </Button>
                      {canDownload && (
                        <a
                          href={previewUrl(document.uuid)}
                          target="_blank"
                          rel="noopener noreferrer"
                        >
                          <Button
                            size="icon"
                            variant="ghost"
                            title="Descargar"
                          >
                            <DownloadIcon className="h-4 w-4" />
                          </Button>
                        </a>
                      )}
                      {/* Solo mostrar el botón de eliminar si no estamos en modo solo lectura */}
                      {canDelete && !readOnly && (
                        <Button
                          size="icon"
                          variant="ghost"
                          className="text-destructive hover:bg-destructive/10"
                          onClick={() => handleDelete(document)}
                          title="Eliminar"
                        >
                          <TrashIcon className="h-4 w-4" />
                        </Button>
                      )}
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </div>
      )}

      <DocumentPreviewModal
        document={selectedDocument}
        isOpen={isPreviewModalOpen}
        onOpenChange={setIsPreviewModalOpen}
      />

      <DocumentUploadModal
        types={types}
        isOpen={isUploadModalOpen}
        onOpenChange={setIsUploadModalOpen}
        onUpload={handleUpload}
        isUploading={isUploading}
      />

      <AlertDialog open={isAlertDialogOpen} onOpenChange={setIsAlertDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>
              {getColumnLabel('documents', 'confirm_delete')}
            </AlertDialogTitle>
            <AlertDialogDescription>
              Esta acción no se puede deshacer. El documento se eliminará permanentemente.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel disabled={isDeleting}>Cancelar</AlertDialogCancel>
            <AlertDialogAction
              onClick={handleConfirmDelete}
              disabled={isDeleting}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              {isDeleting ? 'Eliminando...' : 'Eliminar'}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
