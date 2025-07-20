import { Head } from "@inertiajs/react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { 
    CheckCircle, 
    XCircle, 
    User, 
    Calendar,
    MapPin,
    Shield,
    AlertTriangle
} from "lucide-react";

interface Employee {
    first_name: string;
    last_name: string;
    identification: string;
    position?: string;
    company?: string;
}

interface Event {
    name: string;
    description?: string;
    start_date?: string;
    end_date?: string;
    location?: string;
}

interface Zone {
    id: number;
    name: string;
    description?: string;
    color?: string;
}

interface Credential {
    id: number;
    uuid: string;
    status: string;
    qr_code: string;
    generated_at?: string;
    expires_at?: string;
    is_active: boolean;
    is_expired: boolean;
}

interface AccreditationRequest {
    id: number;
    uuid: string;
    status: string;
    employee: Employee;
    event: Event;
    zones: Zone[];
    credential: Credential;
}

interface VerifyCredentialProps {
    qrCode: string;
    isValid: boolean;
    request?: AccreditationRequest;
    credential?: Credential;
    error?: string;
}

export default function VerifyCredential({ 
    qrCode, 
    isValid, 
    request, 
    credential, 
    error 
}: VerifyCredentialProps) {
    const formatDate = (dateString?: string) => {
        if (!dateString) return "N/A";
        return new Date(dateString).toLocaleString("es-ES", {
            year: 'numeric',
            month: 'long', 
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const getValidityBadge = () => {
        if (!isValid || !credential) {
            return (
                <Badge variant="destructive" className="flex items-center gap-2">
                    <XCircle className="h-4 w-4" />
                    Credencial Inválida
                </Badge>
            );
        }

        if (credential.is_expired) {
            return (
                <Badge variant="destructive" className="flex items-center gap-2">
                    <AlertTriangle className="h-4 w-4" />
                    Credencial Expirada
                </Badge>
            );
        }

        if (!credential.is_active) {
            return (
                <Badge variant="destructive" className="flex items-center gap-2">
                    <XCircle className="h-4 w-4" />
                    Credencial Inactiva
                </Badge>
            );
        }

        return (
            <Badge variant="default" className="bg-green-600 flex items-center gap-2">
                <CheckCircle className="h-4 w-4" />
                Credencial Válida
            </Badge>
        );
    };

    return (
        <>
            <Head title="Verificación de Credencial" />
            
            <div className="min-h-screen bg-gray-50 flex items-center justify-center p-4">
                <div className="w-full max-w-2xl space-y-6">
                    {/* Header */}
                    <div className="text-center">
                        <h1 className="text-3xl font-bold text-gray-900">
                            Verificación de Credencial
                        </h1>
                        <p className="text-muted-foreground mt-2">
                            Resultado de la verificación del código QR
                        </p>
                    </div>

                    {/* Estado de Verificación */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <Shield className="h-5 w-5" />
                                    Estado de Verificación
                                </div>
                                {getValidityBadge()}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Código QR</p>
                                    <p className="font-mono text-sm bg-gray-100 p-2 rounded">
                                        {qrCode}
                                    </p>
                                </div>
                                
                                {error && (
                                    <Alert variant="destructive">
                                        <AlertTriangle className="h-4 w-4" />
                                        <AlertTitle>Error de Verificación</AlertTitle>
                                        <AlertDescription>{error}</AlertDescription>
                                    </Alert>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Información de la Credencial (solo si es válida) */}
                    {isValid && request && credential && (
                        <>
                            {/* Información del Empleado */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <User className="h-5 w-5" />
                                        Información del Portador
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="grid gap-3 md:grid-cols-2">
                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">Nombre Completo</p>
                                            <p className="font-medium">
                                                {request.employee.first_name} {request.employee.last_name}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">Identificación</p>
                                            <p className="font-medium">{request.employee.identification}</p>
                                        </div>
                                        {request.employee.position && (
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Cargo</p>
                                                <p className="font-medium">{request.employee.position}</p>
                                            </div>
                                        )}
                                        {request.employee.company && (
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Empresa</p>
                                                <p className="font-medium">{request.employee.company}</p>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Información del Evento */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Calendar className="h-5 w-5" />
                                        Información del Evento
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div>
                                        <p className="text-sm font-medium text-muted-foreground">Evento</p>
                                        <p className="font-medium">{request.event.name}</p>
                                    </div>
                                    {request.event.description && (
                                        <div>
                                            <p className="text-sm font-medium text-muted-foreground">Descripción</p>
                                            <p className="text-sm">{request.event.description}</p>
                                        </div>
                                    )}
                                    <div className="grid gap-3 md:grid-cols-2">
                                        {request.event.start_date && (
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Fecha de Inicio</p>
                                                <p className="text-sm">{formatDate(request.event.start_date)}</p>
                                            </div>
                                        )}
                                        {request.event.end_date && (
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Fecha de Fin</p>
                                                <p className="text-sm">{formatDate(request.event.end_date)}</p>
                                            </div>
                                        )}
                                    </div>
                                    {request.event.location && (
                                        <div className="flex items-center gap-2">
                                            <MapPin className="h-4 w-4 text-muted-foreground" />
                                            <p className="text-sm">{request.event.location}</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* Zonas Autorizadas */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Zonas de Acceso Autorizadas</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex flex-wrap gap-2">
                                        {request.zones.map((zone) => (
                                            <Badge 
                                                key={zone.id} 
                                                variant="outline"
                                                className="px-3 py-1"
                                                style={zone.color ? { 
                                                    borderColor: zone.color, 
                                                    color: zone.color,
                                                    backgroundColor: `${zone.color}10` 
                                                } : undefined}
                                            >
                                                {zone.name}
                                            </Badge>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Detalles de la Credencial */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Detalles de la Credencial</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div className="grid gap-3 md:grid-cols-2">
                                        {credential.generated_at && (
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Fecha de Emisión</p>
                                                <p className="font-medium">{formatDate(credential.generated_at)}</p>
                                            </div>
                                        )}
                                        {credential.expires_at && (
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Fecha de Expiración</p>
                                                <p className="font-medium">{formatDate(credential.expires_at)}</p>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        </>
                    )}

                    {/* Advertencia de seguridad */}
                    <Alert>
                        <Shield className="h-4 w-4" />
                        <AlertTitle>Verificación Oficial</AlertTitle>
                        <AlertDescription>
                            Esta verificación ha sido realizada por el sistema oficial de credenciales. 
                            Los datos mostrados corresponden al momento de emisión de la credencial.
                        </AlertDescription>
                    </Alert>
                </div>
            </div>
        </>
    );
}
