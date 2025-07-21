import { Head } from "@inertiajs/react";
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Badge } from "@/components/ui/badge";
import { 
    CheckCircle, 
    XCircle, 
    QrCode, 
    User, 
    MapPin, 
    Calendar,
    Building,
    Shield,
    Clock
} from "lucide-react";
import { useState, FormEvent } from "react";
import { router } from "@inertiajs/react";

interface Employee {
    first_name: string;
    last_name: string;
    identification?: string;
    position?: string;
    company?: string;
}

interface Event {
    name: string;
    location?: string;
    start_date?: string;
    end_date?: string;
}

interface Zone {
    name: string;
    color?: string;
}

interface Credential {
    status: string;
    issued_at: string;
    expires_at?: string;
    verified_at: string;
}

interface VerificationResult {
    valid: boolean;
    data?: {
        employee: Employee;
        event: Event;
        zones: Zone[];
        request_status: string;
        credential: Credential;
    };
    message?: string;
}

interface VerificationPageProps {
    qrCode?: string;
    result?: VerificationResult;
}

export default function QRVerification({ qrCode = '', result }: VerificationPageProps) {
    const [inputQrCode, setInputQrCode] = useState(qrCode);
    const [isVerifying, setIsVerifying] = useState(false);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        if (!inputQrCode.trim()) return;

        setIsVerifying(true);
        router.get(`/verify-qr?qr=${encodeURIComponent(inputQrCode.trim())}`, {}, {
            onFinish: () => setIsVerifying(false)
        });
    };

    const formatDate = (dateString: string) => {
        try {
            return new Date(dateString).toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch {
            return dateString;
        }
    };

    return (
        <>
            <Head title="Verificación de Credencial QR" />
            
            <div className="min-h-screen bg-gray-50 py-8">
                <div className="container mx-auto px-4 max-w-4xl">
                    {/* Header */}
                    <div className="text-center mb-8">
                        <div className="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-4">
                            <QrCode className="w-8 h-8 text-blue-600" />
                        </div>
                        <h1 className="text-3xl font-bold text-gray-900">
                            Verificación de Credencial
                        </h1>
                        <p className="text-lg text-gray-600 mt-2">
                            Escanea o ingresa el código QR para verificar la autenticidad de una credencial
                        </p>
                    </div>

                    {/* Formulario de verificación - solo mostrar si no viene QR en la URL */}
                    {!qrCode && (
                        <Card className="mb-8">
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <QrCode className="w-5 h-5" />
                                    Verificar Credencial
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form onSubmit={handleSubmit} className="space-y-4">
                                    <div>
                                        <Label htmlFor="qr-code">Código QR</Label>
                                        <Input
                                            id="qr-code"
                                            type="text"
                                            value={inputQrCode}
                                            onChange={(e) => setInputQrCode(e.target.value)}
                                            placeholder="Ingrese el código QR de la credencial"
                                            className="mt-1"
                                        />
                                    </div>
                                    <Button 
                                        type="submit" 
                                        disabled={!inputQrCode.trim() || isVerifying}
                                        className="w-full"
                                    >
                                        {isVerifying ? 'Verificando...' : 'Verificar Credencial'}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    )}
                    
                    {/* Mensaje informativo cuando se accede desde QR escaneado */}
                    {qrCode && (
                        <Card className="mb-8 border-blue-200 bg-blue-50">
                            <CardContent className="pt-6">
                                <div className="flex items-center gap-3 text-blue-700">
                                    <QrCode className="w-5 h-5" />
                                    <p className="text-sm">
                                        <strong>Credencial escaneada:</strong> Mostrando información de la credencial verificada automáticamente.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Resultados de verificación */}
                    {result && (
                        <div className="space-y-6">
                            {/* Estado de verificación */}
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="flex items-center justify-center space-x-3">
                                        {result.valid ? (
                                            <>
                                                <CheckCircle className="w-12 h-12 text-green-500" />
                                                <div className="text-center">
                                                    <h2 className="text-2xl font-bold text-green-700">
                                                        ✓ Credencial Válida
                                                    </h2>
                                                    <p className="text-green-600">
                                                        La credencial ha sido verificada exitosamente
                                                    </p>
                                                </div>
                                            </>
                                        ) : (
                                            <>
                                                <XCircle className="w-12 h-12 text-red-500" />
                                                <div className="text-center">
                                                    <h2 className="text-2xl font-bold text-red-700">
                                                        ✗ Credencial Inválida
                                                    </h2>
                                                    <p className="text-red-600">
                                                        {result.message || 'La credencial no pudo ser verificada'}
                                                    </p>
                                                </div>
                                            </>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Detalles de la credencial válida */}
                            {result.valid && result.data && (
                                <div className="grid gap-6 md:grid-cols-2">
                                    {/* Información del titular */}
                                    <Card>
                                        <CardHeader>
                                            <CardTitle className="flex items-center gap-2">
                                                <User className="w-5 h-5" />
                                                Titular de la Credencial
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-3">
                                            <div>
                                                <p className="text-sm font-medium text-gray-500">Nombre</p>
                                                <p className="text-lg font-semibold">
                                                    {result.data.employee.first_name} {result.data.employee.last_name}
                                                </p>
                                            </div>
                                            {result.data.employee.identification && (
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">Identificación</p>
                                                    <p className="text-base font-mono">{result.data.employee.identification}</p>
                                                </div>
                                            )}
                                            {result.data.employee.position && (
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">Cargo</p>
                                                    <p className="text-base">{result.data.employee.position}</p>
                                                </div>
                                            )}
                                            {result.data.employee.company && (
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">Empresa</p>
                                                    <p className="text-base flex items-center gap-2">
                                                        <Building className="w-4 h-4" />
                                                        {result.data.employee.company}
                                                    </p>
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>

                                    {/* Información del evento */}
                                    <Card>
                                        <CardHeader>
                                            <CardTitle className="flex items-center gap-2">
                                                <Calendar className="w-5 h-5" />
                                                Evento
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-3">
                                            <div>
                                                <p className="text-sm font-medium text-gray-500">Nombre del Evento</p>
                                                <p className="text-lg font-semibold">{result.data.event.name}</p>
                                            </div>
                                            {result.data.event.location && (
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">Ubicación</p>
                                                    <p className="text-base flex items-center gap-2">
                                                        <MapPin className="w-4 h-4" />
                                                        {result.data.event.location}
                                                    </p>
                                                </div>
                                            )}
                                            {result.data.event.start_date && (
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">
                                                        Fecha {result.data.event.end_date ? 'de Inicio' : ''}
                                                    </p>
                                                    <p className="text-base">{formatDate(result.data.event.start_date)}</p>
                                                </div>
                                            )}
                                            {result.data.event.end_date && (
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">Fecha de Fin</p>
                                                    <p className="text-base">{formatDate(result.data.event.end_date)}</p>
                                                </div>
                                            )}
                                        </CardContent>
                                    </Card>

                                    {/* Zonas de acceso */}
                                    {result.data.zones && result.data.zones.length > 0 && (
                                        <Card>
                                            <CardHeader>
                                                <CardTitle className="flex items-center gap-2">
                                                    <Shield className="w-5 h-5" />
                                                    Zonas de Acceso
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="flex flex-wrap gap-2">
                                                    {result.data.zones.map((zone, index) => (
                                                        <Badge
                                                            key={index}
                                                            variant="secondary"
                                                            style={{
                                                                backgroundColor: zone.color ? `${zone.color}20` : undefined,
                                                                borderColor: zone.color || undefined,
                                                                color: zone.color || undefined
                                                            }}
                                                        >
                                                            {zone.name}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    )}

                                    {/* Estado de aprobación */}
                                    <Card>
                                        <CardHeader>
                                            <CardTitle className="flex items-center gap-2">
                                                <Shield className="w-5 h-5" />
                                                Estado de Acreditación
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-3">
                                            <div>
                                                <p className="text-sm font-medium text-gray-500">Estado de Solicitud</p>
                                                <Badge
                                                    variant={result.data.request_status === 'approved' ? 'default' : 'secondary'}
                                                    className={`${
                                                        result.data.request_status === 'approved' 
                                                            ? 'bg-green-100 text-green-800 border-green-200'
                                                            : result.data.request_status === 'pending'
                                                            ? 'bg-yellow-100 text-yellow-800 border-yellow-200'
                                                            : result.data.request_status === 'rejected'
                                                            ? 'bg-red-100 text-red-800 border-red-200'
                                                            : 'bg-gray-100 text-gray-800 border-gray-200'
                                                    }`}
                                                >
                                                    {result.data.request_status === 'approved' && '✓ Aprobada'}
                                                    {result.data.request_status === 'pending' && '⏳ Pendiente'}
                                                    {result.data.request_status === 'rejected' && '✗ Rechazada'}
                                                    {!['approved', 'pending', 'rejected'].includes(result.data.request_status) && result.data.request_status}
                                                </Badge>
                                            </div>
                                            <div>
                                                <p className="text-sm font-medium text-gray-500">Estado de Credencial</p>
                                                <Badge
                                                    variant={result.data.credential.status === 'ready' ? 'default' : 'secondary'}
                                                    className={`${
                                                        result.data.credential.status === 'ready' 
                                                            ? 'bg-blue-100 text-blue-800 border-blue-200'
                                                            : result.data.credential.status === 'generating'
                                                            ? 'bg-orange-100 text-orange-800 border-orange-200'
                                                            : 'bg-gray-100 text-gray-800 border-gray-200'
                                                    }`}
                                                >
                                                    {result.data.credential.status === 'ready' && '✓ Lista'}
                                                    {result.data.credential.status === 'generating' && '⚙️ Generando'}
                                                    {!['ready', 'generating'].includes(result.data.credential.status) && result.data.credential.status}
                                                </Badge>
                                            </div>
                                        </CardContent>
                                    </Card>
                                    
                                    {/* Información de emisión */}
                                    <Card>
                                        <CardHeader>
                                            <CardTitle className="flex items-center gap-2">
                                                <Clock className="w-5 h-5" />
                                                Información de Verificación
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent className="space-y-3">
                                            <div>
                                                <p className="text-sm font-medium text-gray-500">Credencial Emitida</p>
                                                <p className="text-base">{formatDate(result.data.credential.issued_at)}</p>
                                            </div>
                                            {result.data.credential.expires_at && (
                                                <div>
                                                    <p className="text-sm font-medium text-gray-500">Fecha de Expiración</p>
                                                    <p className="text-base">{formatDate(result.data.credential.expires_at)}</p>
                                                </div>
                                            )}
                                            <div>
                                                <p className="text-sm font-medium text-gray-500">Verificado el</p>
                                                <p className="text-base">{formatDate(result.data.credential.verified_at)}</p>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Información adicional */}
                    <Card className="mt-8">
                        <CardContent className="pt-6">
                            <div className="text-center text-sm text-gray-500">
                                <p>
                                    Esta verificación confirma la autenticidad de la credencial en el momento de la consulta.
                                </p>
                                <p className="mt-2">
                                    Para mayor seguridad, siempre verifique que la información mostrada 
                                    coincida con la credencial física presentada.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
