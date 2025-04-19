// Definición de tipos para la aplicación
import { ComponentType } from 'react';
import { LucideProps } from 'lucide-react';

export interface User {
  id: number;
  name: string;
  email: string;
  email_verified_at?: string;
  created_at: string;
  updated_at: string;
  roles?: string[];
  avatar?: string;     // Campo necesario para user-info.tsx
  active?: boolean;    // Campo necesario para StatusRenderer
  [key: string]: string | number | boolean | string[] | null | undefined;  // Índice de firma para compatibilidad con Entity
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
  type: DocumentType;    // Alias para document_type para compatibilidad
  [key: string]: string | number | boolean | DocumentType | User | null | undefined | string[];    // Índice de firma para compatibilidad con Entity
}

export interface DocumentType {
  id: number;
  code: string;
  label: string;
  module?: string | null; // Permitir null para compatibilidad con backend
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

// Interfaces de navegación y UI necesarias para otros componentes
export interface BreadcrumbItem {
  title: string;
  href: string;
  active?: boolean;
}

export interface NavItem {
  title: string;
  href?: string;
  icon?: ComponentType<LucideProps> | React.ReactNode;
  disabled?: boolean;
  external?: boolean;
  label?: string;
  description?: string;
  items?: NavItem[];
  permission?: string;
}

export interface SharedData {
  auth: {
    user: User;
    permissions: string[];
  };
  flash?: {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
  };
  errors?: Record<string, string[]>;
  // Esto permite contener cualquier propiedad adicional que el backend pueda enviar
  [key: string]: unknown;
}

// Interfaz Entity genérica para BaseShowPage y BaseIndexPage
export interface Entity {
  id?: number;
  // Índice de firma genérico para permitir acceder a cualquier propiedad de entidades
  [key: string]: unknown;
}

// Tipos para configuraciones de tablas
export interface ColumnConfig {
  key: string;
  label: string;
  sortable?: boolean;
  searchable?: boolean;
  render?: (value: unknown, row: Record<string, unknown>) => React.ReactNode;
}

export interface TableAction {
  label: string;
  icon?: React.ReactNode;
  action: (row: Record<string, unknown>) => void;
  enabled?: boolean | ((row: Record<string, unknown>) => boolean);
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
