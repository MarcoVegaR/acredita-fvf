import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    flash?: {
        success?: string;
        message?: string;
        error?: string;
        [key: string]: unknown;
    };
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    roles?: string[];
    permissions?: string[];
    [key: string]: unknown; // This allows for additional properties...
}

export interface Document {
    id: number;
    uuid: string;
    filename: string;
    original_filename: string;
    file_size: number;
    mime_type: string;
    path: string;
    is_validated: boolean;
    created_at: string;
    updated_at: string;
    user: {
        id: number;
        name: string;
    };
    type: {
        id: number;
        code: string;
        label: string;
    };
}

export interface DocumentType {
    id: number;
    code: string;
    label: string;
    module: string | null;
}
