import React from 'react';
import { format } from 'date-fns';
import { es } from 'date-fns/locale';
import { 
  UserPlus, 
  Send, 
  Eye, 
  CheckCircle, 
  XCircle, 
  RotateCcw, 
  Pause,
  Clock,
  User
} from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

interface TimelineEvent {
  type: string;
  timestamp: string;
  user: {
    id: number;
    name: string;
  } | null;
  message: string;
  details: string | null;
  icon: string;
  color: string;
}

interface TimelineTabProps {
  timeline: TimelineEvent[];
}

const getIcon = (iconName: string) => {
  const icons: Record<string, React.ComponentType<{ className?: string }>> = {
    UserPlus,
    Send,
    Eye,
    CheckCircle,
    XCircle,
    RotateCcw,
    Pause
  };
  
  return icons[iconName] || Clock;
};

const getColorClasses = (color: string) => {
  const colors: Record<string, { bg: string; border: string; text: string; badge: string }> = {
    blue: {
      bg: 'bg-blue-50',
      border: 'border-blue-200',
      text: 'text-blue-700',
      badge: 'bg-blue-100 text-blue-800'
    },
    indigo: {
      bg: 'bg-indigo-50',
      border: 'border-indigo-200',
      text: 'text-indigo-700',
      badge: 'bg-indigo-100 text-indigo-800'
    },
    yellow: {
      bg: 'bg-yellow-50',
      border: 'border-yellow-200',
      text: 'text-yellow-700',
      badge: 'bg-yellow-100 text-yellow-800'
    },
    green: {
      bg: 'bg-green-50',
      border: 'border-green-200',
      text: 'text-green-700',
      badge: 'bg-green-100 text-green-800'
    },
    red: {
      bg: 'bg-red-50',
      border: 'border-red-200',
      text: 'text-red-700',
      badge: 'bg-red-100 text-red-800'
    },
    orange: {
      bg: 'bg-orange-50',
      border: 'border-orange-200',
      text: 'text-orange-700',
      badge: 'bg-orange-100 text-orange-800'
    },
    gray: {
      bg: 'bg-gray-50',
      border: 'border-gray-200',
      text: 'text-gray-700',
      badge: 'bg-gray-100 text-gray-800'
    }
  };
  
  return colors[color] || colors.gray;
};

export function TimelineTab({ timeline }: TimelineTabProps) {
  if (!timeline || timeline.length === 0) {
    return (
      <div className="flex flex-col items-center justify-center py-12 text-center">
        <Clock className="h-12 w-12 text-muted-foreground mb-4" />
        <h3 className="text-lg font-medium text-muted-foreground mb-2">
          Sin historial disponible
        </h3>
        <p className="text-sm text-muted-foreground">
          No hay eventos registrados para esta solicitud.
        </p>
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center gap-2 mb-6">
        <Clock className="h-5 w-5 text-muted-foreground" />
        <h3 className="text-lg font-medium">Historial de la solicitud</h3>
        <Badge variant="outline" className="ml-auto">
          {timeline.length} evento{timeline.length !== 1 ? 's' : ''}
        </Badge>
      </div>

      <div className="relative">
        {/* Línea vertical del timeline */}
        <div className="absolute left-6 top-6 bottom-0 w-0.5 bg-border"></div>
        
        <div className="space-y-6">
          {timeline.map((event, index) => {
            const IconComponent = getIcon(event.icon);
            const colors = getColorClasses(event.color);
            
            return (
              <div key={index} className="relative flex gap-4">
                {/* Icono del evento */}
                <div className={`relative flex-shrink-0 w-12 h-12 rounded-full ${colors.bg} ${colors.border} border-2 flex items-center justify-center z-10`}>
                  <IconComponent className={`h-5 w-5 ${colors.text}`} />
                </div>
                
                {/* Contenido del evento */}
                <div className="flex-1 min-w-0">
                  <Card className={`${colors.bg} ${colors.border} border`}>
                    <CardContent className="p-4">
                      {/* Header del evento */}
                      <div className="flex items-start justify-between mb-2">
                        <div className="flex-1">
                          <h4 className={`font-medium ${colors.text} mb-1`}>
                            {event.message}
                          </h4>
                          <div className="flex items-center gap-2 text-sm text-muted-foreground">
                            <Clock className="h-3 w-3" />
                            <span>
                              {format(new Date(event.timestamp), "d 'de' MMMM 'de' yyyy 'a las' HH:mm", { locale: es })}
                            </span>
                          </div>
                        </div>
                        <Badge className={colors.badge}>
                          {event.type}
                        </Badge>
                      </div>
                      
                      {/* Usuario que realizó la acción */}
                      {event.user && (
                        <div className="flex items-center gap-2 mb-3 text-sm">
                          <User className="h-4 w-4 text-muted-foreground" />
                          <span className="font-medium">{event.user.name}</span>
                        </div>
                      )}
                      
                      {/* Detalles adicionales */}
                      {event.details && (
                        <div className="mt-3 p-3 bg-background/50 rounded-md border">
                          <p className="text-sm leading-relaxed">
                            <strong>Comentarios:</strong><br />
                            {event.details}
                          </p>
                        </div>
                      )}
                    </CardContent>
                  </Card>
                </div>
              </div>
            );
          })}
        </div>
      </div>
      
      {/* Footer con información adicional */}
      <div className="mt-8 p-4 bg-muted/20 rounded-lg border border-dashed">
        <p className="text-xs text-muted-foreground text-center">
          Este historial muestra todos los eventos registrados para la solicitud en orden cronológico.
          Los eventos se registran automáticamente cuando se realizan acciones sobre la solicitud.
        </p>
      </div>
    </div>
  );
}
