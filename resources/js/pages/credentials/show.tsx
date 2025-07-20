import { Head, Link } from "@inertiajs/react";
import AppLayout from "@/layouts/app-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { 
    ArrowLeft, 
    Download, 
    Eye, 
    User, 
    Calendar,
    MapPin,
    RefreshCw
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
    status: 'pending' | 'generating' | 'ready' | 'failed';
    qr_code?: string;
    generated_at?: string;
    expires_at?: string;
    is_ready: boolean;
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

interface ShowCredentialProps {
    request: AccreditationRequest;
    credential: Credential;
    canDownload: boolean;
}

export default function ShowCredential({ request, credential, canDownload }: ShowCredentialProps) {
    const getStatusColor = (status: string) => {
        const colors = {
            pending: "bg-yellow-100 text-yellow-800",
            generating: "bg-blue-100 text-blue-800",
            ready: "bg-green-100 text-green-800",
            failed: "bg-red-100 text-red-800"
        };
        return colors[status as keyof typeof colors] || "bg-gray-100 text-gray-800";
    };

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

    return (
        <AppLayout>
            <Head title={`Credencial - ${request.employee.first_name} ${request.employee.last_name}`} />
            
            <div className="container mx-auto py-6 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button asChild variant="outline" size="sm">
                            <Link href={`/accreditation-requests/${request.uuid}`}>
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver a Solicitud
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold">
                                Credencial Digital
                            </h1>
                            <p className="text-muted-foreground">
                                {request.employee.first_name} {request.employee.last_name}
                            </p>
                        </div>
                    </div>

                    <Badge className={getStatusColor(credential.status)}>
                        {credential.status === 'pending' && 'Pendiente'}
                        {credential.status === 'generating' && 'Generando'}
                        {credential.status === 'ready' && 'Lista'}
                        {credential.status === 'failed' && 'Error'}
                    </Badge>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Información del Empleado */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <User className="h-5 w-5" />
                                Información del Empleado
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Nombre</p>
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
                            {request.event.start_date && (
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Fecha de Inicio</p>
                                    <p className="text-sm">{formatDate(request.event.start_date)}</p>
                                </div>
                            )}
                            {request.event.location && (
                                <div className="flex items-center gap-2">
                                    <MapPin className="h-4 w-4 text-muted-foreground" />
                                    <p className="text-sm">{request.event.location}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Zonas Asignadas */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Zonas de Acceso</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-wrap gap-2">
                                {request.zones.map((zone) => (
                                    <Badge 
                                        key={zone.id} 
                                        variant="outline"
                                        style={zone.color ? { borderColor: zone.color, color: zone.color } : undefined}
                                    >
                                        {zone.name}
                                    </Badge>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Información de la Credencial */}
                <Card>
                    <CardHeader>
                        <CardTitle>Estado de la Credencial</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Estado</p>
                                <Badge className={getStatusColor(credential.status)}>
                                    {credential.status === 'pending' && 'Pendiente de procesamiento'}
                                    {credential.status === 'generating' && 'Generando credencial...'}
                                    {credential.status === 'ready' && 'Lista para descarga'}
                                    {credential.status === 'failed' && 'Error en generación'}
                                </Badge>
                            </div>
                            
                            {credential.generated_at && (
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Generada el</p>
                                    <p className="font-medium">{formatDate(credential.generated_at)}</p>
                                </div>
                            )}

                            {credential.expires_at && (
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Expira el</p>
                                    <p className="font-medium">{formatDate(credential.expires_at)}</p>
                                </div>
                            )}

                            {credential.qr_code && (
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Código QR</p>
                                    <p className="font-mono text-sm bg-gray-100 p-2 rounded">
                                        {credential.qr_code}
                                    </p>
                                </div>
                            )}
                        </div>

                        {canDownload && credential.is_ready && (
                            <div>
                                <Separator className="my-4" />
                                <div className="flex flex-wrap gap-3">
                                    <Button asChild>
                                        <Link href={`/accreditation-requests/${request.uuid}/credential/preview`}>
                                            <Eye className="h-4 w-4 mr-2" />
                                            Vista Previa
                                        </Link>
                                    </Button>
                                    
                                    <Button asChild variant="outline">
                                        <Link href={`/accreditation-requests/${request.uuid}/credential/download/image`}>
                                            <Download className="h-4 w-4 mr-2" />
                                            Descargar PNG
                                        </Link>
                                    </Button>
                                    
                                    <Button asChild variant="outline">
                                        <Link href={`/accreditation-requests/${request.uuid}/credential/download/pdf`}>
                                            <Download className="h-4 w-4 mr-2" />
                                            Descargar PDF
                                        </Link>
                                    </Button>

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
                                            Regenerar
                                        </Link>
                                    </Button>
                                </div>
                            </div>
                        )}

                        {credential.status === 'failed' && (
                            <div>
                                <Separator className="my-4" />
                                <Button 
                                    asChild 
                                    variant="outline"
                                >
                                    <Link 
                                        href={`/accreditation-requests/${request.uuid}/credential/regenerate`}
                                        method="post"
                                    >
                                        <RefreshCw className="h-4 w-4 mr-2" />
                                        Reintentar Generación
                                    </Link>
                                </Button>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
