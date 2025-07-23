import { Head } from "@inertiajs/react";
import { Card, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
// No necesitamos el Badge component
import { 
    CheckCircle, 
    XCircle, 
    QrCode, 
    User
} from "lucide-react";
import { useState, FormEvent } from "react";
import { router } from "@inertiajs/react";

interface Employee {
    first_name: string;
    last_name: string;
    document_type?: string;
    document_number?: string;
    photo_path?: string;
    company?: string;
    position?: string;
}

interface Event {
    name: string;
    location?: string;
}

interface Zone {
    name: string;
    color?: string;
}

interface Credential {
    status: string;
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

    return (
        <>
            <Head title="Verificación de Credencial QR" />
            
            <div className="min-h-screen bg-gray-50 py-8">
                <div className="container mx-auto px-4 max-w-2xl">
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

                    {/* Formulario de Búsqueda QR - Diseño Minimalista */}
                    {!qrCode && (
                        <Card className="border-0 shadow-sm">
                            <div className="p-8">
                                <div className="text-center mb-8">
                                    <div className="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                        <QrCode className="w-8 h-8 text-blue-600" />
                                    </div>
                                    <h2 className="text-2xl font-bold text-gray-900 mb-2">Verificar Credencial</h2>
                                    <p className="text-gray-600">Ingrese el código QR para verificar la autenticidad de la credencial</p>
                                </div>
                                <form onSubmit={handleSubmit} className="max-w-md mx-auto">
                                    <div className="space-y-4">
                                        <div>
                                            <Label htmlFor="qrcode" className="text-sm font-medium text-gray-700">Código QR</Label>
                                            <div className="flex mt-2 space-x-3">
                                                <Input
                                                    id="qrcode"
                                                    type="text"
                                                    value={inputQrCode}
                                                    onChange={(e) => setInputQrCode(e.target.value)}
                                                    placeholder="Ejemplo: CRD_ABC123XYZ_456"
                                                    className="flex-1 font-mono text-sm"
                                                    autoFocus
                                                />
                                                <Button 
                                                    type="submit" 
                                                    disabled={isVerifying || !inputQrCode.trim()}
                                                    className="px-6"
                                                >
                                                    {isVerifying ? 'Verificando...' : 'Verificar'}
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </Card>
                    )}

                    {/* Information message when accessed from scanned QR */}
                    {qrCode && !result && (
                        <Card className="mb-8 border-blue-200 bg-blue-50">
                            <CardContent className="p-4">
                                <div className="flex items-center gap-3 text-blue-700">
                                    <QrCode className="w-5 h-5 flex-shrink-0" />
                                    <p className="text-sm">
                                        <strong>Verificando credencial...</strong> Por favor espere mientras verificamos la información.
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Verification Results */}
                    {result && (
                        <>
                            {/* Mensaje de Error - Diseño Minimalista */}
                            {(!result.valid || !result.data) && (
                                <Card className="border-0 shadow-sm">
                                    <div className="px-6 py-4 border-l-4 bg-red-50 border-red-500">
                                        <div className="flex items-center justify-between">
                                            <div className="flex items-center space-x-3">
                                                <XCircle className="h-5 w-5 text-red-600" />
                                                <div>
                                                    <h2 className="text-lg font-semibold text-red-900">
                                                        Credencial No Válida
                                                    </h2>
                                                    <p className="text-sm text-red-700">
                                                        El código QR no corresponde a ninguna credencial válida
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-xs font-medium bg-red-100 text-red-800">
                                                INVÁLIDO
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div className="p-6">
                                        <div className="text-center">
                                            <div className="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                                <XCircle className="h-8 w-8 text-red-500" />
                                            </div>
                                            <h3 className="text-lg font-semibold text-gray-900 mb-2">Credencial No Encontrada</h3>
                                            <p className="text-gray-600 mb-6">
                                                {result.message || "El código QR escaneado no corresponde a ninguna credencial válida en nuestro sistema."}
                                            </p>
                                            
                                            <Button 
                                                onClick={() => router.get('/verify-qr')}
                                                variant="outline"
                                                className="px-6"
                                            >
                                                Intentar con Otro Código
                                            </Button>
                                        </div>
                                    </div>
                                </Card>
                            )}

                            {/* Valid Credential Result - Rediseño Profesional */}
                            {result.valid && result.data && (
                                <div className="space-y-6">
                                    {/* Header de Estado - Diseño Minimalista */}
                                    <Card className="border-0 shadow-sm">
                                        <div className={`px-6 py-4 border-l-4 ${
                                            result.data.request_status === 'approved' 
                                                ? 'bg-green-50 border-green-500' 
                                                : 'bg-red-50 border-red-500'
                                        }`}>
                                            <div className="flex items-center justify-between">
                                                <div className="flex items-center space-x-3">
                                                    {result.data.request_status === 'approved' ? 
                                                        <CheckCircle className="h-5 w-5 text-green-600" /> : 
                                                        <XCircle className="h-5 w-5 text-red-600" />
                                                    }
                                                    <div>
                                                        <h2 className={`text-lg font-semibold ${
                                                            result.data.request_status === 'approved' 
                                                                ? 'text-green-900' 
                                                                : 'text-red-900'
                                                        }`}>
                                                            {result.data.request_status === 'approved' 
                                                                ? 'Credencial Válida' 
                                                                : 'Credencial No Válida'}
                                                        </h2>
                                                        <p className={`text-sm ${
                                                            result.data.request_status === 'approved' 
                                                                ? 'text-green-700' 
                                                                : 'text-red-700'
                                                        }`}>
                                                            Verificación realizada el {new Date().toLocaleDateString('es-ES', {
                                                                year: 'numeric',
                                                                month: 'long',
                                                                day: 'numeric',
                                                                hour: '2-digit',
                                                                minute: '2-digit'
                                                            })}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div className={`inline-flex items-center justify-center whitespace-nowrap rounded-md px-3 py-1 text-xs font-medium ${
                                                    result.data.request_status === 'approved' 
                                                        ? 'bg-green-100 text-green-800' 
                                                        : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {result.data.request_status === 'approved' ? 'APROBADO' : 'NO APROBADO'}
                                                </div>
                                            </div>
                                        </div>
                                    </Card>
                                    
                                    {/* Sección Principal - Información del Portador */}
                                    <Card className="border-0 shadow-sm">
                                        <div className="p-6">
                                            <div className="flex flex-col lg:flex-row lg:space-x-8">
                                                {/* Foto del Portador */}
                                                <div className="flex-shrink-0 mb-6 lg:mb-0">
                                                    <div className="flex justify-center">
                                                        {result.data.employee.photo_path ? (
                                                            <img 
                                                                src={`/storage/${result.data.employee.photo_path}`}
                                                                alt="Foto de perfil" 
                                                                className="h-32 w-32 object-cover rounded-full ring-4 ring-gray-100"
                                                            />
                                                        ) : (
                                                            <div className="h-32 w-32 bg-gray-100 flex items-center justify-center rounded-full ring-4 ring-gray-200">
                                                                <User className="h-12 w-12 text-gray-400" />
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                                
                                                {/* Información del Portador */}
                                                <div className="flex-1">
                                                    <div className="text-center lg:text-left mb-6">
                                                        <h3 className="text-2xl font-bold text-gray-900 mb-1">
                                                            {result.data.employee.first_name} {result.data.employee.last_name}
                                                        </h3>
                                                        <div className="flex flex-wrap justify-center lg:justify-start gap-2 mt-2">
                                                            {(result.data.employee.document_type && result.data.employee.document_number) && (
                                                                <div className="inline-flex items-center px-3 py-1 text-sm bg-gray-50 text-gray-700 rounded-full font-medium">
                                                                    {result.data.employee.document_type}-{result.data.employee.document_number}
                                                                </div>
                                                            )}
                                                            {result.data.employee.company && (
                                                                <div className="inline-flex items-center px-3 py-1 text-sm bg-blue-50 text-blue-700 rounded-full font-medium">
                                                                    {result.data.employee.company}
                                                                </div>
                                                            )}
                                                            {result.data.employee.position && (
                                                                <div className="inline-flex items-center px-3 py-1 text-sm bg-gray-50 text-gray-700 rounded-full font-medium">
                                                                    {result.data.employee.position}
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </Card>
                                            
                                    {/* Sección de Evento y Zonas - Rediseño Minimalista */}
                                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                        {/* Información del Evento */}
                                        <Card className="border-0 shadow-sm">
                                            <div className="p-6">
                                                <div className="flex items-center space-x-2 mb-4">
                                                    <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                                                    <h4 className="text-sm font-semibold text-gray-600 uppercase tracking-wide">Evento</h4>
                                                </div>
                                                <h5 className="text-lg font-semibold text-gray-900 mb-2">
                                                    {result.data.event.name}
                                                </h5>
                                                {result.data.event.location && (
                                                    <p className="text-sm text-gray-600">
                                                        {result.data.event.location}
                                                    </p>
                                                )}
                                            </div>
                                        </Card>
                                        
                                        {/* Zonas Autorizadas */}
                                        <Card className="border-0 shadow-sm">
                                            <div className="p-6">
                                                <div className="flex items-center space-x-2 mb-4">
                                                    <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                                                    <h4 className="text-sm font-semibold text-gray-600 uppercase tracking-wide">Zonas Autorizadas</h4>
                                                </div>
                                                <div className="flex flex-wrap gap-2">
                                                    {result.data.zones.map((zone, index) => (
                                                        <div
                                                            key={index}
                                                            className="inline-flex items-center px-3 py-1 text-xs font-medium rounded-full border"
                                                            style={{
                                                                backgroundColor: zone.color ? `${zone.color}15` : '#f3f4f6',
                                                                borderColor: zone.color || '#d1d5db',
                                                                color: zone.color || '#6b7280'
                                                            }}
                                                        >
                                                            {zone.name}
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>
                                        </Card>
                                    </div>
                                </div>
                            )}
                        </>
                    )}

                    {/* Footer Informativo - Diseño Minimalista */}
                    <div className="mt-8 p-6 bg-gray-50 rounded-lg border border-gray-200">
                        <div className="flex items-center justify-center space-x-2 mb-3">
                            <div className="w-1.5 h-1.5 bg-gray-400 rounded-full"></div>
                            <h4 className="text-xs font-semibold text-gray-500 uppercase tracking-wider">Verificación Oficial</h4>
                            <div className="w-1.5 h-1.5 bg-gray-400 rounded-full"></div>
                        </div>
                        <div className="text-center space-y-2">
                            <p className="text-sm text-gray-600">
                                Esta verificación confirma la autenticidad de la credencial en el momento de la consulta.
                            </p>
                            <p className="text-xs text-gray-500">
                                Para mayor seguridad, siempre verifique que la información mostrada coincida con la credencial física presentada.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
