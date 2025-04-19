import React, { useState, useEffect } from 'react';
import { getColumnLabel } from '@/utils/translations/column-labels';
import { Button } from '@/components/ui/button';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from '@/components/ui/alert-dialog';
import { ImageType } from '@/types';
import { ImageIcon, EyeIcon, TrashIcon, Loader2 } from 'lucide-react';
import useImages from '@/hooks/useImages';
import ImageUploadModal from './ImageUploadModal';
import ImagePreviewModal from './ImagePreviewModal';

interface ImagesSectionProps {
  module: string;
  entityId: number;
  types: ImageType[];
  permissions: string[];
  readOnly?: boolean;
}

export default function ImagesSection({ 
  module, 
  entityId, 
  types, 
  permissions,
  readOnly = false 
}: ImagesSectionProps) {
  console.log('[ImagesSection] 🔄 COMPONENTE RENDERIZADO', {
    module,
    entityId,
    typesCount: types?.length,
    permissionsCount: permissions?.length
  });
  const {
    images,
    loading,
    uploading,
    removing,
    upload,
    remove,
    previewUrl,
    thumbnailUrl,
    list
  } = useImages({ module, entityId });

  const [uploadModalOpen, setUploadModalOpen] = useState(false);
  const [previewModalOpen, setPreviewModalOpen] = useState(false);
  const [selectedImage, setSelectedImage] = useState<string | null>(null);
  const [imageToDelete, setImageToDelete] = useState<string | null>(null);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  
  // Control para evitar cargas repetitivas
  const [initialLoadDone, setInitialLoadDone] = useState<boolean>(false);
  
  // Cargar imágenes cuando el componente se monta, si no hay imágenes iniciales
  useEffect(() => {
    console.log('[ImagesSection] 🔄 useEffect para carga inicial', {
      imagesLength: images.length,
      loading,
      listFunction: !!list,
      initialLoadDone
    });
    
    // Solo cargar la primera vez y si no está cargando
    if (images.length === 0 && !loading && !initialLoadDone) {
      console.log('[ImagesSection] 🔍 No hay imágenes iniciales, cargando lista...');
      
      // Marcar que ya hemos iniciado la carga inicial
      setInitialLoadDone(true);
      
      list().then(() => {
        console.log('[ImagesSection] ✅ Lista cargada exitosamente');
      }).catch(err => {
        console.error('[ImagesSection] ❌ Error al cargar lista:', err);
      });
    }
  }, [images.length, loading, list, initialLoadDone]);

  // Agregar logs para depuración de URLs
  useEffect(() => {
    console.log(`[ImagesSection] 📸 Imágenes actualizadas: ${images.length}`);
    
    images.forEach(image => {
      console.log('[ImagesSection] 📸 Imagen:', image.name, {
        uuid: image.uuid,
        url: image.url,
        thumbnail_url: image.thumbnail_url,
        path: image.path
      });
    });
  }, [images]);

  const handleUpload = (files: FileList, typeCode: string) => {
    upload(files, typeCode);
    setUploadModalOpen(false);
  };

  const handlePreview = (uuid: string) => {
    setSelectedImage(uuid);
    setPreviewModalOpen(true);
  };

  const handleDeleteConfirm = (uuid: string) => {
    setImageToDelete(uuid);
    setDeleteDialogOpen(true);
  };

  const handleDeleteCancel = () => {
    setImageToDelete(null);
    setDeleteDialogOpen(false);
  };

  const handleDeleteConfirmed = () => {
    if (imageToDelete) {
      remove(imageToDelete);
      setImageToDelete(null);
      setDeleteDialogOpen(false);
    }
  };

  const canUpload = permissions.includes(`images.upload.${module}`) && !readOnly;
  const canDelete = permissions.includes(`images.delete.${module}`) && !readOnly;

  const selectedImageObject = images.find(img => img.uuid === selectedImage) || null;

  return (
    <div className="space-y-6">
      <div className="flex justify-between">
        <h2 className="text-2xl font-bold tracking-tight">
          {getColumnLabel('images', 'section_title')}
        </h2>
        <div className="flex items-center space-x-2">
          {canUpload && (
            <Button 
              onClick={() => setUploadModalOpen(true)}
              disabled={uploading}
            >
              {uploading ? (
                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
              ) : (
                <ImageIcon className="mr-2 h-4 w-4" />
              )}
              {getColumnLabel('images', 'upload_image')}
            </Button>
          )}
        </div>
      </div>

      {loading ? (
        <div className="flex h-40 items-center justify-center">
          <Loader2 className="h-8 w-8 animate-spin text-muted-foreground" />
        </div>
      ) : images.length === 0 ? (
        <div className="flex h-40 flex-col items-center justify-center rounded-md border border-dashed p-8 text-center text-muted-foreground">
          <ImageIcon className="mb-4 h-10 w-10" />
          <p>{getColumnLabel('images', 'no_images')}</p>
          {canUpload && (
            <Button 
              variant="link" 
              onClick={() => setUploadModalOpen(true)}
              className="mt-2"
            >
              {getColumnLabel('images', 'upload_image')}
            </Button>
          )}
        </div>
      ) : (
        <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
          {images.map((image) => (
            <div 
              key={image.uuid} 
              className="group relative overflow-hidden rounded-md border bg-background"
            >
              <div className="relative aspect-square overflow-hidden">
                {/* Los logs están en useEffect para evitar problemas de TypeScript */}
                <img 
                  src={thumbnailUrl(image.uuid)} 
                  alt={image.name}
                  className="h-full w-full object-cover transition-all group-hover:scale-105"
                  onError={(e) => {
                    console.error('[ImagesSection] Error loading image:', image.name, e);
                    // Provide fallback to original URL on error
                    const fallback = image.url || '/placeholder.png';
                    e.currentTarget.src = fallback;
                  }}
                />
                <div className="absolute inset-0 bg-black/0 transition-all group-hover:bg-black/40"></div>
                <div className="absolute inset-0 flex items-center justify-center gap-2 opacity-0 transition-all group-hover:opacity-100">
                  <Button 
                    size="icon" 
                    variant="secondary" 
                    onClick={() => handlePreview(image.uuid)}
                  >
                    <EyeIcon className="h-4 w-4" />
                  </Button>
                  {canDelete && (
                    <Button 
                      size="icon" 
                      variant="destructive" 
                      onClick={() => handleDeleteConfirm(image.uuid)}
                      disabled={removing}
                    >
                      {removing && imageToDelete === image.uuid ? (
                        <Loader2 className="h-4 w-4 animate-spin" />
                      ) : (
                        <TrashIcon className="h-4 w-4" />
                      )}
                    </Button>
                  )}
                </div>
              </div>
              <div className="p-2">
                <p className="truncate text-sm font-medium">{image.name}</p>
                <p className="text-xs text-muted-foreground">
                  {image.imageType?.label || 'Sin tipo'}
                </p>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Modales */}
      <ImageUploadModal
        open={uploadModalOpen}
        onClose={() => setUploadModalOpen(false)}
        onUpload={handleUpload}
        types={types}
        uploading={uploading}
        module={module}
      />

      <ImagePreviewModal
        open={previewModalOpen}
        onClose={() => {
          setPreviewModalOpen(false);
          setSelectedImage(null);
        }}
        image={selectedImageObject}
        previewUrl={selectedImage ? previewUrl(selectedImage) : ''}
      />

      <AlertDialog open={deleteDialogOpen} onOpenChange={setDeleteDialogOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>¿Está seguro de eliminar esta imagen?</AlertDialogTitle>
            <AlertDialogDescription>
              Esta acción no se puede deshacer. La imagen será eliminada permanentemente.
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel onClick={handleDeleteCancel}>Cancelar</AlertDialogCancel>
            <AlertDialogAction 
              onClick={handleDeleteConfirmed}
              className="bg-destructive text-destructive-foreground hover:bg-destructive/90"
            >
              Eliminar
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </div>
  );
}
