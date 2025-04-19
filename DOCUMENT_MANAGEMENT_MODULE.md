# Módulo de Gestión de Documentos

Este documento describe la implementación del módulo independiente de gestión múltiple de documentos para la aplicación Laravel 12 + Inertia.js + React/TypeScript, siguiendo la arquitectura SOLID existente. Incluye guías de integración con otros módulos basadas en la experiencia de implementación con el módulo de usuarios.

## Contexto

El sistema es un monolito Laravel 12 + Inertia.js + React/TypeScript, regido por una arquitectura SOLID con capas de Repositorio, Servicio y Controlador base. El módulo de documentos permite la gestión múltiple de documentos, invocándose siempre desde su propia sección Inertia, incluso cuando un módulo solo tenga un archivo.

- El almacenamiento es local
- Los tipos de documento (inicialmente solo PDF) se definen estáticamente en `config/documents.php`
- Se mantiene la lógica de permisos centralizada con PermissionHelper de Spatie, sin usar Policies
- Integración con el sistema de notificaciones centralizado basado en sonner (a través del componente `FlashMessages`)

## Estructura de Base de Datos

### Esquema de Identificadores

- Cada tabla conserva su PK `id` (autoincremental)
- Se añade columna `uuid` en `documents` (y opcionalmente en `documentables`) para exponer en rutas públicas

### Tablas y Relaciones

```
documents
├── id (PK)
├── uuid
├── document_type_id (FK)
├── user_id (FK, who uploaded)
├── filename
├── original_filename
├── file_size
├── mime_type
├── path
├── is_validated (boolean)
└── timestamps

document_types
├── id (PK)
├── code (string, unique)
├── label (string)
└── timestamps

documentables (pivot polimórfico)
├── id (PK)
├── uuid (opcional)
├── document_id (FK)
├── documentable_id
├── documentable_type
└── timestamps
```

## Migraciones y Seeder de Tipos

### Migraciones

```php
// create_document_types_table.php
Schema::create('document_types', function (Blueprint $table) {
    $table->id();
    $table->string('code')->unique();
    $table->string('label');
    $table->timestamps();
});

// create_documents_table.php
Schema::create('documents', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->unique();
    $table->foreignId('document_type_id')->constrained();
    $table->foreignId('user_id')->constrained();
    $table->string('filename');
    $table->string('original_filename');
    $table->integer('file_size');
    $table->string('mime_type');
    $table->string('path');
    $table->boolean('is_validated')->default(false);
    $table->timestamps();
});

// create_documentables_table.php
Schema::create('documentables', function (Blueprint $table) {
    $table->id();
    $table->uuid('uuid')->nullable()->unique();
    $table->foreignId('document_id')->constrained()->cascadeOnDelete();
    $table->morphs('documentable');
    $table->timestamps();
});
```

### Seeder de Tipos

```php
// DocumentTypesSeeder.php
public function run()
{
    $types = config('documents.types');
    
    foreach ($types as $type) {
        DocumentType::updateOrCreate(
            ['code' => $type['code']],
            ['label' => $type['label']]
        );
    }
}
```

## Modelos

### Document

```php
class Document extends Model
{
    use HasFactory, HasUuids;
    
    protected $fillable = [
        'document_type_id',
        'user_id',
        'filename',
        'original_filename',
        'file_size',
        'mime_type',
        'path',
        'is_validated'
    ];
    
    protected $casts = [
        'is_validated' => 'boolean',
        'file_size' => 'integer'
    ];
    
    public function type()
    {
        return $this->belongsTo(DocumentType::class, 'document_type_id');
    }
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function documentables()
    {
        return $this->hasMany(Documentable::class);
    }
    
    public function scopeUnvalidated($query)
    {
        return $query->where('is_validated', false);
    }
}
```

### DocumentType

```php
class DocumentType extends Model
{
    use HasFactory;
    
    protected $fillable = ['code', 'label'];
    
    public function documents()
    {
        return $this->hasMany(Document::class);
    }
    
    public function scopeForModule($query, $moduleCode)
    {
        return $query->whereIn('code', 
            collect(config('documents.modules.'.$moduleCode.'.allowed_types', []))
        );
    }
}
```

### Documentable (Pivot Polimórfico)

```php
class Documentable extends Model
{
    use HasFactory;
    use HasUuids;
    
    protected $fillable = [
        'document_id',
        'documentable_id',
        'documentable_type'
    ];
    
    public function document()
    {
        return $this->belongsTo(Document::class);
    }
    
    public function documentable()
    {
        return $this->morphTo();
    }
}
```

## Repositorios

### DocumentRepositoryInterface

```php
interface DocumentRepositoryInterface extends RepositoryInterface
{
    public function listByEntity(string $module, int $entityId);
    public function findByUuid(string $uuid);
    public function deleteByUuid(string $uuid);
    public function getDocumentTypes(string $module = null);
}
```

### DocumentRepository

```php
class DocumentRepository extends BaseRepository implements DocumentRepositoryInterface
{
    public function __construct(Document $model)
    {
        parent::__construct($model);
    }
    
    public function listByEntity(string $module, int $entityId)
    {
        $modelClass = config("documents.modules.{$module}.model");
        
        return $this->model
            ->whereHas('documentables', function ($query) use ($modelClass, $entityId) {
                $query->where('documentable_type', $modelClass)
                      ->where('documentable_id', $entityId);
            })
            ->with(['type', 'user'])
            ->latest()
            ->get();
    }
    
    public function findByUuid(string $uuid)
    {
        return $this->model
            ->where('uuid', $uuid)
            ->with(['type', 'user'])
            ->firstOrFail();
    }
    
    public function deleteByUuid(string $uuid)
    {
        $document = $this->findByUuid($uuid);
        return $document->delete();
    }
    
    public function getDocumentTypes(string $module = null)
    {
        if ($module) {
            return DocumentType::forModule($module)->get();
        }
        
        return DocumentType::all();
    }
}
```

## Servicios

### DocumentServiceInterface

```php
interface DocumentServiceInterface
{
    public function upload(UploadedFile $file, string $module, int $entityId, int $userId, int $typeId = null);
    public function delete(string $documentUuid);
    public function list(string $module, int $entityId);
    public function getByUuid(string $uuid);
    public function getDocumentTypes(string $module = null);
}
```

### DocumentService

```php
class DocumentService implements DocumentServiceInterface
{
    protected $documentRepository;
    
    public function __construct(DocumentRepositoryInterface $documentRepository)
    {
        $this->documentRepository = $documentRepository;
    }
    
    public function upload(UploadedFile $file, string $module, int $entityId, int $userId, int $typeId = null)
    {
        // Validar que el módulo exista
        if (!array_key_exists($module, config('documents.modules'))) {
            throw new \InvalidArgumentException("El módulo {$module} no está configurado");
        }
        
        // Obtener el tipo de documento predeterminado si no se proporciona
        if (!$typeId) {
            $defaultType = config("documents.modules.{$module}.default_type");
            $type = DocumentType::where('code', $defaultType)->firstOrFail();
            $typeId = $type->id;
        }
        
        // Generar UUID para el documento
        $uuid = (string) Str::uuid();
        
        // Guardar el archivo
        $originalFilename = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = "{$uuid}.{$extension}";
        $path = $file->storeAs("documents/{$module}", $filename, 'public');
        
        // Crear registro de documento
        $document = $this->documentRepository->create([
            'document_type_id' => $typeId,
            'user_id' => $userId,
            'filename' => $filename,
            'original_filename' => $originalFilename,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'path' => $path,
            'is_validated' => false,
        ]);
        
        // Crear relación polimórfica
        $modelClass = config("documents.modules.{$module}.model");
        $document->documentables()->create([
            'documentable_type' => $modelClass,
            'documentable_id' => $entityId,
        ]);
        
        // Disparar evento
        event(new DocumentUploaded($document, $module, $entityId));
        
        return $document;
    }
    
    public function delete(string $documentUuid)
    {
        $document = $this->documentRepository->findByUuid($documentUuid);
        
        // Eliminar archivo físico
        Storage::disk('public')->delete($document->path);
        
        // Eliminar registro
        $result = $this->documentRepository->deleteByUuid($documentUuid);
        
        // Disparar evento
        event(new DocumentDeleted($document));
        
        return $result;
    }
    
    public function list(string $module, int $entityId)
    {
        return $this->documentRepository->listByEntity($module, $entityId);
    }
    
    public function getByUuid(string $uuid)
    {
        return $this->documentRepository->findByUuid($uuid);
    }
    
    public function getDocumentTypes(string $module = null)
    {
        return $this->documentRepository->getDocumentTypes($module);
    }
}
```

## Validación y Autorización

### StoreDocumentRequest

```php
class StoreDocumentRequest extends FormRequest
{
    public function authorize()
    {
        $module = $this->route('module');
        
        return PermissionHelper::hasAnyPermission([
            'documents.upload',
            "documents.upload.{$module}"
        ]);
    }
    
    public function rules()
    {
        $module = $this->route('module');
        $config = config("documents.modules.{$module}");
        $maxSize = $config['max_size'] ?? 10240; // 10MB default
        $allowedMimes = $config['allowed_mimes'] ?? 'pdf';
        
        return [
            'file' => ["required", "file", "mimes:{$allowedMimes}", "max:{$maxSize}"],
            'document_type_id' => ['sometimes', 'nullable', 'exists:document_types,id']
        ];
    }
}
```

### DeleteDocumentRequest

```php
class DeleteDocumentRequest extends FormRequest
{
    public function authorize()
    {
        $document = Document::where('uuid', $this->document_uuid)->firstOrFail();
        $documentable = $document->documentables()->first();
        
        if (!$documentable) {
            return false;
        }
        
        // Extraer el nombre del módulo desde el tipo documentable
        $modelClass = $documentable->documentable_type;
        $moduleKey = array_search($modelClass, array_column(config('documents.modules'), 'model'));
        
        if (!$moduleKey) {
            return false;
        }
        
        return PermissionHelper::hasAnyPermission([
            'documents.delete',
            "documents.delete.{$moduleKey}"
        ]);
    }
    
    public function rules()
    {
        return [
            'document_uuid' => ['required', 'string', 'exists:documents,uuid']
        ];
    }
}
```

## Controladores (Inertia)

### DocumentController

```php
class DocumentController extends BaseController
{
    protected $documentService;
    
    public function __construct(DocumentServiceInterface $documentService)
    {
        $this->documentService = $documentService;
    }
    
    public function index(Request $request, $module, $entityId)
    {
        try {
            if (!PermissionHelper::hasAnyPermission(['documents.view', "documents.view.{$module}"])) {
                return $this->respondWithError('No tiene permisos para ver documentos');
            }
            
            $documents = $this->documentService->list($module, $entityId);
            $documentTypes = $this->documentService->getDocumentTypes($module);
            $moduleConfig = config("documents.modules.{$module}");
            
            $this->logAction("Visualizó documentos del módulo {$module}");
            
            return Inertia::render('Documents/Index', [
                'documents' => $documents,
                'documentTypes' => $documentTypes,
                'module' => $module,
                'entityId' => $entityId,
                'moduleConfig' => $moduleConfig
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Listar documentos');
        }
    }
    
    public function store(StoreDocumentRequest $request, $module, $entityId)
    {
        try {
            $document = $this->documentService->upload(
                $request->file('file'),
                $module,
                $entityId,
                auth()->id(),
                $request->input('document_type_id')
            );
            
            $this->logAction("Subió documento en el módulo {$module}");
            
            return redirect()->back()->with('success', 'Documento subido correctamente');
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Subir documento');
        }
    }
    
    public function destroy(DeleteDocumentRequest $request, $documentUuid)
    {
        try {
            $document = $this->documentService->getByUuid($documentUuid);
            $this->documentService->delete($documentUuid);
            
            $this->logAction("Eliminó documento {$document->original_filename}");
            
            return redirect()->back()->with('success', 'Documento eliminado correctamente');
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Eliminar documento');
        }
    }
    
    public function download($documentUuid)
    {
        try {
            $document = $this->documentService->getByUuid($documentUuid);
            
            $documentable = $document->documentables()->first();
            $modelClass = $documentable->documentable_type;
            $moduleKey = array_search($modelClass, array_column(config('documents.modules'), 'model'));
            
            if (!PermissionHelper::hasAnyPermission(['documents.download', "documents.download.{$moduleKey}"])) {
                return $this->respondWithError('No tiene permisos para descargar documentos');
            }
            
            $this->logAction("Descargó documento {$document->original_filename}");
            
            return Storage::disk('public')->download(
                $document->path,
                $document->original_filename
            );
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Descargar documento');
        }
    }
}
```

## Rutas Web

```php
// routes/web.php

// Rutas de documentos
Route::prefix('/{module}/{entity_id}/documents')->group(function () {
    Route::get('/', [DocumentController::class, 'index'])->name('documents.index');
    Route::post('/', [DocumentController::class, 'store'])->name('documents.store');
});

Route::delete('/documents/{document_uuid}', [DocumentController::class, 'destroy'])->name('documents.destroy');
Route::get('/documents/{document_uuid}/download', [DocumentController::class, 'download'])->name('documents.download');
```

## Configuración Central

### config/documents.php

```php
<?php

## Guía de Integración con Otros Módulos

Basado en la experiencia de integración con el módulo de usuarios, a continuación se presentan las mejores prácticas para integrar este módulo de documentos con otros módulos de la aplicación.

### Backend: Controlador de Documentos

#### Puntos clave para la integración

1. **Separación de contextos de usuario**
   - Es crítico distinguir entre el usuario autenticado (`$currentUser`) y el usuario objetivo (`$targetUser`) cuando se manejan documentos de usuarios.
   - En módulos que no sean usuarios, cambia `$targetUser` por la entidad correspondiente del módulo.

   ```php
   // En DocumentController
   public function index(string $module, int $entityId)
   {
       // Obtener la entidad objetivo según el módulo
       if ($module === 'users') {
           $targetUser = \App\Models\User::findOrFail($entityId);
           // Logs para depuración si es necesario
           Log::info('DEBUG - USUARIO OBJETIVO (DOCUMENTOS)', [
               'entity_id' => $entityId,
               'target_user_id' => $targetUser->id,
               'target_user_name' => $targetUser->name,
               'url_completa' => request()->url()
           ]);
       } else {
           // Adaptar para otros módulos según sea necesario
           $targetEntity = $this->resolveEntityByModule($module, $entityId);
       }
       
       // Obtener el usuario autenticado y sus permisos
       $currentUser = auth()->user();
       // Registrar información completa del usuario para depuración
       Log::info('DEBUG - USUARIO AUTENTICADO (PERMISOS)', [
           'auth_user_id' => $currentUser->id,
           'auth_user_name' => $currentUser->name,
           'roles' => $currentUser->getRoleNames()->toArray(),
           'all_permissions' => $currentUser->getPermissionNames()->toArray(),
           'has_documents_upload' => $currentUser->can('documents.upload'),
           'has_documents_upload_users' => $currentUser->can('documents.upload.users'),
           'is_admin' => $currentUser->hasRole('admin')
       ]);
       
       // Resto del código...
   }
   ```

2. **Verificación de permisos en varias capas**
   - Implementar verificación con permisos específicos del módulo primero y genéricos después
   - Asegurar que los permisos se pasen correctamente al frontend

   ```php
   // Verificación de permisos específica y genérica
   $modulePermission = "documents.view.{$module}";
   $genericPermission = "documents.view";
   
   if (!$currentUser->can($modulePermission) && !$currentUser->can($genericPermission) && !$currentUser->hasRole('admin')) {
       abort(403, 'No tiene permiso para ver documentos en este módulo');
   }
   ```

### Frontend: Componente DocumentsSection

1. **Manejo de rutas y contextos**
   - Detectar correctamente si estamos en una vista dedicada de documentos o en una vista de detalle

   ```typescript
   // Identificar el contexto de la vista
   const isDocumentsPage = window.location.pathname.endsWith('/documents');
   ```

2. **Implementación correcta de permisos**
   - Comprobar permisos específicos del módulo y genéricos

   ```typescript
   const hasDocumentPermission = (action: string, moduleSpecific: boolean = true): boolean => {
     const genericPermission = `documents.${action}`;
     const modulePermission = `documents.${action}.${module}`;
     
     // Verificar el permiso específico del módulo primero
     if (moduleSpecific && permissions.includes(modulePermission)) {
       return true;
     }
     
     // Verificar el permiso genérico como fallback
     return permissions.includes(genericPermission);
   };
   ```

3. **Lógica de visibilidad del botón según contexto**
   - En vistas dedicadas de documentos, mostrar el botón si hay permisos
   - En vistas de detalle (tabs), respetar el modo `readOnly`

   ```typescript
   // Lógica combinada para determinar visibilidad
   const canUpload = (isDocumentsPage || !readOnly) && hasDocumentPermission('upload');
   ```

4. **Manejo del título en el componente**
   - Permitir que el componente acepte un parámetro `title` para dar flexibilidad en diferentes contextos
   - Cuando se usa en tabs de detalle, pasar `title=""` para evitar título duplicado con el de la sección

   ```typescript
   // En DocumentsTab.tsx
   const sectionTitle = title || getColumnLabel('documents', 'section_title');

   return (
     <div className="space-y-4">
       {title && (
         <h3 className="text-lg font-medium">{sectionTitle}</h3>
       )}
       
       <DocumentsSection ... />
     </div>
   );
   ```

### Integración en páginas

1. **Vista dedicada de documentos** (ejemplo con usuarios)
   - Usar `BaseShowPage` con el patrón de campo ficticio para renderizar `DocumentsSection`

   ```tsx
   <BaseShowPage
     options={{
       title: title,
       subtitle: `Gestión de documentos para ${entity.name}`,
       breadcrumbs: breadcrumbs,
       moduleName: "documents",
       entity: entity,
       sections: [
         {
           title: "Documentos",
           fields: [
             {
               key: 'id',
               label: '',
               render: () => (
                 <DocumentsSection
                   module="users"
                   entityId={entity.id}
                   types={types}
                   permissions={permissions}
                   readOnly={false}
                 />
               )
             }
           ]
         }
       ]
     }}
   />
   ```

2. **Tab de documentos en vista de detalle**
   - Al integrar en una vista de detalle con tabs, usar clave vacía para evitar problemas con la tabla de datos:

   ```tsx
   // Tab para documentos en una vista de detalle (ej: usuario)
   {
     tab: "documents",
     title: "Documentos del usuario",
     fields: [
       {
         key: "", // Usar una clave vacía evita problemas con la identificación
         label: "", // Sin etiqueta para ocupar todo el espacio disponible
         render: () => (
           <div className="w-full">
             <DocumentsTab
               module="users"
               entityId={user.id}
               types={documentTypes}
               permissions={userPermissions}
               title="" // Pasar título vacío para evitar duplicación
             />
           </div>
         )
       }
     ]
   }
   ```

   > **Nota importante sobre layout**: Al integrar la tabla de documentos en una vista de detalle, es posible que el grid layout predeterminado de BaseShowPage (2 columnas en pantallas medianas) cause problemas de ancho. Para casos especiales donde la tabla necesita ocupar todo el ancho, considere soluciones específicas como CSS personalizado.

### Permisos necesarios

Para cada módulo que integre documentos, asegurar que los siguientes permisos estén creados en el seeder:

```php
// Permisos genéricos (ya existentes)
$this->createPermission('documents.view', 'Ver documentos');
$this->createPermission('documents.upload', 'Subir documentos');
$this->createPermission('documents.delete', 'Eliminar documentos');
$this->createPermission('documents.download', 'Descargar documentos');

// Permisos específicos para el nuevo módulo
$this->createPermission('documents.view.{module}', 'Ver documentos de {ModuleLabel}');
$this->createPermission('documents.upload.{module}', 'Subir documentos de {ModuleLabel}');
$this->createPermission('documents.delete.{module}', 'Eliminar documentos de {ModuleLabel}');
$this->createPermission('documents.download.{module}', 'Descargar documentos de {ModuleLabel}');
```

## Configuración de Módulos

```php
return [
    // Definición de tipos de documentos
    'types' => [
        [
            'code' => 'contract',
            'label' => 'Contrato'
        ],
        [
            'code' => 'invoice',
            'label' => 'Factura'
        ],
        [
            'code' => 'other',
            'label' => 'Otro'
        ]
    ],
    
    // Configuración por módulo
    'modules' => [
        'users' => [
            'model' => App\Models\User::class,
            'allowed_types' => ['contract', 'other'],
            'default_type' => 'contract',
            'max_size' => 5120, // 5MB
            'allowed_mimes' => 'pdf'
        ],
        'companies' => [
            'model' => App\Models\Company::class,
            'allowed_types' => ['invoice', 'contract', 'other'],
            'default_type' => 'invoice',
            'max_size' => 10240, // 10MB
            'allowed_mimes' => 'pdf'
        ],
        // Otros módulos...
    ]
];
```

## Eventos y Listeners

```php
// Events/DocumentUploaded.php
class DocumentUploaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $document;
    public $module;
    public $entityId;

    public function __construct(Document $document, string $module, int $entityId)
    {
        $this->document = $document;
        $this->module = $module;
        $this->entityId = $entityId;
    }
}

// Events/DocumentDeleted.php
class DocumentDeleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }
}
```

## Sistema de Notificaciones

El sistema de notificaciones está centralizado a través del componente `FlashMessages`, que es el único punto donde los mensajes flash del servidor deben ser procesados y mostrados como notificaciones toast usando la biblioteca sonner.

### Backend

```php
// En el controlador, usar session()->flash para mensajes de éxito
public function store(DocumentRequest $request)
{
    // Lógica de guardado...
    
    // Enviar mensaje flash para notificación
    session()->flash('success', 'Documento subido correctamente');
    
    return redirect()->back();
}
```

### Frontend

1. **Componente FlashMessages**
   - El componente `FlashMessages` se encarga de detectar y mostrar todos los mensajes flash del servidor.
   - **¡IMPORTANTE!** Otras partes de la aplicación NO deben capturar ni procesar estos mensajes flash para evitar duplicación.

2. **Notificaciones desde acciones del cliente**
   - Para notificaciones generadas por el cliente sin interacción con el servidor, usar directamente la API de sonner:

   ```typescript
   import { toast } from 'sonner';
   
   // Mostrar una notificación de éxito
   toast.success('Acción completada exitosamente');
   ```

## Solución de Problemas Comunes

### 1. Notificaciones Duplicadas

Si aparecen notificaciones duplicadas al realizar acciones como subir o eliminar documentos:

- Verificar que solo el componente `FlashMessages` esté procesando los mensajes flash.
- Comprobar que otros componentes (como `BaseShowPage` o páginas específicas) no estén implementando su propia lógica de captura de mensajes flash.

```typescript
// Código que puede causar notificaciones duplicadas - EVITAR ESTO
useEffect(() => {
  if (flash?.success) {
    toast.success(flash.success);
  }
}, [flash]);
```

### 2. Problemas con Componentes UI (Radix UI)

Al usar componentes Select o Dialog basados en Radix UI (como en el modal de subida de documentos), pueden ocurrir errores de recursión infinita con eventos de focus:

```
Uncaught InternalError: too much recursion
    focus focus-scope.tsx:295
    handleFocusIn2 focus-scope.tsx:81
    ...
```

Solución:

```typescript
// Agregar un manejador de eventos focus que detenga la propagación
<SelectTrigger 
  id="document-type"
  onFocus={(e: React.FocusEvent<HTMLButtonElement>) => {
    // Romper el ciclo de recursión
    e.stopPropagation();
  }}
>
  <SelectValue placeholder="Seleccione un tipo" />
</SelectTrigger>
```

## Service Provider

El `RepositoryServiceProvider.php` debe ser actualizado para incluir el binding de las interfaces de documento:

```php
// RepositoryServiceProvider.php
$this->app->bind(
    \App\Repositories\Document\DocumentRepositoryInterface::class,
    \App\Repositories\Document\DocumentRepository::class
);

$this->app->bind(
    \App\Services\Document\DocumentServiceInterface::class,
    \App\Services\Document\DocumentService::class
);
```

## Permisos

Los siguientes permisos deben ser agregados al seeder de permisos:

```php
// Permisos generales
'documents.view',
'documents.upload',
'documents.delete',
'documents.download',

// Permisos por módulo
'documents.view.users',
'documents.upload.users',
'documents.delete.users',
'documents.download.users',

'documents.view.companies',
'documents.upload.companies',
'documents.delete.companies',
'documents.download.companies',
// etc.
```

## Consideraciones de Implementación

1. **Identificadores únicos**: El UUID se utiliza para exponer documentos en rutas públicas, evitando revelar IDs secuenciales.

2. **Seguridad**: Los permisos se verifican a nivel de controlador y request, siguiendo el patrón existente con PermissionHelper.

3. **Polimorfismo**: El diseño permite asociar documentos con cualquier entidad a través de la tabla pivot `documentables`.

4. **Configuración centralizada**: Toda la configuración específica de módulos y tipos de documentos está definida en `config/documents.php`.

5. **Extensibilidad**: Para añadir soporte a un nuevo módulo, solo se requiere actualizar la configuración y añadir los permisos correspondientes.
