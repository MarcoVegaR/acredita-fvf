// Tipos básicos para entidades relacionadas
export interface Event {
  id: number;
  name: string;
  date?: string;
  venue?: string;
  description?: string;
}

export interface Area {
  id: number;
  name: string;
}

export interface Provider {
  id: number;
  name: string;
  area_id: number;
  area?: Area;
}

export interface User {
  id: number;
  name: string;
  email: string;
}

export interface PrintBatch {
  id: number;
  uuid: string;
  event_id: number;
  area_id?: number | null;
  provider_id?: number | null;
  generated_by: number;
  status: 'queued' | 'processing' | 'ready' | 'failed' | 'archived';
  filters_snapshot: FilterSnapshot;
  total_credentials: number;
  processed_credentials: number;
  pdf_path?: string | null;
  started_at?: string | null;
  finished_at?: string | null;
  error_message?: string | null;
  retry_count: number;
  created_at: string;
  updated_at: string;
  
  // Relaciones
  event?: Event;
  area?: Area;
  provider?: Provider;
  generated_by_user?: User;
  
  // Accessors
  is_ready: boolean;
  is_processing: boolean;
  progress_percentage: number;
  duration?: number | null;
  formatted_duration?: string | null;
  status_badge: {
    label: string;
    color: 'blue' | 'yellow' | 'green' | 'red' | 'gray';
  };
  
  // Index signature for Entity compatibility
  [key: string]: unknown;
}

export interface FilterSnapshot {
  event_id: number;
  area_id?: number | number[] | null;
  provider_id?: number | number[] | null;
  only_unprinted: boolean;
}

// Para la tabla (puede diferir ligeramente del modelo completo)
export interface TablePrintBatch {
  id: number;
  uuid: string;
  event: {
    id: number;
    name: string;
  };
  area?: {
    id: number;
    name: string;
  } | null;
  provider?: {
    id: number;
    name: string;
  } | null;
  generated_by_user: {
    id: number;
    name: string;
  };
  status: PrintBatch['status'];
  total_credentials: number;
  processed_credentials: number;
  progress_percentage: number;
  created_at: string;
  finished_at?: string | null;
  status_badge: PrintBatch['status_badge'];
  is_ready: boolean;
  is_processing: boolean;
  can_download: boolean;
  can_retry: boolean;
  pdf_path: string | null;
  // Additional properties for Entity compatibility (using Record for compatibility)
  [key: string]: unknown;
}

// Datos para filtros en el frontend
export interface FilterData {
  events: Array<{
    id: number;
    name: string;
  }>;
  areas: Array<{
    id: number;
    name: string;
  }>;
  providers: Array<{
    id: number;
    name: string;
    area_id: number;
    area?: {
      id: number;
      name: string;
    };
  }>;
  statuses: Array<{
    value: PrintBatch['status'];
    label: string;
  }>;
}

// Estadísticas del dashboard
export interface PrintBatchStats {
  total: number;
  ready: number;
  processing: number;
  failed: number;
}

// Para el formulario de creación
export interface CreateBatchFormData {
  event_id: number;
  area_id?: number[];
  provider_id?: number[];
  only_unprinted: boolean;
}

// Preview de credenciales antes de crear lote
export interface BatchPreview {
  credentials_count: number;
  estimated_pages: number;
  can_create: boolean;
}

// Respuesta de lotes en procesamiento (para polling)
export interface ProcessingBatch {
  id: number;
  uuid: string;
  status: 'queued' | 'processing';
  progress_percentage: number;
  total_credentials: number;
  processed_credentials: number;
  event_name: string;
}
