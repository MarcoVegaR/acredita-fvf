# Patrón Base Index

Este documento proporciona una guía sobre cómo utilizar el patrón Base Index para crear rápidamente vistas de listado de datos en tu aplicación Laravel con Inertia.js y React. El sistema incluye traducciones centralizadas, tarjetas de estadísticas, acciones de fila avanzadas y seguridad de tipos mejorada.

## Índice

1. [Introducción](#introducción)
2. [Componentes disponibles](#componentes-disponibles)
3. [Métodos de implementación](#métodos-de-implementación)
   - [Usando el componente BaseIndexPage (recomendado)](#usando-el-componente-baseindexpage-recomendado)
   - [Creación manual de una vista de índice](#creación-manual-de-una-vista-de-índice)
4. [Configuración avanzada](#configuración-avanzada)
   - [Traducciones](#traducciones)
   - [Tarjetas de estadísticas](#tarjetas-de-estadísticas)
   - [Paginación](#paginación)
   - [Ordenamiento](#ordenamiento)
   - [Filtrado](#filtrado)
   - [Exportación](#exportación)
   - [Acciones de fila](#acciones-de-fila)
   - [Sistema de permisos](#sistema-de-permisos)
5. [Integración con Backend](#integración-con-backend)
6. [Notificaciones y mensajes flash](#notificaciones-y-mensajes-flash)
7. [Seguridad de tipos](#seguridad-de-tipos)
8. [Sistema de permisos](#sistema-de-permisos)
9. [Ejemplos completos](#ejemplos-completos)
10. [Solución de problemas comunes](#solución-de-problemas-comunes)

## Introducción

El patrón Base Index proporciona una estructura reutilizable para crear vistas de listado de datos con funcionalidades avanzadas como ordenamiento, filtrado, paginación, exportación y gestión de acciones por fila. Este patrón está construido sobre TanStack Table (React Table) y componentes UI de ShadCN.

Características principales:
- **Sistema centralizado de traducciones** para etiquetas de columnas
- **Tarjetas de estadísticas** para mostrar métricas importantes
- **Interfaz visual mejorada** con diseño responsive y jerarquía visual clara
- **Tipado seguro** con TypeScript para prevenir errores
- **Acciones de fila** con confirmación integrada mediante AlertDialog
- **Control de acceso granular** basado en permisos de usuario para todas las funcionalidades

## Componentes disponibles

El patrón Base Index incluye los siguientes componentes:

- `BaseIndexPage`: Componente de alto nivel que facilita la creación de páginas de índice sin duplicación de código.
- `DataTable`: Componente principal que orquesta todas las funcionalidades.
- `DataTableToolbar`: Barra de herramientas con búsqueda global, filtros y opciones de tabla.
- `FilterToolbar`: Componente flexible para filtrado avanzado de datos, integrado en la barra de herramientas.
- `DataTablePagination`: Controles de paginación para navegación.
- `DataTableViewOptions`: Selector de visibilidad de columnas.
- `DataTableExport`: Opciones de exportación (Excel, CSV, imprimir, copiar).
- `DataTableRowActions`: Acciones por fila (ver, editar, eliminar, personalizadas) con confirmación integrada.
- `StatisticCards`: Tarjetas con estadísticas relevantes al módulo.
- `Utilidades de traducción`: Sistema centralizado para gestionar etiquetas de columnas.
- `Integración con Spatie Permissions`: Control de acceso basado en permisos para todas las acciones usando el hook `usePermissions`.

## Métodos de implementación

### Usando el componente BaseIndexPage (recomendado)

El método más eficiente para crear vistas de índice es utilizando el componente `BaseIndexPage`, que encapsula toda la lógica común y reduce significativamente la cantidad de código repetitivo.

#### Paso 1: Definir las columnas

```tsx
// columns.tsx
import { ColumnDef } from "@tanstack/react-table";
import { Button } from "@/components/ui/button";
import { ArrowUpDown } from "lucide-react";

// Al extender de Entity, obtenemos compatibilidad completa con BaseIndexPage
export interface User extends Entity {
  id: number;
  name: string;
  email: string;
  email_verified_at: string | null;
  created_at: string;
  // Necesario para cumplir con la interfaz Entity
  [key: string]: unknown;
}

export const columns: ColumnDef<User>[] = [
  {
    accessorKey: "id",
    header: ({ column }) => (
      <Button
        variant="ghost"
        onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
        className="font-semibold"
      >
        ID
        <ArrowUpDown className="ml-2 h-4 w-4" />
      </Button>
    ),
    cell: ({ row }) => <div className="font-medium">{row.getValue("id")}</div>,
    enableSorting: true,
  },
  // Otras columnas...
];
```

#### Paso 2: Crear el componente de índice

```tsx
// index.tsx
import React from "react";
import { BaseIndexPage } from "@/components/base-index/base-index-page";
import { columns, type User } from "./columns";

interface UsersIndexProps {
  users: {
    data: User[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
  filters?: {
    search?: string;
    sort?: string;
    order?: "asc" | "desc";
    page?: number;
    per_page?: number;
  };
}

export default function Index({ users, filters = {} }: UsersIndexProps) {
  // Configuración centralizada para el índice de usuarios
  const indexOptions = {
    title: "Gestión de Usuarios",
    subtitle: "Administre todos los usuarios del sistema", // Subtítulo descriptivo
    endpoint: "/users",
    moduleName: "users", // Identificador para traducciones de columnas
    breadcrumbs: [
      { title: "Dashboard", href: "/dashboard" },
      { title: "Usuarios", href: "/users" },
    ],
    // Tarjetas de estadísticas para mostrar métricas importantes
    stats: [
      { value: 125, label: "Total Usuarios", icon: "users", color: "blue" },
      { value: 38, label: "Nuevos este mes", icon: "user-plus", color: "green" },
      { value: 8, label: "Administradores", icon: "shield", color: "amber" },
    ],
    columns: columns,
    // Define las columnas que se pueden buscar globalmente
    searchableColumns: ["name", "email"],
    // Placeholder personalizado para el campo de búsqueda
    searchPlaceholder: "Buscar por nombre o correo",
    filterableColumns: ["name", "email"],
    defaultSorting: [{ id: "id", desc: true }],
    exportOptions: {
      enabled: true,
      fileName: "usuarios",
      exportTypes: ["excel", "csv", "print", "copy"] as ("excel" | "csv" | "print" | "copy")[],
    },
    newButton: {
      show: true,
      label: "Nuevo Usuario",
    },
    // Sistema de permisos para controlar acceso a funcionalidades
    permissions: {
      create: "users.create",
      view: "users.show",
      edit: "users.edit",
      delete: "users.delete",
    },
    // Acciones de fila con confirmación integrada
    rowActions: {
      view: {
        enabled: true,
        label: "Ver detalles",
        permission: "users.show", // Se verificará el permiso específico
      },
      edit: {
        enabled: true,
        label: "Editar",
        permission: "users.edit",
      },
      delete: {
        enabled: true,
        label: "Eliminar",
        permission: "users.delete",
        confirmMessage: (user: User) => `¿Está seguro que desea eliminar al usuario ${user.name}?`,
      },
    },
  };

  return (
    <BaseIndexPage<User> 
      data={users} 
      filters={filters} 
      options={indexOptions} 
    />
  );
}
```

### Creación manual de una vista de índice

### Paso 1: Importar los componentes necesarios

```tsx
import { DataTable } from "@/components/base-index";
import { ColumnDef } from "@tanstack/react-table";
import { Button } from "@/components/ui/button";
import { ArrowUpDown, MoreHorizontal } from "lucide-react";
```

### Paso 2: Definir la interfaz de datos

Define la estructura de tus datos:

```tsx
interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  active: boolean;
  createdAt: string;
}
```

## Sistema de permisos

El componente BaseIndexPage incluye una integración completa con el sistema de permisos de Spatie. Esta integración se gestiona a través del hook personalizado `usePermissions` que proporciona una interfaz unificada para verificar permisos en toda la aplicación.

### Configuración de permisos

Para implementar permisos en tu página de índice, añade la propiedad `permissions` a la configuración:

```tsx
const indexOptions = {
  // Otras opciones...
  permissions: {
    view: "module.index",     // Permiso para ver la lista
    create: "module.create",  // Permiso para crear
    edit: "module.edit",      // Permiso para editar
    delete: "module.delete",  // Permiso para eliminar
  },
};
```

### Permisos en acciones de fila

Cada acción de fila puede tener su propio permiso:

```tsx
rowActions: {
  view: {
    enabled: true,
    label: "Ver detalles",
    permission: "products.show", // Permiso específico para esta acción
  },
  // Otras acciones...
}
```

### Uso del hook usePermissions

Si necesitas verificar permisos en otras partes de tu aplicación:

```tsx
import { usePermissions } from '@/hooks/use-permissions';

function MyComponent() {
  const { hasPermission } = usePermissions();
  
  return (
    <div>
      {hasPermission('users.create') && (
        <Button>Crear Usuario</Button>
      )}
    </div>
  );
}
```

### Filtrado de elementos por permiso

Puedes filtrar arrays de elementos basados en permisos:

```tsx
const { filterByPermission } = usePermissions();

// Filtra elementos de navegación basados en permisos
const visibleMenuItems = filterByPermission(allMenuItems);
```

### Paso 3: Configurar las columnas

Define las columnas de la tabla con sus configuraciones:

```tsx
const columns: ColumnDef<User>[] = [
  {
    accessorKey: "id",
    header: "ID",
    cell: ({ row }) => <div>{row.getValue("id")}</div>,
  },
  {
    accessorKey: "name",
    header: ({ column }) => (
      <Button
        variant="ghost"
        onClick={() => column.toggleSorting(column.getIsSorted() === "asc")}
      >
        Nombre
        <ArrowUpDown className="ml-2 h-4 w-4" />
      </Button>
    ),
    cell: ({ row }) => <div>{row.getValue("name")}</div>,
  },
  {
    accessorKey: "email",
    header: "Email",
    cell: ({ row }) => <div>{row.getValue("email")}</div>,
  },
  {
    accessorKey: "role",
    header: "Rol",
    cell: ({ row }) => <div>{row.getValue("role")}</div>,
  },
  {
    accessorKey: "active",
    header: "Estado",
    cell: ({ row }) => (
      <div>
        {row.getValue("active") ? (
          <span className="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">
            Activo
          </span>
        ) : (
          <span className="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">
            Inactivo
          </span>
        )}
      </div>
    ),
  },
  {
    accessorKey: "createdAt",
    header: "Fecha de creación",
    cell: ({ row }) => (
      <div>{new Date(row.getValue("createdAt")).toLocaleDateString()}</div>
    ),
  },
];
```

### Paso 4: Crear el componente de la vista

```tsx
export default function UsersIndex({ users }) {
  // Implementa las acciones para cada fila
  const renderRowActions = (user: User) => (
    <DataTableRowActions
      row={user}
      actions={{
        view: {
          enabled: true,
          handler: (user) => {
            // Navegar a la vista de detalle
            window.location.href = `/users/${user.id}`;
          },
        },
        edit: {
          enabled: true,
          handler: (user) => {
            // Navegar a la vista de edición
            window.location.href = `/users/${user.id}/edit`;
          },
        },
        delete: {
          enabled: true,
          confirmMessage: `¿Está seguro que desea eliminar el usuario ${user.name}?`,
          handler: (user) => {
            // Llamar a la API para eliminar
            fetch(`/api/users/${user.id}`, {
              method: "DELETE",
              headers: {
                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "",
              },
            }).then(() => {
              // Recargar la página o actualizar la tabla
              window.location.reload();
            });
          },
        },
      }}
    />
  );

  return (
    <div className="container py-6">
      <h1 className="text-2xl font-bold mb-6">Usuarios</h1>
      
      <DataTable
        columns={columns}
        data={users}
        filterableColumns={["name", "email", "role"]}
        defaultSorting={[{ id: "id", desc: true }]}
        renderRowActions={renderRowActions}
        exportOptions={{
          enabled: true,
          fileName: "usuarios",
          exportTypes: ["excel", "csv", "print", "copy"],
        }}
      />
    </div>
  );
}
```

## Configuración avanzada

### Tarjetas de estadísticas

El componente `BaseIndexPage` soporta la visualización de tarjetas de estadísticas en la parte superior de la página para mostrar métricas importantes relacionadas con el módulo:

```tsx
const indexOptions = {
  // Otras opciones...
  stats: [
    { value: 125, label: "Total Usuarios", icon: "users", color: "blue" },
    { value: 38, label: "Nuevos este mes", icon: "user-plus", color: "green" },
    { value: 8, label: "Inactivos", icon: "user-x", color: "red" },
  ],
};
```

Opciones disponibles para cada tarjeta:
- `value`: El valor numérico a mostrar (puede ser formateado automáticamente si es grande)
- `label`: Descripción de la métrica
- `icon`: Nombre del ícono a mostrar (usando Lucide icons)
- `color`: Color de la tarjeta ("blue", "green", "red", "amber", "purple", etc.)
- `trend`: Opcional, para mostrar tendencia ("up" o "down")
- `trendValue`: Opcional, valor del porcentaje de tendencia (ej: "12%")

Las tarjetas se adaptan automáticamente a diferentes tamaños de pantalla.

### Traducciones

El patrón Base Index incluye un sistema de traducciones por módulo para las etiquetas de columnas, lo que permite mantener centralizadas todas las traducciones y reutilizarlas en diferentes partes de la aplicación.

#### Configuración del sistema de traducciones

Las traducciones se almacenan en `/resources/js/utils/translations/column-labels.ts` organizadas por módulo:

```typescript
const columnTranslations: TranslationsMap = {
  // Módulo de usuarios
  users: {
    id: "ID",
    name: "Nombre",
    email: "Correo Electrónico",
    // ... más traducciones
  },
  
  // Módulo de roles
  roles: {
    id: "ID",
    name: "Nombre",
    // ... más traducciones
  },
  
  // Agrega más módulos según sea necesario
};
```

#### Uso en componentes

Para utilizar las traducciones, solo necesitas especificar el parámetro `moduleName` al configurar el componente `BaseIndexPage`:

```tsx
const indexOptions = {
  // ... otras opciones
  moduleName: "users", // Especifica el módulo para traducciones
};

return (
  <BaseIndexPage 
    data={users} 
    filters={filters} 
    options={indexOptions} 
  />
);
```

#### Beneficios

- **Centralización**: Todas las traducciones en un solo lugar
- **Consistencia**: Los mismos nombres en la interfaz y exportaciones
- **Mantenibilidad**: Fácil actualización y localización
- **Flexibilidad**: Soporte para diferentes idiomas o contextos

### Paginación

Por defecto, la paginación se maneja en el lado del cliente. Para usar paginación en el lado del servidor:

```tsx
<DataTable
  columns={columns}
  data={users.data}
  serverSide={{
    totalRecords: users.total,
    pageCount: users.last_page,
    onPaginationChange: ({ pageIndex, pageSize }) => {
      // Aquí implementas la lógica para cargar la página
      window.location.href = `/users?page=${pageIndex + 1}&per_page=${pageSize}`;
    },
  }}
/>
```

### Ordenamiento

Para habilitar el ordenamiento en el lado del servidor:

```tsx
<DataTable
  columns={columns}
  data={users.data}
  serverSide={{
    // Otras opciones...
    onSortingChange: (sorting) => {
      const column = sorting[0]?.id || "id";
      const order = sorting[0]?.desc ? "desc" : "asc";
      window.location.href = `/users?sort=${column}&order=${order}`;
    },
  }}
/>
```

### Filtrado

#### Filtrado Básico Global

Para activar el filtrado global en el lado del servidor:

```tsx
<DataTable
  columns={columns}
  data={users.data}
  serverSide={{
    // Otras opciones...
    onGlobalFilterChange: (filter) => {
      window.location.href = `/users?search=${filter}`;
    },
  }}
/>
```

#### Filtrado Avanzado con FilterToolbar

El componente `FilterToolbar` proporciona una interfaz avanzada y flexible para aplicar múltiples filtros a tus datos. Este componente está integrado en la barra de herramientas principal para una mejor experiencia de usuario.

##### Configuración en BaseIndexPage

```tsx
const indexOptions = {
  // Otras opciones...
  filterConfig: {
    // Filtros de tipo select (desplegables)
    select: [
      {
        id: "role",
        label: "Rol", 
        options: [
          { value: "admin", label: "Administrador" },
          { value: "editor", label: "Editor" },
          { value: "user", label: "Usuario" }
        ]
      }
    ],
    // Filtros booleanos (verdadero/falso)
    boolean: [
      {
        id: "active",
        label: "Estado", 
        trueLabel: "Activos",
        falseLabel: "Inactivos"
      }
    ]
  },
  filterEmptyMessage: "Sin filtros aplicados. Utilice el botón 'Filtrar' para refinar los resultados.",
};
```

##### Implementación en el Controlador Backend

```php
// En tu controlador Laravel
public function index(Request $request)
{
    $query = User::query();
    
    // Aplicar filtros si existen
    if ($request->has('active')) {
        $isActive = filter_var($request->input('active'), FILTER_VALIDATE_BOOLEAN);
        $query->where('active', $isActive);
    }
    
    if ($request->has('role')) {
        $role = $request->input('role');
        $query->whereHas('roles', function($q) use ($role) {
            $q->where('name', $role);
        });
    }
    
    // Resto de la implementación...
    
    // Importante: Incluir los filtros aplicados en la respuesta
    return inertia('Users/Index', [
        'users' => $users,
        'filters' => $request->only(['search', 'sort', 'order', 'per_page', 'active', 'role'])
    ]);
}
```

##### Características del FilterToolbar

- **Integración en la barra de herramientas**: Los filtros aparecen junto a la búsqueda global siguiendo patrones de diseño UI estándar.
- **Indicador visual**: Muestra un contador de filtros activos.
- **Filtros select**: Para opciones predefinidas como categorías, roles, estados, etc.
- **Filtros booleanos**: Para estados binarios (activo/inactivo, publicado/borrador, etc.)
- **Responsive**: Funciona en dispositivos móviles y de escritorio.
- **TypeScript**: Completamente tipado para prevenir errores.
- **Modo compacto**: Optimizado para integrarse en la barra de herramientas principal.

### Exportación

Personaliza las opciones de exportación:

```tsx
<DataTable
  // Otras props...
  exportOptions={{
    enabled: true,
    fileName: "reporte-usuarios",
    exportTypes: ["excel", "csv"], // Limita los tipos disponibles
  }}
/>
```

### Acciones de fila

Puedes personalizar las acciones y añadir acciones personalizadas:

```tsx
<DataTableRowActions
  row={user}
  actions={{
    // Acciones básicas...
    custom: [
      {
        label: "Enviar correo",
        icon: <Mail className="h-4 w-4" />,
        handler: (user) => {
          // Lógica para enviar correo
        },
      },
    ],
  }}
/>
```

## Integración con Backend

### En el controlador Laravel

```php
public function index(Request $request)
{
    $query = User::query();
    
    // Filtrado
    if ($request->has('search')) {
        $search = $request->input('search');
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }
    
    // Ordenamiento
    $sort = $request->input('sort', 'id');
    $order = $request->input('order', 'desc');
    $query->orderBy($sort, $order);
    
    // Paginación
    $perPage = $request->input('per_page', 10);
    $users = $query->paginate($perPage);
    
    return Inertia::render('Users/Index', [
        'users' => $users,
        'filters' => $request->all(),
    ]);
}
```

### En las rutas

```php
Route::get('/users', [UserController::class, 'index'])->name('users.index');
Route::delete('/api/users/{user}', [UserController::class, 'destroy'])->name('api.users.destroy');
```

## Notificaciones y mensajes flash

El sistema incluye integración con notificaciones a través del componente Sonner. Para mostrar notificaciones desde el backend:

```php
// En el controlador
public function store(Request $request)
{
    // ... lógica para guardar

    return redirect()->route('users.index')
        ->with('success', 'Usuario creado correctamente');
}

public function update(Request $request, User $user)
{
    // ... lógica para actualizar

    return redirect()->route('users.index')
        ->with('success', 'Usuario actualizado correctamente');
}

public function destroy(User $user)
{
    // ... lógica para eliminar

    return response()->json(['success' => true])
        ->with('success', 'Usuario eliminado correctamente');
}
```

También puedes mostrar notificaciones directamente desde el frontend:

```tsx
import { toast } from "sonner";

// En alguna función
toast.success("Operación exitosa", {
  description: "Los cambios se guardaron correctamente",
});

toast.error("Error", {
  description: "Ocurrió un problema al procesar la solicitud",
});
```

## Solución de problemas comunes

### Problemas con el sistema de filtros

#### 1. Los filtros no persisten después de navegar

Si los filtros se aplican pero desaparecen después de la navegación o no se mantienen activos visualmente:

1. **Usar URLs relativas para el endpoint**
   ```tsx
   // ✓ CORRECTO: URL relativa
   endpoint: "/roles",
   
   // ✗ INCORRECTO: URL absoluta
   endpoint: "http://localhost:8000/roles",
   endpoint: route('roles.index'), // Genera URL absoluta
   ```

   El componente FilterToolbar está diseñado para funcionar con URLs relativas para la sincronización correcta entre la URL y el estado de los filtros.

2. **Asegurar que el controlador devuelve todos los parámetros de filtro**
   ```php
   // ✓ CORRECTO: Incluir todos los parámetros de filtro personalizados
   return inertia('Roles/Index', [
       'roles' => $roles,
       'filters' => $request->only([
           'search', 'sort', 'order', 'per_page',
           'has_permissions', 'permission_module' // Incluir filtros personalizados
       ])
   ]);
   
   // ✗ INCORRECTO: Omitir parámetros de filtro personalizados
   return inertia('Roles/Index', [
       'roles' => $roles,
       'filters' => $request->only(['search', 'sort', 'order'])
   ]);
   ```

   Si el controlador no devuelve los filtros personalizados, el componente no podrá detectarlos como activos.

3. **Verificar los logs de consola**
   - Revisar si los filtros se están inicializando correctamente en el useEffect del FilterToolbar
   - Verificar si hay errores de TypeError al acceder a propiedades de filtros

### Problemas con la serialización JSON

#### 1. Objetos complejos en columnas de tabla

Si enfrentas problemas al mostrar objetos complejos (como colecciones de permisos) en columnas o al exportar:

1. **Transformar objetos complejos a strings antes de pasarlos a la vista**
   ```php
   // En el servicio o controlador
   $roles->through(function ($role) {
       // Convertir colección de permisos a string para visualización
       if (is_object($role->permissions) && method_exists($role->permissions, 'implode')) {
           $permissionString = $role->permissions->implode(', ');
           if (empty($permissionString)) {
               $permissionString = 'Sin permisos';
           }
           $role->permissions = $permissionString;
       }
       return $role;
   });
   ```

2. **Mejorar la función formatExportValue para manejar objetos complejos**
   ```typescript
   export function formatExportValue(value: unknown): string {
       if (value === null || value === undefined) {
           return '';
       }
       
       if (typeof value === 'object') {
           // Intentar convertir a JSON
           try {
               // Si es un array o tiene método toString(), usarlo
               if (Array.isArray(value)) {
                   return value.join(', ');
               }
               return JSON.stringify(value);
           } catch {
               return '[Objeto complejo]';
           }
       }
       
       return String(value);
   }
   ```

### Los permisos no se reflejan en la interfaz

Si los elementos protegidos por permisos no se muestran correctamente:

1. **Verificar configuración del middleware en Laravel 12**
   ```php
   // En bootstrap/app.php
   $middleware->alias([
       'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
       // Otros aliases...
   ]);
   ```

2. **Asegurar la correcta serialización de permisos**
   ```php
   // En HandleInertiaRequests.php
   'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray(),
   ```

3. **Comprobar los logs de consola**
   - Verificar que `auth.user.permissions` contiene los permisos esperados

4. **Limpiar caché tras cambios**
   ```bash
   php artisan optimize:clear
   ```

### El componente BaseIndexPage no respeta los permisos

Verifica que la configuración de permisos está correctamente definida:

```tsx
permissions: {
  create: "module.create",
  edit: "module.edit",
  // etc.
},
```

## Ejemplos completos

### Implementación completa de un índice de productos

```tsx
// products/index.tsx
import React from "react";
import { BaseIndexPage } from "@/components/base-index/base-index-page";
import { columns, type Product } from "./columns";
import { PageProps } from "@inertiajs/core";

interface ProductsIndexProps extends PageProps {
  products: {
    data: Product[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
  };
  filters?: {
    search?: string;
    sort?: string;
    order?: "asc" | "desc";
    page?: number;
    per_page?: number;
  };
}

export default function Index({ products, filters = {} }: ProductsIndexProps) {
  // Configuración centralizada para el índice de productos
  const indexOptions = {
    title: "Gestión de Productos",
    subtitle: "Administre todos los productos de su inventario",
    endpoint: "/products",
    moduleName: "products", // Identificador para traducciones de columnas
    breadcrumbs: [
      { title: "Dashboard", href: "/dashboard" },
      { title: "Productos", href: "/products" },
    ],
    // Tarjetas de estadísticas relevantes
    stats: [
      { value: 584, label: "Total Productos", icon: "shopping-bag", color: "blue" },
      { value: 42, label: "Sin stock", icon: "alert-triangle", color: "red" },
      { value: 128, label: "Nuevos este mes", icon: "trending-up", color: "green" },
    ],
    columns: columns,
    searchableColumns: ["name", "sku", "category"],
    searchPlaceholder: "Buscar por nombre, SKU o categoría",
    filterableColumns: ["category", "status"],
    defaultSorting: [{ id: "id", desc: true }],
    exportOptions: {
      enabled: true,
      fileName: "productos",
      exportTypes: ["excel", "csv", "print"] as ("excel" | "csv" | "print")[],
    },
    newButton: {
      show: true,
      label: "Nuevo Producto",
    },
    // Sistema completo de permisos
    permissions: {
      create: "products.create",
      view: "products.show",
      edit: "products.edit",
      delete: "products.delete",
    },
    rowActions: {
      view: {
        enabled: true,
        label: "Ver detalles",
        permission: "products.show",
      },
      edit: {
        enabled: true,
        label: "Editar",
        permission: "products.edit",
      },
      delete: {
        enabled: true,
        label: "Eliminar",
        permission: "products.delete",
        confirmMessage: (product: Product) => 
          `¿Está seguro que desea eliminar el producto ${product.name}?`,
      },
      // Acción personalizada con permiso específico
      custom: [
        {
          label: "Actualizar stock",
          icon: "refresh-cw",
          permission: "products.manage_inventory",
          onClick: (product: Product) => {
            // Lógica para actualizar el stock
          },
        },
      ],
    },
  };

  return (
    <BaseIndexPage<Product> 
      data={products} 
      filters={filters} 
      options={indexOptions} 
    />
  );
}
```

### Vista de índice básica

```tsx
import { DataTable } from "@/components/base-index";
import { columns } from "./columns"; // Define tus columnas en un archivo separado

export default function UsersIndex({ users }) {
  return (
    <div className="container py-6">
      <h1 className="text-2xl font-bold mb-6">Usuarios</h1>
      <DataTable
        columns={columns}
        data={users}
        filterableColumns={["name", "email"]}
      />
    </div>
  );
}
```

### Vista con acciones y servidor

```tsx
import { DataTable } from "@/components/base-index";
import { DataTableRowActions } from "@/components/base-index";
import { columns } from "./columns";
import { router } from "@inertiajs/react";

export default function UsersIndex({ users, filters }) {
  const handlePaginationChange = ({ pageIndex, pageSize }) => {
    router.visit(`/users`, {
      data: {
        ...filters,
        page: pageIndex + 1,
        per_page: pageSize,
      },
      preserveState: true,
      only: ["users"],
    });
  };

  const handleSortingChange = (sorting) => {
    router.visit(`/users`, {
      data: {
        ...filters,
        sort: sorting[0]?.id || "id",
        order: sorting[0]?.desc ? "desc" : "asc",
      },
      preserveState: true,
      only: ["users"],
    });
  };

  const renderRowActions = (user) => (
    <DataTableRowActions
      row={user}
      actions={{
        view: {
          enabled: true,
          handler: (user) => router.visit(`/users/${user.id}`),
        },
        edit: {
          enabled: true,
          handler: (user) => router.visit(`/users/${user.id}/edit`),
        },
        delete: {
          enabled: true,
          handler: (user) => {
            router.delete(`/users/${user.id}`, {
              onSuccess: () => {
                toast.success("Usuario eliminado correctamente");
              },
            });
          },
        },
      }}
    />
  );

  return (
    <div className="container py-6">
      <h1 className="text-2xl font-bold mb-6">Usuarios</h1>
      <DataTable
        columns={columns}
        data={users.data}
        renderRowActions={renderRowActions}
        serverSide={{
          totalRecords: users.total,
          pageCount: users.last_page,
          onPaginationChange: handlePaginationChange,
          onSortingChange: handleSortingChange,
        }}
      />
    </div>
  );
}
```

---

Este documento proporciona una guía para usar el patrón Base Index. Recuerda adaptar los ejemplos a tus necesidades específicas y consultar la documentación de TanStack Table para funcionalidades más avanzadas.

## Ventajas del componente BaseIndexPage

- **Reducción de código duplicado**: Centraliza la lógica común como paginación, ordenamiento y filtrado.
- **Mayor consistencia**: Asegura que todas las pantallas de índice tengan el mismo comportamiento y apariencia.
- **Desarrollo más rápido**: Crea nuevas pantallas de índice en minutos con mínimo código.
- **Mantenimiento simplificado**: Los cambios en la lógica común solo necesitan hacerse en un lugar.
- **Tipado seguro**: Proporciona interfaces TypeScript para ayudar a evitar errores.

## Arquitectura del Patrón Base Index

```
base-index/
 ├── base-index-page.tsx         # Componente de alto nivel para crear índices
 ├── data-table.tsx              # Componente principal de tabla
 ├── data-table-toolbar.tsx      # Barra de herramientas (búsqueda, exportación)
 ├── data-table-pagination.tsx   # Controles de paginación
 ├── data-table-export.tsx       # Funcionalidad de exportación
 ├── data-table-row-actions.tsx  # Acciones por fila con AlertDialog
 ├── data-table-view-options.tsx # Opciones de visibilidad de columnas
 ├── statistic-cards.tsx         # Tarjetas para mostrar estadísticas
 └── README.md                   # Esta documentación
```

## Seguridad de tipos

El sistema ha sido diseñado para aprovechar al máximo el sistema de tipos de TypeScript. Para una correcta integración, se recomienda seguir estas pautas:

### Extender de la interfaz Entity

Todas las entidades de datos deben extender de la interfaz `Entity` para garantizar compatibilidad con `BaseIndexPage`:

```tsx
import { Entity } from "@/components/base-index/base-index-page";

export interface User extends Entity {
  id: number;
  name: string;
  email: string;
  // Otras propiedades específicas...
  
  // Requerido para cumplir con Entity
  [key: string]: unknown;
}
```

### Especificar el tipo genérico

Siempre especifica el tipo genérico al usar `BaseIndexPage`:

```tsx
<BaseIndexPage<User> 
  data={users} 
  filters={filters} 
  options={indexOptions} 
/>
```

Esto garantiza que las opciones, columnas y acciones estén correctamente tipadas.
