// Definición de tipos para la aplicación

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at?: string;
  created_at: string;
  updated_at: string;
  roles?: string[];
}

export interface Role {
  id: number;
  name: string;
  nameshow?: string;
  permissions?: Permission[];
  created_at?: string;
  updated_at?: string;
}

export interface Permission {
  id: number;
  name: string;
  nameshow?: string;
  guard_name?: string;
}

export interface Document {
  id: number;
  uuid: string;
  name: string;
  original_filename: string;
  mime_type: string;
  file_size: number;
  created_at: string;
  updated_at: string;
  user: User;
  document_type: DocumentType;
}

export interface DocumentType {
  id: number;
  code: string;
  label: string;
  module?: string;
}

export interface Image {
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

export interface ImageType {
  id: number;
  code: string;
  label: string;
  module: string;
}

export interface PaginatedData<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

// Tipos para configuraciones de tablas
export interface ColumnConfig {
  key: string;
  label: string;
  sortable?: boolean;
  searchable?: boolean;
  render?: (value: any, row: any) => React.ReactNode;
}

export interface TableAction {
  label: string;
  icon?: React.ReactNode;
  action: (row: any) => void;
  enabled?: boolean | ((row: any) => boolean);
}

export interface RowActionConfig {
  edit?: TableAction;
  delete?: TableAction;
  view?: TableAction;
  custom?: TableAction[];
}

// Tipos para formularios
export interface FormFieldProps {
  name: string;
  label: string;
  required?: boolean;
  placeholder?: string;
  description?: string;
}
