import React, { useState } from "react";
import { FormField, FormItem, FormLabel, FormControl, FormMessage, FormDescription } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { FormSection } from "@/components/base-form/form-section";
import { FormTabContainer, FormTab } from "@/components/base-form";
import { BaseFormOptions, useFormContext } from "@/components/base-form/base-form";
import { ShieldIcon, KeyIcon, TagIcon, Info, Lock } from "lucide-react";
import { Badge } from "@/components/ui/badge";
import { toast } from "sonner";
import { Card } from "@/components/ui/card";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { Role, RoleFormData } from "./schema";
import { isProtectedRole } from "./utils";
import { getRoleLabel } from "@/utils/translations/role-labels";
import { usePermissions } from "@/hooks/usePermissions";
import { PermissionsSelector } from "./permissions-selector";

interface RoleFormProps {
  options: BaseFormOptions<RoleFormData>;
  permissions?: { 
    name: string;
    module: string;
    description?: string;
  }[];
}

export function RoleForm({ options, permissions = [] }: RoleFormProps) {
  // Obtener el formulario del contexto
  const { form, isSubmitting } = useFormContext<RoleFormData>();
  const permissionsManager = usePermissions();
  
  // Determinar si es edición o creación
  const isEditing = options.isEdit;
  const isProtected = isEditing && isProtectedRole(form.getValues('name'));

  return (
    <>
      <div className="mb-5 flex items-center gap-2">
        <Badge className={isEditing 
          ? "px-2 py-1 bg-blue-100 text-blue-800" 
          : "px-2 py-1 bg-emerald-100 text-emerald-800"
        }>
          {isEditing ? "Modo edición" : "Nuevo rol"}
        </Badge>
        
        {isProtected && (
          <Badge variant="destructive" className="px-2 py-1">
            Rol protegido del sistema
          </Badge>
        )}
      </div>
      
      <FormTabContainer defaultValue="general">
        <FormTab value="general" label="Información General" icon={<KeyIcon className="h-4 w-4" />}>
          <FormSection 
            title="Datos del Rol" 
            description="Defina el nombre y características básicas del rol"
          >
            <FormField
              control={form.control}
              name="name"
              render={({ field }) => (
                <FormItem className="space-y-2">
                  <div className="flex items-center gap-1">
                    <FormLabel className="text-base font-medium">{getRoleLabel('name')}</FormLabel>
                    <TooltipProvider>
                      <Tooltip>
                        <TooltipTrigger asChild>
                          <Info className="h-4 w-4 text-muted-foreground cursor-help" />
                        </TooltipTrigger>
                        <TooltipContent sideOffset={5}>
                          <p className="w-[220px] text-sm">Identificador único del rol que se utilizará en todo el sistema</p>
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                  </div>
                  <FormControl>
                    <div className="relative">
                      <TagIcon className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                      <Input 
                        {...field} 
                        placeholder="Ej: Editor, Supervisor, etc." 
                        disabled={isProtected || isSubmitting}
                        className="pl-9 transition-all focus-within:ring-2 focus-within:ring-primary/20 border-input/50 hover:border-input"
                      />
                      {isProtected && (
                        <Lock className="absolute right-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-destructive" />
                      )}
                    </div>
                  </FormControl>
                  <FormDescription>
                    El nombre debe ser único y descriptivo de las funciones que tendrá este rol.
                    {isProtected && " Este rol está protegido y no puede cambiar su nombre."}
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />
          </FormSection>
        </FormTab>
        
        <FormTab value="permissions" label="Permisos" icon={<ShieldIcon className="h-4 w-4" />}>
          <FormSection 
            title="Permisos del rol" 
            description="Seleccione los permisos que tendrá este rol en el sistema"
            className="mb-4"
          >
            <Card className="p-4">
              <FormField
                control={form.control}
                name="permissions"
                render={({ field }) => (
                  <FormItem>
                    <FormControl>
                      <PermissionsSelector
                        permissions={permissions}
                        selectedPermissions={field.value || []}
                        onChange={field.onChange}
                        isReadOnly={isProtected}
                      />
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </Card>
          </FormSection>
        </FormTab>
      </FormTabContainer>
    </>
  );
}
