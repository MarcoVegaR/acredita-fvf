import React from "react";
import { Head } from "@inertiajs/react";
import { PageHeader } from "@/components/ui/page-header";
import { Card, CardHeader, CardTitle, CardDescription, CardContent } from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { Badge } from "@/components/ui/badge";
import { ShieldIcon, UserIcon, EditIcon, ArrowLeftIcon } from "lucide-react";
import { Breadcrumb, BreadcrumbItem, BreadcrumbLink, BreadcrumbList, BreadcrumbSeparator } from "@/components/ui/breadcrumb";
import { Role } from "./schema";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { formatPermissionDescription, groupPermissionsByModule, isProtectedRole } from "./utils";
import { getModuleLabel } from "@/utils/translations/role-labels";
import { usePermissions } from "@/hooks/usePermissions";

interface RoleDetailsProps {
  role: Role;
  rolePermissions: string[];
  usersWithRole?: { 
    id: number;
    name: string;
    email: string;
  }[];
}

export default function Show({ role, rolePermissions = [], usersWithRole = [] }: RoleDetailsProps) {
  const { can } = usePermissions();
  const isProtected = isProtectedRole(role.name);
  
  // Agrupar permisos por módulo para mejor visualización
  const permissionsByModule = groupPermissionsByModule(rolePermissions);
  
  return (
    <>
      <Head title={`Rol: ${role.name}`} />
      
      <div className="space-y-6">
        <Breadcrumb>
          <BreadcrumbList>
            <BreadcrumbItem>
              <BreadcrumbLink href="/dashboard">Dashboard</BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbSeparator />
            <BreadcrumbItem>
              <BreadcrumbLink href="/roles">Roles</BreadcrumbLink>
            </BreadcrumbItem>
            <BreadcrumbSeparator />
            <BreadcrumbItem>
              <BreadcrumbLink>{role.name}</BreadcrumbLink>
            </BreadcrumbItem>
          </BreadcrumbList>
        </Breadcrumb>
        
        <PageHeader
          title={`Rol: ${role.name}`}
          subtitle="Información detallada del rol y sus permisos"
        >
          <div className="flex items-center gap-2">
            <Button size="sm" variant="outline" asChild>
              <a href="/roles">
                <ArrowLeftIcon className="h-4 w-4 mr-1" />
                Volver a roles
              </a>
            </Button>
            
            {can('roles.edit') && !isProtected && (
              <Button size="sm" asChild>
                <a href={`/roles/${role.id}/edit`}>
                  <EditIcon className="h-4 w-4 mr-1" />
                  Editar
                </a>
              </Button>
            )}
          </div>
        </PageHeader>
        
        <div className="flex items-center gap-2 mb-4">
          <Badge variant="outline" className="px-2 py-1">
            Guard: {role.guard_name}
          </Badge>
          
          {isProtected && (
            <Badge variant="destructive" className="px-2 py-1">
              Rol protegido del sistema
            </Badge>
          )}
          
          <Badge variant="outline" className="px-2 py-1">
            {rolePermissions.length} permisos asignados
          </Badge>
        </div>
        
        <Tabs defaultValue="permissions">
          <TabsList>
            <TabsTrigger value="permissions">
              <ShieldIcon className="h-4 w-4 mr-1" /> Permisos
            </TabsTrigger>
            <TabsTrigger value="users">
              <UserIcon className="h-4 w-4 mr-1" /> Usuarios ({usersWithRole.length})
            </TabsTrigger>
          </TabsList>
          
          <TabsContent value="permissions" className="mt-6">
            {Object.keys(permissionsByModule).length === 0 ? (
              <Card>
                <CardContent className="pt-6">
                  <div className="text-center py-6">
                    <p className="text-muted-foreground">Este rol no tiene permisos asignados.</p>
                  </div>
                </CardContent>
              </Card>
            ) : (
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {Object.entries(permissionsByModule).map(([module, modulePermissions]) => (
                  <Card key={module}>
                    <CardHeader className="pb-2">
                      <CardTitle className="text-base">
                        Módulo: {getModuleLabel(module)}
                      </CardTitle>
                      <CardDescription>
                        {modulePermissions.length} permisos
                      </CardDescription>
                    </CardHeader>
                    <CardContent>
                      <ul className="grid grid-cols-1 gap-1">
                        {modulePermissions.map(permission => (
                          <li key={permission} className="py-1 px-2 bg-muted rounded-sm text-sm">
                            {formatPermissionDescription(permission)}
                          </li>
                        ))}
                      </ul>
                    </CardContent>
                  </Card>
                ))}
              </div>
            )}
          </TabsContent>
          
          <TabsContent value="users" className="mt-6">
            <Card>
              <CardHeader>
                <CardTitle className="text-base">Usuarios con este rol</CardTitle>
                <CardDescription>
                  {usersWithRole.length} usuarios tienen asignado el rol "{role.name}"
                </CardDescription>
              </CardHeader>
              <CardContent>
                {usersWithRole.length === 0 ? (
                  <div className="text-center py-6">
                    <p className="text-muted-foreground">No hay usuarios con este rol asignado.</p>
                  </div>
                ) : (
                  <ul className="divide-y">
                    {usersWithRole.map(user => (
                      <li key={user.id} className="py-2">
                        <div className="flex justify-between items-center">
                          <div>
                            <p className="font-medium">{user.name}</p>
                            <p className="text-sm text-muted-foreground">{user.email}</p>
                          </div>
                          {can('users.edit') && (
                            <Button variant="outline" size="sm" asChild>
                              <a href={`/users/${user.id}/edit`}>Ver usuario</a>
                            </Button>
                          )}
                        </div>
                      </li>
                    ))}
                  </ul>
                )}
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </div>
    </>
  );
}
