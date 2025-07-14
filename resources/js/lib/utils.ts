import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

/**
 * Formatea una fecha y hora en un formato legible
 * @param date - Fecha en formato string o Date
 * @param options - Opciones de formato
 * @returns Fecha formateada
 */
export function formatDateTime(date: string | Date | null | undefined, options: Intl.DateTimeFormatOptions = {}): string {
    if (!date) return '-';
    
    const defaultOptions: Intl.DateTimeFormatOptions = {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        ...options
    };
    
    return new Date(date).toLocaleDateString('es-ES', defaultOptions);
}

/**
 * Formatea una fecha en un formato legible sin incluir la hora
 * @param date - Fecha en formato string o Date
 * @returns Fecha formateada
 */
export function formatDate(date: string | Date | null | undefined): string {
    if (!date) return '-';
    
    return formatDateTime(date, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: undefined,
        minute: undefined
    });
}
