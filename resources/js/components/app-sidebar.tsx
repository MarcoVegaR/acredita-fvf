import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { LayoutGrid, Settings, Users, FileImage, Building, Truck, UserRound } from 'lucide-react';
import AppLogo from './app-logo';
import { usePermissions } from '@/hooks/use-permissions';

// Define los elementos de navegación con sus permisos requeridos
const mainNavItems: (NavItem & { permission?: string })[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
        // No requiere permiso especial
    },
    {
        title: 'Usuarios',
        href: '/users',
        icon: Users,
        permission: 'users.index', // Requiere permiso para ver listado de usuarios
    },
    {
        title: 'Áreas',
        href: '/areas',
        icon: Building,
        permission: 'areas.index', // Requiere permiso para ver listado de áreas
    },
    {
        title: 'Proveedores',
        href: '/providers',
        icon: Truck,
        permission: 'provider.view', // Requiere permiso para ver listado de proveedores
    },
    {
        title: 'Empleados',
        href: '/employees',
        icon: UserRound, // Usamos un icono específico para Empleados
        permission: 'employee.view', // Requiere permiso para ver listado de empleados
    },
    {
        title: 'Roles',
        href: '/roles',
        icon: Settings,
        permission: 'roles.index', // Requiere permiso para ver listado de roles
    },
    {
        title: 'Plantillas',
        href: '/templates',
        icon: FileImage,
        permission: 'templates.index', // Requiere permiso para ver listado de plantillas
    },
];

const footerNavItems: NavItem[] = [
    // Items comentados por no ser necesarios
    /*{
        title: 'Repository',
        href: 'https://github.com/laravel/react-starter-kit',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits',
        icon: BookOpen,
    },*/
];

export function AppSidebar() {
    // Utilizamos el hook personalizado para gestionar permisos
    const { filterByPermission } = usePermissions();
    
    // Filtra los elementos de navegación basados en permisos
    const filteredNavItems = filterByPermission(mainNavItems);
    
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={filteredNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
