/**
 * Formatea una fecha en formato ISO a formato local
 * @param date Fecha en formato ISO o string de fecha
 * @param options Opciones de formato (por defecto: día, mes, año, hora y minutos)
 * @returns Fecha formateada
 */
export function formatDateToLocale(
  date: string | Date | null | undefined,
  options: Intl.DateTimeFormatOptions = {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }
): string {
  if (!date) {
    return '';
  }

  try {
    const dateObj = typeof date === 'string' ? new Date(date) : date;
    return dateObj.toLocaleDateString('es-ES', options);
  } catch (error) {
    console.error('Error al formatear fecha:', error);
    return String(date);
  }
}

/**
 * Formatea una fecha en formato ISO a formato relativo (hace X tiempo)
 * @param date Fecha en formato ISO o string de fecha
 * @returns Texto relativo (ej: "hace 5 minutos", "hace 2 días")
 */
export function formatDateRelative(date: string | Date | null | undefined): string {
  if (!date) {
    return '';
  }

  const dateObj = typeof date === 'string' ? new Date(date) : date;
  const now = new Date();
  const diffInSeconds = Math.floor((now.getTime() - dateObj.getTime()) / 1000);

  // Menos de 1 minuto
  if (diffInSeconds < 60) {
    return 'hace unos segundos';
  }

  // Menos de 1 hora
  if (diffInSeconds < 3600) {
    const minutes = Math.floor(diffInSeconds / 60);
    return `hace ${minutes} ${minutes === 1 ? 'minuto' : 'minutos'}`;
  }

  // Menos de 1 día
  if (diffInSeconds < 86400) {
    const hours = Math.floor(diffInSeconds / 3600);
    return `hace ${hours} ${hours === 1 ? 'hora' : 'horas'}`;
  }

  // Menos de 30 días
  if (diffInSeconds < 2592000) {
    const days = Math.floor(diffInSeconds / 86400);
    return `hace ${days} ${days === 1 ? 'día' : 'días'}`;
  }

  // Más de 30 días, mostrar fecha completa
  return formatDateToLocale(dateObj);
}
