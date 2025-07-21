import { useState, useEffect } from "react";
import { Link } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { 
    Download, 
    Eye, 
    Loader, 
    AlertTriangle, 
    CheckCircle, 
    Clock,
    RefreshCw
} from "lucide-react";

interface Credential {
    id: number;
    uuid: string;
    status: 'pending' | 'generating' | 'ready' | 'failed';
    retry_count: number;
    error_message?: string;
    generated_at?: string;
    is_ready: boolean;
}

interface AccreditationRequest {
    id: number;
    uuid: string;
    status: string;
    credential?: Credential;
    employee: {
        identification: string;
        first_name: string;
        last_name: string;
    };
}

interface CredentialSectionProps {
    request: AccreditationRequest;
    canDownload?: boolean;
    canRegenerate?: boolean;
}

export default function CredentialSection({ 
    request, 
    canDownload = false, 
    canRegenerate = false 
}: CredentialSectionProps) {
    const [credential, setCredential] = useState<Credential | null>(request.credential || null);
    

    const [isPolling, setIsPolling] = useState(false);

    // Polling para estados pendientes/generating
    useEffect(() => {
        if (!credential || ['pending', 'generating'].includes(credential.status)) {
            setIsPolling(true);
            
            const pollStatus = async () => {
                try {
                    const response = await fetch(`/accreditation-requests/${request.uuid}/credential/status`);
                    if (response.ok) {
                        const data = await response.json();
                        if (data.credential) {
                            setCredential(data.credential);
                            
                            // Detener polling si está lista o falló
                            if (['ready', 'failed'].includes(data.credential.status)) {
                                setIsPolling(false);
                            }
                        }
                    }
                } catch (error) {
                    console.error('Error polling credential status:', error);
                }
            };

            // Poll inicial
            pollStatus();
            
            // Continuar polling cada 5 segundos
            const interval = setInterval(pollStatus, 5000);
            
            return () => {
                clearInterval(interval);
                setIsPolling(false);
            };
        }
    }, [request.uuid, credential, credential?.status]);

    // Solo mostrar si la solicitud está aprobada
    if (request.status !== 'approved') {
        return null;
    }

    const getStatusBadge = (status: string) => {
        const statusMap = {
            pending: { variant: "secondary" as const, icon: Clock, text: "Pendiente" },
            generating: { variant: "default" as const, icon: Loader, text: "Generando" },
            ready: { variant: "default" as const, icon: CheckCircle, text: "Lista" },
            failed: { variant: "destructive" as const, icon: AlertTriangle, text: "Error" }
        };

        const config = statusMap[status as keyof typeof statusMap];
        if (!config) return null;

        const Icon = config.icon;
        
        return (
            <Badge variant={config.variant} className="flex items-center gap-1">
                <Icon className={`h-3 w-3 ${status === 'generating' ? 'animate-spin' : ''}`} />
                {config.text}
            </Badge>
        );
    };

    const getErrorMessage = (errorMessage?: string) => {
        if (!errorMessage) return "Error desconocido";
        
        try {
            const parsed = JSON.parse(errorMessage);
            return parsed.message || "Error desconocido";
        } catch {
            return errorMessage;
        }
    };

    return (
        <Card className="mt-6">
            <CardHeader>
                <CardTitle className="flex items-center justify-between">
                    <span>Credencial Digital</span>
                    {credential && getStatusBadge(credential.status)}
                </CardTitle>
            </CardHeader>
            <CardContent className="space-y-4">
                {!credential && (
                    <Alert>
                        <AlertTriangle className="h-4 w-4" />
                        <AlertTitle>Credencial no generada</AlertTitle>
                        <AlertDescription>
                            La credencial aún no ha sido generada para esta solicitud.
                        </AlertDescription>
                    </Alert>
                )}

                {credential?.status === 'pending' && (
                    <div className="flex items-center gap-2 text-muted-foreground">
                        <Clock className="h-4 w-4" />
                        <span>Preparando generación de credencial...</span>
                    </div>
                )}
                
                {credential?.status === 'generating' && (
                    <div className="flex items-center gap-2 text-blue-600">
                        <Loader className="animate-spin h-4 w-4" />
                        <span>
                            Generando credencial...
                            {credential.retry_count > 0 && ` (Intento ${credential.retry_count + 1})`}
                        </span>
                    </div>
                )}
                
                {credential?.status === 'ready' && (
                    <div className="space-y-3">
                        <div className="flex items-center gap-2 text-green-600">
                            <CheckCircle className="h-4 w-4" />
                            <span>Credencial lista para descarga</span>
                            {credential.generated_at && (
                                <span className="text-sm text-muted-foreground">
                                    (Generada: {new Date(credential.generated_at).toLocaleDateString()})
                                </span>
                            )}
                        </div>
                        
                        {canDownload && (
                            <div className="space-y-3">
                                {/* Botón principal */}
                                <div className="flex justify-center">
                                    <Button asChild size="lg" className="w-full sm:w-auto">
                                        <Link href={`/accreditation-requests/${request.uuid}/credential/preview`}>
                                            <Eye className="h-5 w-5 mr-2" />
                                            Ver Credencial
                                        </Link>
                                    </Button>
                                </div>
                                
                                {/* Botones secundarios */}
                                <div className="flex flex-wrap gap-2 justify-center">
                                    <Button asChild variant="outline">
                                        <a href={`/accreditation-requests/${request.uuid}/credential/download/image`} target="_blank">
                                            <Download className="h-4 w-4 mr-2" />
                                            PNG
                                        </a>
                                    </Button>
                                    
                                    <Button asChild variant="outline">
                                        <a href={`/accreditation-requests/${request.uuid}/credential/download/pdf`} target="_blank">
                                            <Download className="h-4 w-4 mr-2" />
                                            PDF
                                        </a>
                                    </Button>
                                    
                                    {canRegenerate && (
                                        <Button 
                                            asChild 
                                            variant="secondary"
                                        >
                                            <Link 
                                                href={`/accreditation-requests/${request.uuid}/credential/regenerate`}
                                                method="post"
                                            >
                                                <RefreshCw className="h-4 w-4 mr-2" />
                                                Regenerar
                                            </Link>
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                )}
                
                {credential?.status === 'failed' && (
                    <div className="space-y-3">
                        <Alert variant="destructive">
                            <AlertTriangle className="h-4 w-4" />
                            <AlertTitle>Error en generación</AlertTitle>
                            <AlertDescription className="space-y-2">
                                <p>{getErrorMessage(credential.error_message)}</p>
                                <p className="text-sm">
                                    Intentos realizados: {credential.retry_count}
                                </p>
                            </AlertDescription>
                        </Alert>
                        
                        {canRegenerate && (
                            <Button 
                                asChild 
                                variant="outline" 
                                size="sm"
                            >
                                <Link 
                                    href={`/accreditation-requests/${request.uuid}/credential/regenerate`}
                                    method="post"
                                >
                                    <RefreshCw className="h-4 w-4 mr-2" />
                                    Reintentar Generación
                                </Link>
                            </Button>
                        )}
                    </div>
                )}
                
                {isPolling && credential && ['pending', 'generating'].includes(credential.status) && (
                    <div className="text-xs text-muted-foreground flex items-center gap-1">
                        <div className="animate-pulse w-2 h-2 bg-blue-500 rounded-full"></div>
                        Actualizando estado automáticamente...
                    </div>
                )}
            </CardContent>
        </Card>
    );
}
