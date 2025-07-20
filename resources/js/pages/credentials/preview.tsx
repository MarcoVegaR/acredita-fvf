import { Head, Link } from "@inertiajs/react";
import AppLayout from "@/layouts/app-layout";
import { Button } from "@/components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { 
    ArrowLeft, 
    Download, 
    ZoomIn,
    ZoomOut,
    RotateCcw
} from "lucide-react";
import { useState } from "react";

interface Employee {
    first_name: string;
    last_name: string;
    identification: string;
    position?: string;
    company?: string;
}

interface Event {
    name: string;
    location?: string;
}

interface Zone {
    id: number;
    name: string;
    color?: string;
}

interface Credential {
    id: number;
    uuid: string;
    status: string;
    credential_image_path?: string;
    qr_code?: string;
    generated_at?: string;
}

interface AccreditationRequest {
    id: number;
    uuid: string;
    employee: Employee;
    event: Event;
    zones: Zone[];
    credential: Credential;
}

interface PreviewCredentialProps {
    request: AccreditationRequest;
    credential: Credential;
    imageUrl?: string;
    canDownload: boolean;
}

export default function PreviewCredential({ 
    request, 
    credential, 
    imageUrl, 
    canDownload 
}: PreviewCredentialProps) {
    const [zoom, setZoom] = useState(100);
    const [rotation, setRotation] = useState(0);

    const handleZoomIn = () => setZoom(prev => Math.min(prev + 25, 200));
    const handleZoomOut = () => setZoom(prev => Math.max(prev - 25, 50));
    const handleReset = () => {
        setZoom(100);
        setRotation(0);
    };
    const handleRotate = () => setRotation(prev => (prev + 90) % 360);

    return (
        <AppLayout>
            <Head title={`Vista Previa - Credencial ${request.employee.first_name} ${request.employee.last_name}`} />
            
            <div className="container mx-auto py-6">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-4">
                        <Button asChild variant="outline" size="sm">
                            <Link href={`/accreditation-requests/${request.uuid}/credential`}>
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Volver
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold">
                                Vista Previa de Credencial
                            </h1>
                            <p className="text-muted-foreground">
                                {request.employee.first_name} {request.employee.last_name} - {request.event.name}
                            </p>
                        </div>
                    </div>

                    {canDownload && credential.status === 'ready' && (
                        <div className="flex gap-2">
                            <Button asChild variant="outline">
                                <Link href={`/accreditation-requests/${request.uuid}/credential/download/image`}>
                                    <Download className="h-4 w-4 mr-2" />
                                    Descargar PNG
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link href={`/accreditation-requests/${request.uuid}/credential/download/pdf`}>
                                    <Download className="h-4 w-4 mr-2" />
                                    Descargar PDF
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>

                <div className="grid gap-6 lg:grid-cols-4">
                    {/* Controles */}
                    <Card className="lg:col-span-1">
                        <CardHeader>
                            <CardTitle>Controles de Vista</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <label className="text-sm font-medium mb-2 block">Zoom</label>
                                <div className="flex items-center gap-2">
                                    <Button 
                                        variant="outline" 
                                        size="sm" 
                                        onClick={handleZoomOut}
                                        disabled={zoom <= 50}
                                    >
                                        <ZoomOut className="h-4 w-4" />
                                    </Button>
                                    <span className="text-sm font-medium min-w-[3rem] text-center">
                                        {zoom}%
                                    </span>
                                    <Button 
                                        variant="outline" 
                                        size="sm" 
                                        onClick={handleZoomIn}
                                        disabled={zoom >= 200}
                                    >
                                        <ZoomIn className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>

                            <div>
                                <label className="text-sm font-medium mb-2 block">Rotación</label>
                                <Button 
                                    variant="outline" 
                                    size="sm" 
                                    onClick={handleRotate}
                                    className="w-full"
                                >
                                    <RotateCcw className="h-4 w-4 mr-2" />
                                    Rotar 90°
                                </Button>
                            </div>

                            <Button 
                                variant="outline" 
                                size="sm" 
                                onClick={handleReset}
                                className="w-full"
                            >
                                Restablecer
                            </Button>

                            {/* Información */}
                            <div className="pt-4 space-y-2 text-sm">
                                <div>
                                    <p className="font-medium text-muted-foreground">Estado:</p>
                                    <p>{credential.status}</p>
                                </div>
                                {credential.generated_at && (
                                    <div>
                                        <p className="font-medium text-muted-foreground">Generada:</p>
                                        <p>{new Date(credential.generated_at).toLocaleDateString()}</p>
                                    </div>
                                )}
                                {credential.qr_code && (
                                    <div>
                                        <p className="font-medium text-muted-foreground">Código QR:</p>
                                        <p className="font-mono text-xs bg-gray-100 p-1 rounded truncate">
                                            {credential.qr_code}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Vista Previa */}
                    <Card className="lg:col-span-3">
                        <CardHeader>
                            <CardTitle>Credencial</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="relative overflow-auto border border-gray-200 rounded-lg bg-gray-50 min-h-[500px] flex items-center justify-center">
                                {imageUrl && credential.status === 'ready' ? (
                                    <div className="relative">
                                        <img
                                            src={imageUrl}
                                            alt={`Credencial de ${request.employee.first_name} ${request.employee.last_name}`}
                                            className="max-w-none shadow-lg"
                                            style={{
                                                transform: `scale(${zoom / 100}) rotate(${rotation}deg)`,
                                                transformOrigin: 'center',
                                                transition: 'transform 0.2s ease-in-out'
                                            }}
                                        />
                                    </div>
                                ) : credential.status === 'generating' ? (
                                    <div className="text-center space-y-4">
                                        <div className="animate-spin h-8 w-8 border-4 border-blue-500 border-t-transparent rounded-full mx-auto"></div>
                                        <div>
                                            <h3 className="text-lg font-medium">Generando credencial...</h3>
                                            <p className="text-muted-foreground">
                                                Por favor espere mientras se genera la credencial
                                            </p>
                                        </div>
                                    </div>
                                ) : credential.status === 'pending' ? (
                                    <div className="text-center space-y-4">
                                        <div className="h-8 w-8 bg-yellow-500 rounded-full mx-auto flex items-center justify-center">
                                            <div className="h-4 w-4 bg-white rounded-full animate-pulse"></div>
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-medium">Credencial pendiente</h3>
                                            <p className="text-muted-foreground">
                                                La generación de la credencial iniciará pronto
                                            </p>
                                        </div>
                                    </div>
                                ) : credential.status === 'failed' ? (
                                    <div className="text-center space-y-4">
                                        <div className="h-8 w-8 bg-red-500 rounded-full mx-auto flex items-center justify-center">
                                            <div className="h-4 w-4 bg-white"></div>
                                        </div>
                                        <div>
                                            <h3 className="text-lg font-medium text-red-600">Error en generación</h3>
                                            <p className="text-muted-foreground">
                                                No se pudo generar la credencial. Intente regenerarla.
                                            </p>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-center space-y-4">
                                        <div className="h-8 w-8 bg-gray-400 rounded-full mx-auto"></div>
                                        <div>
                                            <h3 className="text-lg font-medium">Vista previa no disponible</h3>
                                            <p className="text-muted-foreground">
                                                La credencial no está lista para visualización
                                            </p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
