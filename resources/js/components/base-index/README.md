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
5. [Integración con Backend](#integración-con-backend)
6. [Notificaciones y mensajes flash](#notificaciones-y-mensajes-flash)
7. [Seguridad de tipos](#seguridad-de-tipos)
8. [Ejemplos completos](#ejemplos-completos)

## Introducción

El patrón Base Index proporciona una estructura reutilizable para crear vistas de listado de datos con funcionalidades avanzadas como ordenamiento, filtrado, paginación, exportación y gestión de acciones por fila. Este patrón está construido sobre TanStack Table (React Table) y componentes UI de ShadCN.

Características principales:
- **Sistema centralizado de traducciones** para etiquetas de columnas
- **Tarjetas de estadísticas** para mostrar métricas importantes
- **Interfaz visual mejorada** con diseño responsive y jerarquía visual clara
- **Tipado seguro** con TypeScript para prevenir errores
- **Acciones de fila** con confirmación integrada mediante AlertDialog

## Componentes disponibles

El patrón Base Index incluye los siguientes componentes:

- `BaseIndexPage`: Componente de alto nivel que facilita la creación de páginas de índice sin duplicación de código.
- `DataTable`: Componente principal que orquesta todas las funcionalidades.
- `DataTableToolbar`: Barra de herramientas con búsqueda global y opciones de tabla.
- `DataTablePagination`: Controles de paginación para navegación.
- `DataTableViewOptions`: Selector de visibilidad de columnas.
- `DataTableExport`: Opciones de exportación (Excel, CSV, imprimir, copiar).
- `DataTableRowActions`: Acciones por fila (ver, editar, eliminar, personalizadas) con confirmación integrada.
- `StatisticCards`: Tarjetas con estadísticas relevantes al módulo.
- `Utilidades de traducción`: Sistema centralizado para gestionar etiquetas de columnas.

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
    // Acciones de fila con confirmación integrada
    rowActions: {
      view: {
        enabled: true,
        label: "Ver detalles",
      },
      edit: {
        enabled: true,
        label: "Editar",
      },
      delete: {
        enabled: true,
        label: "Eliminar",
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

## Ejemplos completos

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
