# Sistema de Plantillas para Credenciales Díptico Carta

## Descripción General

El sistema de plantillas permite gestionar plantillas de credenciales díptico carta para los eventos. Cada evento puede tener múltiples plantillas con versiones diferentes, y una plantilla marcada como predeterminada.

## Estructura de Base de Datos

La tabla `templates` tiene la siguiente estructura:

| Campo       | Tipo          | Descripción                                       |
|-------------|---------------|---------------------------------------------------|
| id          | bigint        | Identificador único autoincremental               |
| uuid        | uuid          | Identificador único público para URLs             |
| event_id    | bigint        | Clave foránea al evento relacionado               |
| name        | varchar(255)  | Nombre descriptivo de la plantilla                |
| file_path   | varchar(255)  | Ruta al archivo de la plantilla                   |
| layout_meta | json          | Metadatos del diseño (posición de elementos)      |
| version     | integer       | Versión de la plantilla                           |
| is_default  | boolean       | Indica si es la plantilla predeterminada del evento |
| created_at  | timestamp     | Fecha de creación                                 |
| updated_at  | timestamp     | Fecha de última actualización                     |
| deleted_at  | timestamp     | Fecha de eliminación (soft delete)                |

## Restricciones

- Cada evento puede tener una sola plantilla marcada como predeterminada (`is_default = true`).
- Se utiliza un índice parcial en PostgreSQL para garantizar la unicidad de la plantilla predeterminada por evento.

## Estructura del campo `layout_meta`

El campo `layout_meta` almacena la información del diseño de la plantilla en formato JSON. La estructura mínima requerida es:

```json
{
  "fold_mm": 139.7,
  "rect_photo": {
    "x": 20,
    "y": 20,
    "width": 35,
    "height": 45
  },
  "rect_qr": {
    "x": 170,
    "y": 20,
    "width": 25,
    "height": 25
  },
  "text_blocks": [
    {
      "id": "nombre",
      "x": 70,
      "y": 30,
      "width": 90,
      "height": 10,
      "font_size": 12,
      "alignment": "left"
    },
    {
      "id": "rol",
      "x": 70,
      "y": 45,
      "width": 90,
      "height": 8,
      "font_size": 10,
      "alignment": "left"
    }
  ]
}
```

### Descripción de los campos en `layout_meta`

- **fold_mm**: Posición de la línea de pliegue en milímetros desde el borde superior.
- **rect_photo**: Rectángulo para la foto del participante.
  - x, y: Coordenadas de la esquina superior izquierda en mm.
  - width, height: Ancho y alto en mm.
- **rect_qr**: Rectángulo para el código QR.
  - x, y: Coordenadas de la esquina superior izquierda en mm.
  - width, height: Ancho y alto en mm.
- **text_blocks**: Arreglo de bloques de texto personalizables.
  - id: Identificador del campo (ej: "nombre", "rol").
  - x, y: Coordenadas de la esquina superior izquierda en mm.
  - width, height: Ancho y alto en mm.
  - font_size: Tamaño de fuente en puntos.
  - alignment: Alineación del texto ("left", "center", "right").

## Almacenamiento de Archivos

Las plantillas se almacenan en el disco `templates` configurado en `config/filesystems.php`. La estructura de directorios es:

```
storage/app/public/templates/{uuid_del_evento}/{uuid_del_template}.png
```

El campo `file_path` en la base de datos almacena la ruta relativa al archivo, por ejemplo:
```
templates/{uuid_del_evento}/{uuid_del_template}.png
```

## Integración con el Sistema

- **Modelo**: App\Models\Template
- **Repositorio**: App\Repositories\Template\TemplateRepository
- **Servicio**: App\Services\Template\TemplateService

## Validación y Cache

- El servicio valida la estructura mínima requerida del campo `layout_meta`.
- Se implementa cache con TTL de 60 minutos para mejorar el rendimiento en accesos repetidos a plantillas.
- La relación entre eventos y plantillas se mantiene mediante una relación `belongsTo` en el modelo Template.
