import { useState } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { Document } from '@/types';

export function useDocuments(module: string, entityId: number) {
  const [documents, setDocuments] = useState<Document[]>([]);
  const [loading, setLoading] = useState<boolean>(false);
  const [error, setError] = useState<Error | null>(null);

  /**
   * Load documents for the specified entity
   */
  const list = async () => {
    setLoading(true);
    setError(null);
    
    try {
      // Utilizamos fetch con encabezados adecuados para indicar que queremos JSON
      const response = await fetch(`/${module}/${entityId}/documents`, {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      });
      
      if (!response.ok) {
        throw new Error(`Error ${response.status}: ${response.statusText}`);
      }
      
      const data = await response.json();
      
      // Verificamos si la respuesta contiene documentos
      if (data && data.documents) {
        setDocuments(data.documents);
      } else {
        // Si no hay documentos, establecemos un array vacío
        setDocuments([]);
      }
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Unknown error occurred'));
      toast.error('Error al cargar documentos');
      // Si hay un error, asegurarse de que documents es un array vacío
      setDocuments([]);
    } finally {
      setLoading(false);
    }
  };

  /**
   * Upload a document for the entity
   */
  const upload = async (file: File, typeCode: string) => {
    setLoading(true);
    setError(null);
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('document_type_id', typeCode);
    
    try {
      router.post(`/${module}/${entityId}/documents`, formData, {
        onSuccess: async () => {
          console.log('[useDocuments] Upload onSuccess START');
          await list(); // Refresh the list after upload
          console.log('[useDocuments] Upload onSuccess - list() finished');
        },
        onError: (errors) => {
          setError(new Error(errors.file || 'Error uploading document'));
          toast.error(errors.file || 'Error al subir documento');
        },
        preserveState: true,
      });
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Unknown error occurred'));
      toast.error('Error al subir documento');
      setLoading(false);
    }
  };

  /**
   * Remove a document by UUID
   */
  const remove = async (uuid: string) => {
    setLoading(true);
    setError(null);
    
    try {
      router.delete(`/documents/${uuid}`, {
        // Pasar el UUID también como 'data' para satisfacer la validación del backend
        data: { document_uuid: uuid }, 
        onSuccess: async () => {
          console.log('[useDocuments] Remove onSuccess START');
          await list(); // Refresh the list after deletion
          console.log('[useDocuments] Remove onSuccess - list() finished');
          setLoading(false); // Finalizar carga en éxito
        },
        onError: (errors) => {
          // Intentar obtener un mensaje de error más específico
          const errorMessage = errors.message || errors.document_uuid || 'Error al eliminar el documento.';
          setError(new Error(errorMessage));
          toast.error(errorMessage); // Mostrar el error específico
          console.error("Error details from backend:", errors); // Mantener para depuración
          setLoading(false); // Finalizar carga en error
        },
        preserveState: true,
      });
    } catch (err) {
      // Este bloque catch es menos probable para errores de Inertia
      const errorMsg = err instanceof Error ? err.message : 'Unknown error occurred';
      setError(new Error(errorMsg));
      toast.error(`Error inesperado: ${errorMsg}`);
      setLoading(false); // Finalizar carga en catch
    }
    // No necesitamos un finally si setLoading(false) está en onSuccess/onError/catch
  };

  /**
   * Get the URL for previewing a document
   */
  const previewUrl = (uuid: string) => {
    return `/documents/${uuid}/download`; // Using download endpoint for preview
  };

  return {
    documents,
    loading,
    error,
    list,
    upload,
    remove,
    previewUrl
  };
}
