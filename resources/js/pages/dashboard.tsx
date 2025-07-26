import React from 'react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { 
  Users, 
  Building2, 
  FileCheck, 
  UserCheck, 
  Clock, 
  CheckCircle, 
  AlertCircle,
  FileText,
  TrendingUp,
  Activity
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';

interface DashboardStats {
  employees: {
    total: number;
    active: number;
    by_type: Record<string, number>;
  };
  providers: {
    total: number;
    internal: number;
    external: number;
  };
  accreditations: {
    total: number;
    draft: number;
    submitted: number;
    approved: number;
    pending: number;
  };
}

interface DashboardProps {
  stats: DashboardStats;
  user_role: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const MetricCard: React.FC<{
  title: string;
  value: number;
  icon: React.ReactNode;
  description: string;
  gradient: string;
  progress?: number;
}> = ({ title, value, icon, description, gradient, progress }) => (
  <Card className="group relative overflow-hidden border-0 shadow-lg hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
    <div className={`absolute inset-0 bg-gradient-to-br ${gradient} opacity-5 group-hover:opacity-10 transition-opacity`} />
    <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
      <CardTitle className="text-sm font-medium text-muted-foreground">{title}</CardTitle>
      <div className={`h-8 w-8 rounded-lg bg-gradient-to-br ${gradient} p-2 text-white shadow-md`}>
        {icon}
      </div>
    </CardHeader>
    <CardContent className="space-y-3">
      <div className="text-3xl font-bold tracking-tight">{value.toLocaleString()}</div>
      <p className="text-xs text-muted-foreground">{description}</p>
      {progress && (
        <div className="space-y-1">
          <Progress value={progress} className="h-2" />
          <p className="text-xs text-muted-foreground">{progress}% del total</p>
        </div>
      )}
    </CardContent>
  </Card>
);

const getRoleDisplayName = (role: string): string => {
  const names: Record<string, string> = {
    'admin': 'Administrador',
    'security_manager': 'Gerente de Seguridad', 
    'area_manager': 'Gerente de Área',
    'provider': 'Proveedor'
  };
  return names[role] || role;
};

const getRoleBadgeClass = (role: string): string => {
  const styles: Record<string, string> = {
    'admin': 'bg-gradient-to-r from-purple-500 to-pink-500 text-white border-0',
    'security_manager': 'bg-gradient-to-r from-blue-500 to-cyan-500 text-white border-0',
    'area_manager': 'bg-gradient-to-r from-green-500 to-emerald-500 text-white border-0',
    'provider': 'bg-gradient-to-r from-orange-500 to-amber-500 text-white border-0'
  };
  return styles[role] || 'bg-gray-500 text-white border-0';
};

export default function Dashboard({ stats, user_role }: DashboardProps) {
    const roleDisplay = getRoleDisplayName(user_role);
    const employeeActiveRate = stats.employees.total ? Math.round((stats.employees.active / stats.employees.total) * 100) : 0;
    const completionRate = stats.accreditations.total ? Math.round((stats.accreditations.approved / stats.accreditations.total) * 100) : 0;
    
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            
            <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50 dark:from-slate-950 dark:via-slate-900 dark:to-slate-950">
                <div className="space-y-8 p-6">
                    {/* Header moderno */}
                    <Card className="border-0 shadow-xl bg-white/80 dark:bg-slate-900/80 backdrop-blur-sm">
                        <CardContent className="p-8">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center space-x-4">
                                    <div className="h-12 w-12 rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 p-3 text-white shadow-lg">
                                        <Activity className="h-6 w-6" />
                                    </div>
                                    <div>
                                        <h1 className="text-3xl font-bold tracking-tight">Dashboard</h1>
                                        <p className="text-muted-foreground">Resumen ejecutivo del sistema de acreditaciones</p>
                                    </div>
                                </div>
                                <Badge className={getRoleBadgeClass(user_role) + ' px-4 py-2 shadow-md'}>
                                    {roleDisplay}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Métricas principales */}
                    <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <MetricCard
                            title="Total Empleados"
                            value={stats.employees.total}
                            icon={<Users className="h-4 w-4" />}
                            description="Personal registrado"
                            gradient="from-blue-500 to-blue-600"
                            progress={employeeActiveRate}
                        />
                        <MetricCard
                            title="Empleados Activos"
                            value={stats.employees.active}
                            icon={<UserCheck className="h-4 w-4" />}
                            description="Personal habilitado"
                            gradient="from-emerald-500 to-emerald-600"
                        />
                        <MetricCard
                            title="Acreditaciones"
                            value={stats.accreditations.total}
                            icon={<FileCheck className="h-4 w-4" />}
                            description="Solicitudes procesadas"
                            gradient="from-purple-500 to-purple-600"
                            progress={completionRate}
                        />
                        <MetricCard
                            title="Credenciales Aprobadas"
                            value={stats.accreditations.approved}
                            icon={<CheckCircle className="h-4 w-4" />}
                            description="Credenciales generadas"
                            gradient="from-green-500 to-green-600"
                        />
                    </div>

                    {/* Empleados por tipo */}
                    {(stats.employees.by_type.internal > 0 || stats.employees.by_type.external > 0) && (
                        <div className="space-y-4">
                            <h2 className="text-xl font-semibold flex items-center space-x-2">
                                <Users className="h-5 w-5" />
                                <span>Personal por Tipo</span>
                            </h2>
                            <div className="grid gap-6 md:grid-cols-2">
                                {stats.employees.by_type.internal > 0 && (
                                    <MetricCard
                                        title="Personal Interno"
                                        value={stats.employees.by_type.internal}
                                        icon={<Building2 className="h-4 w-4" />}
                                        description="Empleados de áreas internas"
                                        gradient="from-indigo-500 to-indigo-600"
                                    />
                                )}
                                {stats.employees.by_type.external > 0 && (
                                    <MetricCard
                                        title="Personal Externo"
                                        value={stats.employees.by_type.external}
                                        icon={<Building2 className="h-4 w-4" />}
                                        description="Empleados de empresas externas"
                                        gradient="from-orange-500 to-orange-600"
                                    />
                                )}
                            </div>
                        </div>
                    )}

                    {/* Proveedores */}
                    {(user_role === 'admin' || user_role === 'security_manager' || user_role === 'area_manager') && (
                        <div className="space-y-4">
                            <h2 className="text-xl font-semibold flex items-center space-x-2">
                                <Building2 className="h-5 w-5" />
                                <span>Proveedores</span>
                            </h2>
                            <div className="grid gap-6 md:grid-cols-3">
                                <MetricCard
                                    title="Total Proveedores"
                                    value={stats.providers.total}
                                    icon={<Building2 className="h-4 w-4" />}
                                    description="Organizaciones registradas"
                                    gradient="from-slate-500 to-slate-600"
                                />
                                <MetricCard
                                    title="Áreas Internas"
                                    value={stats.providers.internal}
                                    icon={<Building2 className="h-4 w-4" />}
                                    description="Departamentos internos"
                                    gradient="from-blue-500 to-blue-600"
                                />
                                <MetricCard
                                    title="Empresas Externas"
                                    value={stats.providers.external}
                                    icon={<Building2 className="h-4 w-4" />}
                                    description="Proveedores externos"
                                    gradient="from-amber-500 to-amber-600"
                                />
                            </div>
                        </div>
                    )}

                    {/* Acreditaciones detalladas */}
                    <div className="space-y-4">
                        <h2 className="text-xl font-semibold flex items-center space-x-2">
                            <FileCheck className="h-5 w-5" />
                            <span>Estado de Acreditaciones</span>
                        </h2>
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <MetricCard
                                title="Borradores"
                                value={stats.accreditations.draft}
                                icon={<FileText className="h-4 w-4" />}
                                description="Pendientes de envío"
                                gradient="from-yellow-500 to-amber-500"
                            />
                            <MetricCard
                                title="En Revisión"
                                value={stats.accreditations.submitted}
                                icon={<Clock className="h-4 w-4" />}
                                description="Solicitudes enviadas"
                                gradient="from-blue-500 to-indigo-500"
                            />
                            <MetricCard
                                title="Pendientes"
                                value={stats.accreditations.pending}
                                icon={<AlertCircle className="h-4 w-4" />}
                                description="Requieren atención"
                                gradient="from-red-500 to-pink-500"
                            />
                            <Card className="group relative overflow-hidden border-0 shadow-lg hover:shadow-xl transition-all duration-300">
                                <div className="absolute inset-0 bg-gradient-to-br from-emerald-500/10 to-green-500/10 group-hover:from-emerald-500/20 group-hover:to-green-500/20 transition-all" />
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground flex items-center justify-between">
                                        Tasa de Éxito
                                        <TrendingUp className="h-4 w-4 text-emerald-500" />
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="text-3xl font-bold">{completionRate}%</div>
                                    <div className="space-y-2">
                                        <Progress value={completionRate} className="h-3" />
                                        <p className="text-xs text-muted-foreground">
                                            {stats.accreditations.approved} de {stats.accreditations.total} aprobadas
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    {/* Banner informativo según rol */}
                    {user_role === 'provider' && (
                        <Card className="border-l-4 border-l-blue-500 bg-blue-50/50 dark:bg-blue-950/20">
                            <CardContent className="p-6">
                                <div className="flex items-start space-x-3">
                                    <Building2 className="h-5 w-5 text-blue-600 mt-0.5" />
                                    <div>
                                        <h3 className="font-medium text-blue-900 dark:text-blue-100">
                                            Vista de Proveedor
                                        </h3>
                                        <p className="text-sm text-blue-700 dark:text-blue-300 mt-1">
                                            Las estadísticas mostradas corresponden únicamente a su organización.
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {user_role === 'area_manager' && (
                        <Card className="border-l-4 border-l-green-500 bg-green-50/50 dark:bg-green-950/20">
                            <CardContent className="p-6">
                                <div className="flex items-start space-x-3">
                                    <Users className="h-5 w-5 text-green-600 mt-0.5" />
                                    <div>
                                        <h3 className="font-medium text-green-900 dark:text-green-100">
                                            Vista de Gerente de Área
                                        </h3>
                                        <p className="text-sm text-green-700 dark:text-green-300 mt-1">
                                            Los datos incluyen todos los proveedores y empleados de su área gestionada.
                                        </p>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
