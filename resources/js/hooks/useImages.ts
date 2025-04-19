import { useState, useCallback, useEffect } from 'react';
import { router, usePage } from '@inertiajs/react';
import { toast } from 'sonner';

// Definimos las interfaces aquí mismo para evitar dependencias circulares
interface Image {
  id: number;
  uuid: string;
  name: string;
  path: string;
  mime_type: string;
  size: number;
  width?: number;
  height?: number;
  created_at: string;
  updated_at: string;
  created_by?: number;
  url: string;
  thumbnail_url: string;
  imageType?: ImageType;
}

interface ImageType {
  id: number;
  code: string;
  label: string;
  module: string;
}

type UseImagesProps = {
  module: string;
  entityId: number;
};

interface UseImagesReturn {
  images: Image[];
  loading: boolean;
  uploading: boolean;
  removing: boolean;
  error: string | null;
  list: () => Promise<void>;
  upload: (files: FileList, typeCode: string) => void;
  remove: (uuid: string) => void;
  previewUrl: (uuid: string) => string | null;
  thumbnailUrl: (uuid: string) => string | null;
}

export default function useImages({ module, entityId }: UseImagesProps): UseImagesReturn {
  console.log(`[useImages] 🔄 HOOK INICIALIZADO - module: ${module}, entityId: ${entityId}`);
  
  // Acceder a las props de la página actual de Inertia
  const { props } = usePage();
  
  // Inicializar el estado con los datos de Inertia si están disponibles
  const initialImages = (props.images as Image[] | undefined) || [];
  console.log(`[useImages] 💾 Imágenes iniciales desde props: ${initialImages.length}`, initialImages);
  
  const [images, setImages] = useState<Image[]>(initialImages);
  // Inicializamos loading como false para permitir la primera carga aunque no haya imágenes
  const [loading, setLoading] = useState<boolean>(false);
  const [uploading, setUploading] = useState<boolean>(false);
  const [removing, setRemoving] = useState<boolean>(false);
  const [error, setError] = useState<string | null>(null);
  
  // Inicializar desde props cuando cambian
  useEffect(() => {
    if (props.images) {
      console.log(`[useImages] 💾 Actualizando imágenes desde props de Inertia: ${(props.images as Image[]).length}`);
      setImages(props.images as Image[]);
      setLoading(false);
    }
  }, [props.images]);
  
  // Cargar imágenes inmediatamente si estamos en un tab
  useEffect(() => {
    // Si no están cargadas, pero estamos en una página que no es de imágenes
    if (initialImages.length === 0 && !props.imageTypes) {
      console.log('[useImages] 🔍 Probable contexto de tab, preparando para cargar imágenes...');
      // Nota: No llamamos a list() aquí para evitar doble carga, lo hará el componente ImagesSection
    }
    
    // IMPORTANTE: Permitir que el componente cargue la primera vez aunque initialImages esté vacío
    // Esto es para asegurar que no se bloquee la carga inicial en el contexto de un tab
  }, [initialImages.length, props.imageTypes]);

  // Cargar la lista de imágenes
  const list = useCallback(async () => {
    // No cargar si ya estamos cargando
    if (loading) {
      console.log('[useImages] ❌ list() abortada: ya está cargando');
      return;
    }
    
    console.log(`[useImages] 🔍 list() INICIO - module: ${module}, entityId: ${entityId}`);
    setLoading(true);
    setError(null);

    try {
      // Utilizamos fetch directamente con encabezados adecuados para JSON
      const url = `/${module}/${entityId}/images`;
      console.log(`[useImages] Fetch URL: ${url}`);
      
      const response = await fetch(url, {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      });
      
      console.log(`[useImages] Respuesta fetch: status ${response.status}`);
      
      if (!response.ok) {
        throw new Error(`Error ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      console.log('[useImages] Datos recibidos:', data);
      
      // Verificamos si la respuesta contiene imágenes
      if (data && data.images) {
        console.log(`[useImages] Imágenes encontradas: ${data.images.length}`);
        setImages(data.images);
      } else {
        console.log('[useImages] No se encontraron imágenes en la respuesta. Datos completos:', data);
        // Si no hay imágenes, establecemos un array vacío
        setImages([]);
      }
    } catch (err) {
      console.error('[useImages] Error al cargar imágenes:', err);
      setError(err instanceof Error ? err.message : 'Error desconocido');
      toast.error('No se pudieron cargar las imágenes');
      // Si hay un error, nos aseguramos de que images es un array vacío
      setImages([]);
    } finally {
      setLoading(false);
      console.log('[useImages] list() FIN');
    }
  }, [module, entityId, loading]);

  // Subir nueva(s) imagen(es)
  const upload = useCallback(
    (files: FileList, typeCode: string) => {
      if (files.length === 0) return;

      setUploading(true);
      setError(null);

      const formData = new FormData();
      formData.append('module', module);
      formData.append('entity_id', entityId.toString());
      formData.append('type_code', typeCode);
      formData.append('file', files[0]);

      router.post(`/${module}/${entityId}/images`, formData, {
        onSuccess: async () => {
          console.log('[useImages] Upload onSuccess START');
          await list(); // Refrescar la lista después de subir
          setUploading(false);
          // El toast de éxito viene desde el backend, no duplicamos aquí
          console.log('[useImages] Upload onSuccess END');
        },
        onError: (errors) => {
          setError(errors.file || 'Error al subir la imagen');
          setUploading(false);
          toast.error(errors.file || 'Error al subir la imagen');
        },
        forceFormData: true,
      });
    },
    [module, entityId, list]
  );

  // Eliminar una imagen
  const remove = useCallback(
    (uuid: string) => {
      setRemoving(true);
      setError(null);

      router.delete(`/${module}/${entityId}/images/${uuid}`, {
        onSuccess: async () => {
          console.log('[useImages] Remove onSuccess START');
          // Refrescar la lista en lugar de manipular el estado directamente
          await list();
          setRemoving(false);
          // El toast de éxito viene desde el backend, no duplicamos aquí
          console.log('[useImages] Remove onSuccess END');
        },
        onError: () => {
          setError('Error al eliminar la imagen');
          setRemoving(false);
          toast.error('Error al eliminar la imagen');
        },
      });
    },
    [module, entityId, list]
  );

  // Obtener URL de previsualización
  const previewUrl = useCallback(
    (uuid: string) => {
      const image = images.find((img) => img.uuid === uuid);
      // Retornar null en lugar de cadena vacía para evitar advertencias del navegador
      return image?.url || null;
    },
    [images]
  );

  // Obtener URL de miniatura
  const thumbnailUrl = useCallback(
    (uuid: string) => {
      const image = images.find((img) => img.uuid === uuid);
      // Retornar null en lugar de cadena vacía para evitar advertencias del navegador
      return image?.thumbnail_url || null;
    },
    [images]
  );

  // La carga inicial ya no es necesaria, ahora usamos los datos de props
  // Se mantiene el método list() disponible para actualizaciones bajo demanda

  return {
    images,
    loading,
    uploading,
    removing,
    error,
    list,
    upload,
    remove,
    previewUrl,
    thumbnailUrl,
  };
}
