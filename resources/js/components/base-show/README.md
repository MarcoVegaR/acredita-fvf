# BaseShowPage

El componente `BaseShowPage` proporciona un patrón unificado para las vistas de detalle (Show) a lo largo de toda la aplicación. Está diseñado siguiendo un enfoque centrado en la lectura focalizada, donde el usuario encuentra únicamente la información organizada por secciones, sin distracciones ni acciones prominentes.

## Principios de Diseño

- **Lectura Focalizada**: Sin botones adicionales, solo información organizada.
- **Navegación Sencilla**: Enlace "Volver al listado" integrado en las breadcrumbs.
- **Modularidad por Secciones**: Datos agrupados en bloques lógicos.
- **Control de Acceso Fino**: Secciones o campos con visibilidad basada en permisos.
- **Consistencia Visual**: Comparte layout, estilos y sistema de notificaciones con el resto de la app.
- **Accesibilidad (A11y)**: Roles ARIA apropiados y navegabilidad por teclado.

## Uso Básico

```tsx
import { BaseShowPage } from '@/components/base-show/base-show-page';

export default function ShowUser({ user }) {
  const options = {
    title: user.name,
    subtitle: 'Detalle de usuario',
    breadcrumbs: [
      { title: 'Usuarios', href: '/users' },
      { title: user.name, href: `/users/${user.id}` },
    ],
    backUrl: '/users',
    entity: user,
    moduleName: 'users',
    sections: [
      {
        title: 'Datos básicos',
        fields: ['id', 'name', 'email', 
          { 
            key: 'email_verified_at', 
            label: 'Verificado', 
            render: v => v ? 'Sí' : 'No' 
          }
        ],
      },
      {
        title: 'Metadatos',
        fields: ['created_at', 'updated_at'],
      }
    ],
  };

  return <BaseShowPage options={options} />;
}
```

## Ejemplos Avanzados

### Sección Condicional

```tsx
{
  title: 'Información Administrativa',
  fields: ['created_by', 'updated_by'],
  permission: 'users.view_admin_data', // Solo visible con este permiso
  condition: (user) => user.is_admin // Solo visible si el usuario es admin
}
```

### Relaciones

```tsx
{
  key: 'roles',
  render: (roles, user) => {
    if (!roles || roles.length === 0) {
      return 'Ninguno';
    }
    return (
      <div className="flex flex-wrap gap-1">
        {roles.map(role => (
          <span key={role.id} className="badge">{role.name}</span>
        ))}
      </div>
    );
  }
}
```

## Pruebas

### Probar con diferentes permisos

1. Inicia sesión con un usuario con permisos limitados.
2. Verifica que solo se muestren las secciones y campos permitidos.
3. Compara con la vista desde un usuario admin.

### Probar la responsividad

1. Ajusta la ventana del navegador a diferentes tamaños.
2. Verifica que la vista se adapte correctamente a dispositivos móviles.
3. Confirma que la navegabilidad se mantiene en pantallas pequeñas.

## Integración con otros sistemas

- **Sistema de Notificaciones**: Utiliza Sonner para mostrar mensajes flash del servidor.
- **Sistema de Permisos**: Integrado con Spatie Laravel Permissions.
- **i18n**: Utiliza el sistema centralizado de traducciones para etiquetas de columnas.
