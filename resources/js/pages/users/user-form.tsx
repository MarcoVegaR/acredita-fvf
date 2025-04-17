import React from "react";
import { FormField, FormItem, FormLabel, FormControl, FormMessage, FormDescription } from "@/components/ui/form";
import { Input } from "@/components/ui/input";
import { Checkbox } from "@/components/ui/checkbox";
import { FormSection } from "@/components/base-form/form-section";
import { FormTabContainer, FormTab } from "@/components/base-form";
import { Button } from "@/components/ui/button";
import { User } from "./schema";
import { Badge } from "@/components/ui/badge";
import { Separator } from "@/components/ui/separator";
import { 
  UserIcon, 
  KeyIcon, 
  ShieldIcon, 
  EyeIcon, 
  EyeOffIcon, 
  Mail, 
  Info, 
  AlertCircle, 
  Loader2,
  UserCheck,
  UserPlus,
  AtSign,
  CheckCircle,
  Shield
} from "lucide-react";
import { BaseFormOptions, useFormContext } from "@/components/base-form/base-form";
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from "@/components/ui/tooltip";
import { useEffect, useState } from "react";
import { toast } from "sonner";

interface RoleInfo {
  id: number;
  name: string;
  nameshow?: string;
  description?: string;
  permissions?: string[];
}

export function UserForm({ options }: { options: BaseFormOptions<User> }) {
  // Obtener el formulario del contexto
  const { form, isSubmitting } = useFormContext<User>();
  
  // Estados para la UI
  const [availableRoles, setAvailableRoles] = useState<RoleInfo[]>([]);
  const [loadingRoles, setLoadingRoles] = useState<boolean>(true);
  const [passwordVisible, setPasswordVisible] = useState<boolean>(false);
  const [confirmPasswordVisible, setConfirmPasswordVisible] = useState<boolean>(false);
  const [generatedPassword, setGeneratedPassword] = useState<string>("");

  // Determinar si es formulario de edición o creación
  const isEditing = options.isEdit;
  
  // Cargar roles disponibles desde la API
  useEffect(() => {
    const fetchRoles = async () => {
      try {
        setLoadingRoles(true);
        // En un entorno real, esto sería una llamada a la API
        // Por ejemplo: const response = await fetch('/api/roles');
        
        // Simular carga desde la API con los datos del seeder
        // En producción esto debería venir del backend
        setTimeout(() => {
          const roles: RoleInfo[] = [
            { id: 1, name: "admin", nameshow: "Administrador", description: "Acceso completo a todas las funcionalidades del sistema" },
            { id: 2, name: "editor", nameshow: "Editor", description: "Puede ver y editar usuarios, pero no crear o eliminar" },
            { id: 3, name: "viewer", nameshow: "Visualizador", description: "Solo puede ver información, sin capacidad de modificación" },
            { id: 4, name: "user", nameshow: "Usuario", description: "Acceso básico con permisos mínimos" }
          ];
          setAvailableRoles(roles);
          setLoadingRoles(false);
        }, 500);
      } catch (error) {
        console.error("Error al cargar los roles:", error);
        toast.error("No se pudieron cargar los roles disponibles");
        setLoadingRoles(false);
      }
    };
    
    fetchRoles();
  }, []);
  
  // Obtener la descripción del rol
  const getRoleDescription = (role: RoleInfo): string => {
    return role.description || `Rol ${role.nameshow || role.name}`;
  };
  
  // Generar una contraseña aleatoria segura
  const generateSecurePassword = () => {
    const lowercase = "abcdefghijklmnopqrstuvwxyz";
    const uppercase = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
    const numbers = "0123456789";
    const symbols = "!@#$%^&*()_+{}[]|:;<>,.?/~";
    
    // Asegurar al menos un carácter de cada tipo
    let password = "";
    password += lowercase.charAt(Math.floor(Math.random() * lowercase.length));
    password += uppercase.charAt(Math.floor(Math.random() * uppercase.length));
    password += numbers.charAt(Math.floor(Math.random() * numbers.length));
    password += symbols.charAt(Math.floor(Math.random() * symbols.length));
    
    // Completar el resto de la contraseña
    const allChars = lowercase + uppercase + numbers + symbols;
    for (let i = 0; i < 8; i++) {
      password += allChars.charAt(Math.floor(Math.random() * allChars.length));
    }
    
    // Mezclar los caracteres
    password = password.split('').sort(() => 0.5 - Math.random()).join('');
    
    // Guardar la contraseña en el estado de React
    setGeneratedPassword(password);
  };
  
  // Aplicar la contraseña generada a ambos campos
  const applyGeneratedPassword = () => {
    if (generatedPassword) {
      form.setValue("password", generatedPassword);
      form.setValue("password_confirmation", generatedPassword);
      toast.success('Contraseña aplicada a ambos campos', {
        position: 'bottom-right',
        duration: 2000,
        icon: '✓'
      });
    }
  };

  return (
    <div className="user-form-container">
      <div className="mb-5">
        <Badge className={isEditing 
          ? "px-2 py-1 bg-blue-100 text-blue-800 hover:bg-blue-100" 
          : "px-2 py-1 bg-emerald-100 text-emerald-800 hover:bg-emerald-100"
        }>
          {isEditing 
            ? <UserCheck className="h-3.5 w-3.5 mr-1" /> 
            : <UserPlus className="h-3.5 w-3.5 mr-1" />
          }
          {isEditing ? 'Edición de usuario' : 'Nuevo usuario'}
        </Badge>
      </div>
      
      <FormTabContainer defaultValue={options.defaultTab || "general"} className="shadow-sm rounded-lg border bg-card overflow-hidden">
        <FormTab value="general" label="Información General" icon={<UserIcon className="h-4 w-4" />}>
            <FormSection 
              title="Datos básicos" 
              description="Información principal del usuario" 
              columns={2}
            >
              <FormField
                control={form.control}
                name="name"
                render={({ field }) => (
                  <FormItem className="space-y-2">
                    <div className="flex items-center gap-1">
                      <FormLabel className="text-base font-medium">Nombre completo</FormLabel>
                      <TooltipProvider>
                        <Tooltip>
                          <TooltipTrigger asChild>
                            <Info className="h-4 w-4 text-muted-foreground cursor-help" />
                          </TooltipTrigger>
                          <TooltipContent sideOffset={5} className="bg-popover border rounded-md shadow-md">
                            <p className="w-[200px] text-sm">Nombre completo del usuario como aparecerá en el sistema</p>
                          </TooltipContent>
                        </Tooltip>
                      </TooltipProvider>
                    </div>
                    <FormControl>
                      <div className="relative">
                        <UserCheck className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input 
                          placeholder="Nombre y apellido" 
                          {...field} 
                          autoComplete="name"
                          className="pl-9 transition-all focus-within:ring-2 focus-within:ring-primary/20 border-input/50 hover:border-input"
                        />
                      </div>
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
          
              <FormField
                control={form.control}
                name="email"
                render={({ field }) => (
                  <FormItem className="space-y-2">
                    <div className="flex items-center gap-1">
                      <FormLabel className="text-base font-medium">Correo electrónico</FormLabel>
                      <TooltipProvider>
                        <Tooltip>
                          <TooltipTrigger asChild>
                            <Mail className="h-4 w-4 text-muted-foreground cursor-help" />
                          </TooltipTrigger>
                          <TooltipContent sideOffset={5} className="bg-popover border rounded-md shadow-md">
                            <p className="w-[220px] text-sm leading-relaxed">Debe ser único en el sistema y será usado para iniciar sesión</p>
                          </TooltipContent>
                        </Tooltip>
                      </TooltipProvider>
                    </div>
                    <FormControl>
                      <div className="relative">
                        <AtSign className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input 
                          type="email" 
                          placeholder="correo@ejemplo.com" 
                          {...field} 
                          autoComplete="email"
                          className="pl-9 transition-all focus-within:ring-2 focus-within:ring-primary/20 border-input/50 hover:border-input"
                        />
                      </div>
                    </FormControl>
                    <FormMessage />
                  </FormItem>
                )}
              />
              
              <div className="col-span-2">
                <Separator className="my-4" />
              </div>
              
              <FormField
                control={form.control}
                name="active"
                render={({ field }) => (
                  <FormItem className="flex flex-row items-start space-x-3 space-y-0 rounded-lg border border-input/50 p-4 hover:border-input transition-colors shadow-sm bg-background/50 col-span-2">
                    <FormControl>
                      <Checkbox
                        checked={field.value}
                        onCheckedChange={field.onChange}
                        className="data-[state=checked]:bg-primary data-[state=checked]:text-primary-foreground"
                      />
                    </FormControl>
                    <div className="space-y-1.5 leading-none">
                      <div className="flex items-center">
                        <FormLabel className="text-base font-medium mr-2">Usuario activo</FormLabel>
                        {field.value && (
                          <Badge variant="outline" className="bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-50">
                            <CheckCircle className="h-3 w-3 mr-1" />
                            Activo
                          </Badge>
                        )}
                      </div>
                      <FormDescription className="text-sm">
                        Si está desactivado, el usuario no podrá iniciar sesión en el sistema.
                      </FormDescription>
                    </div>
                  </FormItem>
                )}
              />
            </FormSection>
      </FormTab>
      
      <FormTab value="security" label="Seguridad" icon={<KeyIcon className="h-4 w-4" />}>
        <FormSection 
          title="Contraseña" 
          description={isEditing 
            ? "Dejar en blanco para mantener la contraseña actual" 
            : "Establezca una contraseña segura para el usuario"
          }
        >
          <div className="mb-6">
            <div className="bg-muted/30 rounded-lg border border-dashed p-4 relative overflow-hidden transition-all">
              <div className="flex flex-col gap-3">
                <div className="flex items-center justify-between">
                  <h4 className="font-medium text-sm flex items-center gap-1.5">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-primary">
                      <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                      <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    <span className="text-primary font-semibold">
                      {isEditing ? 'Cambiar contraseña' : 'Generador de contraseñas'}
                    </span>
                  </h4>
                  
                  <div className="flex items-center gap-2">
                    <Button
                      type="button"
                      variant="default"
                      size="sm"
                      className="h-7 px-3 text-xs font-medium rounded-md bg-primary/90 hover:bg-primary shadow-sm"
                      onClick={generateSecurePassword}
                      disabled={isSubmitting}
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="mr-1.5">
                        <path d="M21 2v6h-6"/>
                        <path d="M3 12a9 9 0 0 1 15-6.7L21 8"/>
                        <path d="M3 12a9 9 0 0 0 15 6.7L21 16"/>
                        <path d="M21 22v-6h-6"/>
                      </svg>
                      {isEditing ? 'Nueva contraseña' : 'Generar'}
                    </Button>
                  </div>
                </div>
                
                {isEditing && !form.watch('password') && (
                  <div className="text-xs text-muted-foreground mt-2 bg-yellow-50 p-3 rounded-md border border-yellow-200 flex items-center gap-2 shadow-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-yellow-600 shrink-0">
                      <circle cx="12" cy="12" r="10"/>
                      <line x1="12" x2="12" y1="8" y2="12"/>
                      <line x1="12" x2="12.01" y1="16" y2="16"/>
                    </svg>
                    <span>Solo complete estos campos si desea cambiar la contraseña actual. Dejando en blanco se mantiene la contraseña existente.</span>
                  </div>
                )}
                
                {generatedPassword && (
                  <div className="flex flex-col gap-3 mt-2 bg-primary/5 p-3 rounded-md border border-primary/10">
                    <div className="flex items-center gap-1.5">
                      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-primary">
                        <path d="M20 10c0-5.523-4.477-10-10-10S0 4.477 0 10s4.477 10 10 10 10-4.477 10-10" />
                        <path d="m20 10-9 4.667" />
                        <path d="M10 14.667V22" />
                        <path d="M10 0v14.667" />
                      </svg>
                      <div className="text-xs font-medium text-primary">Contraseña generada:</div>
                    </div>
                    <div className="relative">
                      <div className="bg-background p-3 rounded-md border shadow-sm flex items-center justify-between gap-2">
                        <span className="font-mono text-sm truncate select-all">{generatedPassword}</span>
                        <div className="flex shrink-0 gap-2">
                          <Button 
                            type="button" 
                            variant="outline" 
                            size="sm"
                            className="h-7 px-2 hover:bg-primary/10 hover:text-primary hover:border-primary/30 rounded-md text-xs"
                            onClick={() => {
                              navigator.clipboard.writeText(generatedPassword || '');
                              toast.success('Contraseña copiada al portapapeles', {
                                position: 'bottom-right',
                                duration: 2000
                              });
                            }}
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="mr-1">
                              <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                              <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                            </svg>
                            Copiar
                          </Button>
                          <Button 
                            type="button" 
                            variant="outline" 
                            size="sm"
                            className="h-7 px-2 hover:bg-green-500/10 hover:text-green-600 hover:border-green-200 rounded-md text-xs"
                            onClick={applyGeneratedPassword}
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="mr-1">
                              <path d="M20 6 9 17l-5-5"/>
                            </svg>
                            Aplicar contraseña
                          </Button>
                        </div>
                      </div>
                      <div className="mt-1.5 flex items-center gap-1 text-xs text-muted-foreground">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className="text-green-500">
                          <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                          <path d="m9 11 3 3L22 4" />
                        </svg>
                        <span>Esta contraseña cumple con los estándares de seguridad recomendados</span>
                      </div>
                    </div>
                  </div>
                )}
              </div>
            </div>
          </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <FormField
              control={form.control}
              name="password"
              render={({ field }) => (
                <FormItem className="space-y-2">
                  <div className="flex items-center gap-1">
                    <FormLabel className="text-base font-medium">Contraseña</FormLabel>
                    <TooltipProvider>
                      <Tooltip>
                        <TooltipTrigger asChild>
                          <AlertCircle className="h-4 w-4 text-muted-foreground cursor-help" />
                        </TooltipTrigger>
                        <TooltipContent side="right" className="bg-popover border rounded-md shadow-md">
                          <div className="w-[250px] text-sm space-y-2">
                            <p>La contraseña debe cumplir estos requisitos:</p>
                            <ul className="list-disc pl-4 space-y-1">
                              <li>Al menos 8 caracteres</li>
                              <li>Al menos una letra mayúscula</li>
                              <li>Al menos una letra minúscula</li>
                              <li>Al menos un número</li>
                            </ul>
                          </div>
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                  </div>
                  <FormControl>
                    <div className="relative">
                      <Input 
                        type={passwordVisible ? "text" : "password"} 
                        placeholder={isEditing ? "••••••••" : "Nueva contraseña"} 
                        {...field} 
                        value={field.value || ""}
                        autoComplete={isEditing ? "new-password" : "new-password"}
                        className="transition-all focus-within:ring-2 focus-within:ring-primary/20 pr-10"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="absolute right-0 top-0 h-full px-3 text-muted-foreground hover:text-foreground"
                        onClick={() => setPasswordVisible(!passwordVisible)}
                      >
                        {passwordVisible ? (
                          <EyeOffIcon className="h-4 w-4" />
                        ) : (
                          <EyeIcon className="h-4 w-4" />
                        )}
                      </Button>
                    </div>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
            
            <FormField
              control={form.control}
              name="password_confirmation"
              render={({ field }) => (
                <FormItem className="space-y-2">
                  <FormLabel className="text-base font-medium">Confirmar contraseña</FormLabel>
                  <FormControl>
                    <div className="relative">
                      <Input 
                        type={confirmPasswordVisible ? "text" : "password"} 
                        placeholder="Confirmar contraseña" 
                        {...field} 
                        value={field.value || ""}
                        autoComplete="new-password"
                        className="transition-all focus-within:ring-2 focus-within:ring-primary/20 pr-10"
                      />
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="absolute right-0 top-0 h-full px-3 text-muted-foreground hover:text-foreground"
                        onClick={() => setConfirmPasswordVisible(!confirmPasswordVisible)}
                      >
                        {confirmPasswordVisible ? (
                          <EyeOffIcon className="h-4 w-4" />
                        ) : (
                          <EyeIcon className="h-4 w-4" />
                        )}
                      </Button>
                    </div>
                  </FormControl>
                  <FormMessage />
                </FormItem>
              )}
            />
          </div>
          
          <FormDescription className="mt-5 text-sm text-muted-foreground flex items-center bg-muted/30 p-3 rounded-md border border-muted">
            <Info className="h-4 w-4 mr-2 text-primary shrink-0" />
            <span>Utilice contraseñas fuertes y únicas para cada usuario. Para mayor seguridad, considere habilitar la autenticación de dos factores.</span>
          </FormDescription>
        </FormSection>
      </FormTab>
      
      <FormTab value="roles" label="Roles y permisos" icon={<ShieldIcon className="h-4 w-4" />}>
        <FormSection title="Asignación de roles" description="Asigne uno o más roles para determinar qué puede hacer este usuario en el sistema">
          <FormField
            control={form.control}
            name="roles"
            render={() => (
              <FormItem>
                <div className="mb-4">
                  <div className="flex items-center gap-1.5">
                    <FormLabel className="text-base font-medium">Roles del usuario</FormLabel>
                    <TooltipProvider>
                      <Tooltip>
                        <TooltipTrigger asChild>
                          <Shield className="h-4 w-4 text-muted-foreground cursor-help" />
                        </TooltipTrigger>
                        <TooltipContent sideOffset={5} className="bg-popover border rounded-md shadow-md">
                          <p className="w-[220px] text-sm leading-relaxed">Seleccione uno o más roles para determinar los permisos del usuario</p>
                        </TooltipContent>
                      </Tooltip>
                    </TooltipProvider>
                  </div>
                  <FormDescription>
                    Los roles determinan qué permisos tendrá el usuario en el sistema
                  </FormDescription>
                </div>
                
                {loadingRoles ? (
                  <div className="flex justify-center items-center py-8 border rounded-lg border-dashed">
                    <Loader2 className="h-6 w-6 animate-spin text-primary mr-2" />
                    <span className="text-muted-foreground">Cargando roles disponibles...</span>
                  </div>
                ) : (
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {availableRoles.length === 0 ? (
                      <div className="col-span-2 p-6 text-center text-muted-foreground border rounded-md bg-muted/10">
                        No hay roles disponibles en el sistema
                      </div>
                    ) : (
                      availableRoles.map((role) => (
                        <FormField
                          key={role.name}
                          control={form.control}
                          name="roles"
                          render={({ field }) => {
                            const isChecked = field.value?.includes(role.name);
                            return (
                              <FormItem
                                key={role.name}
                                className={`flex flex-row items-start space-x-3 space-y-0 rounded-lg border p-4 shadow-sm transition-all ${isChecked ? 'bg-primary/5 border-primary/20' : 'hover:bg-muted/10'}`}
                              >
                                <FormControl>
                                  <Checkbox
                                    checked={isChecked}
                                    onCheckedChange={(checked) => {
                                      return checked
                                        ? field.onChange([...(field.value || []), role.name])
                                        : field.onChange(
                                            field.value?.filter(
                                              (value) => value !== role.name
                                            )
                                          )
                                    }}
                                    className={isChecked ? 'data-[state=checked]:bg-primary' : ''}
                                  />
                                </FormControl>
                                <div className="space-y-1 leading-none">
                                  <div className="flex items-center gap-2">
                                    <FormLabel className="text-base font-medium">
                                      {role.nameshow || role.name.charAt(0).toUpperCase() + role.name.slice(1)}
                                    </FormLabel>
                                    {isChecked && (
                                      <Badge variant="outline" className="bg-primary/10 text-primary border-primary/20 hover:bg-primary/10">
                                        Asignado
                                      </Badge>
                                    )}
                                  </div>
                                  <FormDescription>
                                    {getRoleDescription(role)}
                                  </FormDescription>
                                </div>
                              </FormItem>
                            )
                          }}
                        />
                      ))
                    )}
                  </div>
                )}
                <FormMessage />
              </FormItem>
            )}
          />
        </FormSection>
      </FormTab>
    </FormTabContainer>
  </div>
  );
}
