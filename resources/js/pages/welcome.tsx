import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    Shield, 
    Users, 
    Zap, 
    CheckCircle, 
    ArrowRight, 
    Award,
    QrCode,
    FileImage,
    Lock,
    Clock,
    Sparkles
} from 'lucide-react';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    const features = [
        {
            icon: Shield,
            title: 'Seguridad Avanzada',
            description: 'Sistema de autenticación robusto con permisos granulares y verificación QR.'
        },
        {
            icon: QrCode,
            title: 'Credenciales Digitales',
            description: 'Generación automática de credenciales con códigos QR únicos y verificables.'
        },
        {
            icon: Users,
            title: 'Gestión de Empleados',
            description: 'Administración completa de empleados, proveedores y zonas de acceso.'
        },
        {
            icon: FileImage,
            title: 'Plantillas Personalizadas',
            description: 'Editor visual para crear plantillas de credenciales adaptadas a cada evento.'
        },
        {
            icon: Zap,
            title: 'Procesamiento Rápido',
            description: 'Generación asíncrona de credenciales para un procesamiento eficiente.'
        },
        {
            icon: Lock,
            title: 'Control de Acceso',
            description: 'Workflow completo de aprobación con estados de seguimiento detallado.'
        }
    ];

    const stats = [
        { label: 'Sistema Confiable', value: '100%', icon: Award },
        { label: 'Seguridad Avanzada', value: '256-bit', icon: Lock },
        { label: 'Disponibilidad', value: '24/7', icon: Clock },
        { label: 'Soporte Técnico', value: 'Completo', icon: Users }
    ];

    return (
        <>
            <Head title="Acredita FVF - Sistema de Acreditación Digital">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
                <meta name="description" content="Sistema profesional de acreditación digital para eventos deportivos y corporativos. Gestión segura de credenciales con códigos QR." />
            </Head>
            
            <div className="min-h-screen bg-gradient-to-br from-background via-card to-muted dark:from-background dark:via-card dark:to-muted">
                {/* Header */}
                <header className="relative z-50 w-full border-b border-border/50 bg-card/80 backdrop-blur-sm">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="flex h-16 items-center justify-between">
                            {/* Logo */}
                            <div className="flex items-center space-x-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-card dark:bg-card">
                                    <img src="/favicon.ico" alt="FVF Logo" className="h-6 w-6" />
                                </div>
                                <div>
                                    <h1 className="text-xl font-bold text-foreground">Acredita FVF</h1>
                                    <p className="text-xs text-muted-foreground">Sistema de Acreditación</p>
                                </div>
                            </div>

                            {/* Navigation */}
                            <nav className="flex items-center space-x-4">
                                {auth.user ? (
                                    <Button asChild variant="default">
                                        <Link href={route('dashboard')}>
                                            <ArrowRight className="mr-2 h-4 w-4" />
                                            Dashboard
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button asChild variant="ghost">
                                            <Link href={route('login')}>Iniciar Sesión</Link>
                                        </Button>
                                    </>
                                )}
                            </nav>
                        </div>
                    </div>
                </header>

                {/* Hero Section */}
                <section className="relative overflow-hidden px-4 py-24 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-7xl">
                        <div className="text-center">
                            {/* Badge */}
                            <div className="mb-8">
                                <Badge variant="secondary" className="px-4 py-2 text-sm font-medium">
                                    <Sparkles className="mr-2 h-4 w-4" />
                                    Sistema de Acreditación de Nueva Generación
                                </Badge>
                            </div>
                            
                            {/* Main Heading */}
                            <div className="mb-8">
                                <h2 className="text-4xl font-bold tracking-tight text-foreground sm:text-6xl">
                                    Acreditación Digital
                                    <span className="block text-muted-foreground">Profesional y Segura</span>
                                </h2>
                                <p className="mx-auto mt-6 max-w-2xl text-lg leading-8 text-muted-foreground">
                                    Sistema integral para la gestión de credenciales digitales con tecnología QR, 
                                    diseñado para eventos deportivos y corporativos de alto nivel.
                                </p>
                            </div>

                            {/* CTA Buttons */}
                            <div className="flex items-center justify-center gap-4">
                                {!auth.user && (
                                    <Button asChild size="lg" className="px-8">
                                        <Link href={route('login')}>
                                            <Shield className="mr-2 h-5 w-5" />
                                            Acceder al Sistema
                                        </Link>
                                    </Button>
                                )}
                                {auth.user && (
                                    <Button asChild size="lg" className="px-8">
                                        <Link href={route('dashboard')}>
                                            <ArrowRight className="mr-2 h-5 w-5" />
                                            Ir al Dashboard
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Background Pattern */}
                    <div className="absolute inset-0 -z-10 overflow-hidden">
                        <div className="absolute -top-40 -right-40 h-80 w-80 rounded-full bg-gradient-to-br from-primary/20 to-secondary/20 blur-3xl"></div>
                        <div className="absolute -bottom-40 -left-40 h-80 w-80 rounded-full bg-gradient-to-br from-accent/20 to-primary/20 blur-3xl"></div>
                    </div>
                </section>

                {/* Stats Section */}
                <section className="px-4 py-16 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-7xl">
                        <div className="grid grid-cols-2 gap-8 lg:grid-cols-4">
                            {stats.map((stat, index) => {
                                const Icon = stat.icon;
                                return (
                                    <div key={index} className="text-center">
                                        <div className="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-primary">
                                            <Icon className="h-6 w-6 text-primary-foreground" />
                                        </div>
                                        <div className="text-2xl font-bold text-foreground">{stat.value}</div>
                                        <div className="text-sm text-muted-foreground">{stat.label}</div>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* Features Section */}
                <section className="px-4 py-16 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-7xl">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                                Características Principales
                            </h2>
                            <p className="mt-4 text-lg text-muted-foreground">
                                Todo lo que necesitas para gestionar acreditaciones profesionalmente
                            </p>
                        </div>

                        <div className="grid gap-8 lg:grid-cols-3">
                            {features.map((feature, index) => {
                                const Icon = feature.icon;
                                return (
                                    <Card key={index} className="border-border bg-card/80 backdrop-blur-sm">
                                        <CardHeader>
                                            <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary">
                                                <Icon className="h-6 w-6 text-primary-foreground" />
                                            </div>
                                            <CardTitle className="text-xl font-semibold text-foreground">
                                                {feature.title}
                                            </CardTitle>
                                        </CardHeader>
                                        <CardContent>
                                            <CardDescription className="text-muted-foreground">
                                                {feature.description}
                                            </CardDescription>
                                        </CardContent>
                                    </Card>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* Process Section */}
                <section className="px-4 py-16 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-7xl">
                        <div className="text-center mb-16">
                            <h2 className="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                                Proceso Simplificado
                            </h2>
                            <p className="mt-4 text-lg text-muted-foreground">
                                Desde la solicitud hasta la credencial digital en pocos pasos
                            </p>
                        </div>

                        <div className="grid gap-8 lg:grid-cols-4">
                            {[
                                {
                                    step: "1",
                                    title: "Solicitud",
                                    description: "El empleado o proveedor envía su solicitud de acreditación con los datos requeridos.",
                                    icon: Users
                                },
                                {
                                    step: "2", 
                                    title: "Revisión",
                                    description: "El equipo administrativo revisa y valida la información proporcionada.",
                                    icon: CheckCircle
                                },
                                {
                                    step: "3",
                                    title: "Aprobación",
                                    description: "Una vez validada, la solicitud es aprobada y se inicia la generación.",
                                    icon: Award
                                },
                                {
                                    step: "4",
                                    title: "Credencial",
                                    description: "Se genera automáticamente la credencial digital con código QR único.",
                                    icon: QrCode
                                }
                            ].map((item, index) => {
                                const Icon = item.icon;
                                return (
                                    <div key={index} className="text-center">
                                        <div className="relative mb-6">
                                            <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary text-primary-foreground">
                                                <Icon className="h-8 w-8" />
                                            </div>
                                            <div className="absolute -top-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-accent text-xs font-bold text-accent-foreground">
                                                {item.step}
                                            </div>
                                        </div>
                                        <h3 className="text-xl font-semibold text-foreground mb-2">
                                            {item.title}
                                        </h3>
                                        <p className="text-muted-foreground">
                                            {item.description}
                                        </p>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="px-4 py-16 sm:px-6 lg:px-8 bg-primary">
                    <div className="mx-auto max-w-4xl text-center">
                        <h2 className="text-3xl font-bold tracking-tight text-primary-foreground sm:text-4xl">
                            ¿Listo para digitalizar tus acreditaciones?
                        </h2>
                        <p className="mt-6 text-lg leading-8 text-primary-foreground/80">
                            Únete a las organizaciones que confían en nuestro sistema para gestionar 
                            sus eventos de manera profesional y segura.
                        </p>
                        <div className="mt-10 flex items-center justify-center gap-6">
                            {!auth.user && (
                                <>
                                    <Button asChild size="lg" variant="secondary" className="px-8">
                                        <Link href={route('login')}>
                                            <img src="/favicon.ico" alt="FVF" className="mr-2 h-5 w-5" />
                                            Comenzar Ahora
                                        </Link>
                                    </Button>
                                </>
                            )}
                            {auth.user && (
                                <Button asChild size="lg" variant="secondary" className="px-8">
                                    <Link href={route('dashboard')}>
                                        <ArrowRight className="mr-2 h-5 w-5" />
                                        Ir al Dashboard
                                    </Link>
                                </Button>
                            )}
                        </div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="px-4 py-8 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-7xl">
                        <div className="flex items-center justify-between border-t border-border/50 pt-8">
                            <div className="flex items-center space-x-3">
                                <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-primary">
                                    <img src="/favicon.ico" alt="FVF Logo" className="h-4 w-4" />
                                </div>
                                <div>
                                    <div className="text-sm font-medium text-foreground">Acredita FVF</div>
                                    <div className="text-xs text-muted-foreground">
                                        © 2025 Todos los derechos reservados
                                    </div>
                                </div>
                            </div>
                            <div className="text-xs text-muted-foreground">
                                Sistema de Acreditación Digital v1.0
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
