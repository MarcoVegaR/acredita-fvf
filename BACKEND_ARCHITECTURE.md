# Arquitectura Backend

Este documento describe la arquitectura backend de nuestra aplicación Laravel 12 con Inertia y React, siguiendo los principios SOLID para garantizar un código mantenible, escalable y testeable.

## Estructura General

Nuestra arquitectura se basa en una clara separación de responsabilidades:

```
App/
├─ Http/
│  ├─ Controllers/         # Controladores (solo manejan solicitudes y respuestas)
│  ├─ Requests/            # Form Requests para validación y autorización
│  ├─ Resources/           # API Resources para transformación de datos
│  └─ Middleware/          # Middleware personalizado
├─ Models/                 # Modelos Eloquent
├─ Repositories/           # Patrón Repository para acceso a datos
│  ├─ RepositoryInterface.php        # Interfaz base
│  ├─ BaseRepository.php             # Implementación base
│  └─ [Module]/                      # Repositorios por módulo
│     ├─ [Module]RepositoryInterface.php
│     └─ [Module]Repository.php
├─ Services/               # Capa de Servicios para lógica de negocio
│  └─ [Module]/
│     ├─ [Module]ServiceInterface.php
│     └─ [Module]Service.php
└─ Providers/              # Service Providers
   └─ RepositoryServiceProvider.php  # Binding de interfaces a implementaciones
```

## Principios SOLID Aplicados

### S - Single Responsibility Principle
Cada clase tiene una única responsabilidad bien definida y estrictamente respetada:
- **Repositories**: Manejan exclusivamente el acceso a datos (consultas, almacenamiento y recuperación)
- **Services**: Contienen toda la lógica de negocio, incluyendo:
  - Normalización y transformación de datos
  - Coordinación de operaciones entre múltiples repositorios
  - Validación contextual y reglas de negocio
  - Preparación de datos para vistas o respuestas
- **Controllers**: Limitados estrictamente a:
  - Recibir solicitudes HTTP
  - Delegar a servicios adecuados
  - Registrar auditoría de acciones
  - Retornar respuestas HTTP apropiadas
- **Form Requests**: Encapsulan validación de entrada y autorización

### O - Open/Closed Principle
Las clases están abiertas para extensión pero cerradas para modificación:
- Base classes (BaseRepository, BaseController) proporcionan funcionalidad común
- Clases derivadas extienden esta funcionalidad sin modificar el código original

### L - Liskov Substitution Principle
Las interfaces son intercambiables con sus implementaciones:
- UserRepositoryInterface puede ser sustituido por cualquier UserRepository

### I - Interface Segregation Principle
Las interfaces específicas son mejores que las generales:
- Cada módulo tiene sus propias interfaces (UserServiceInterface, RoleServiceInterface, etc.)

### D - Dependency Inversion Principle
Dependemos de abstracciones, no de implementaciones concretas:
- Los controllers dependen de interfaces de servicio
- Los servicios dependen de interfaces de repositorio

## Flujo de Datos

1. **HTTP Request** → Controller
2. Controller → **Form Request** (validación/autorización)
3. Controller → **Service** (lógica de negocio)
4. Service → **Repository** (acceso a datos)
5. Repository → **Model** (Eloquent)
6. Controller → **Inertia Response** / Redirect

## Arquitectura de Controladores

Siguiendo las mejores prácticas de Laravel 12, nuestra arquitectura de controladores se organiza de la siguiente manera:

### Jerarquía de Controladores

1. **Controller.php** (clase base mínima de Laravel)
   - Contiene solamente los traits `AuthorizesRequests` y `ValidatesRequests`
   - No tiene métodos adicionales
   - Coincide con la estructura estándar de Laravel 12

2. **BaseController.php** (nuestro controlador base personalizado)
   - Extiende de `Controller.php` 
   - Centraliza todos los métodos auxiliares comunes:
     - `respondWithSuccess` / `redirectWithSuccess` para respuestas Inertia
     - `respondWithError` / `handleException` para manejo de errores
     - `logAction` para auditoría
     - `validateRequest` para validación (aunque se prefiere Form Requests)
     - `userCan` / `authorizeAction` para autorización

3. **Controladores específicos** (UserController, RoleController, etc.)
   - Extienden de `BaseController.php`
   - Inyectan servicios a través de interfaces (Dependency Inversion)
   - Se limitan a coordinar las solicitudes HTTP y delegar en servicios

### Ejemplo de Controlador

```php
class ProductController extends BaseController
{
    protected $productService;

    public function __construct(ProductServiceInterface $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        try {
            $products = $this->productService->getPaginatedProducts($request);
            return $this->respondWithSuccess('products.index', compact('products'));
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Listar productos');
        }
    }
}
```

## Lineamientos por Capa

### 1. Migraciones, Factories y Seeders

- Cada migración debe representar un único cambio de esquema (Single Responsibility)
- Nombres de migraciones descriptivos (create_users_table, add_active_to_users, etc.)
- Factories para generar datos de prueba coherentes
- Seeders específicos por módulo para poblar la base de datos

```php
// database/migrations/YYYY_MM_DD_create_products_table.php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description');
    $table->decimal('price', 10, 2);
    $table->boolean('active')->default(true);
    $table->timestamps();
});
```

### 2. Modelos

- Mínima lógica dentro de los modelos
- Scopes para consultas comunes
- Relaciones claras y bien documentadas

```php
// app/Models/Product.php
class Product extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'price', 'active'];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
```

### 3. Repository Pattern

- Define interfaces para cada entidad
- Implementa BaseRepository con métodos genéricos
- Repositorios concretos extienden BaseRepository

```php
// Nuevo repositorio para productos
// app/Repositories/Product/ProductRepositoryInterface.php
interface ProductRepositoryInterface extends RepositoryInterface
{
    public function findByName(string $name);
    public function getActive();
}

// app/Repositories/Product/ProductRepository.php
class ProductRepository extends BaseRepository implements ProductRepositoryInterface
{
    public function __construct(Product $model)
    {
        parent::__construct($model);
    }

    public function findByName(string $name)
    {
        return $this->model->where('name', 'LIKE', "%{$name}%")->get();
    }

    public function getActive()
    {
        return $this->model->active()->get();
    }
}
```

No olvides registrar el repositorio en el RepositoryServiceProvider:

```php
// app/Providers/RepositoryServiceProvider.php
public function register(): void
{
    // Repositorios existentes
    $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
    
    // Nuevo repositorio
    $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
}
```

### 4. Service Layer

- Interfaces e implementaciones para cada módulo
- Centraliza TODA la lógica de negocio (no debe haber lógica en controladores)
- Orquestación de operaciones complejas
- Implementa transformaciones y normalizaciones de datos
- Prepara datos para vistas y respuestas
- Delega acceso a datos a los repositorios

```php
// app/Services/Product/ProductServiceInterface.php
interface ProductServiceInterface
{
    public function getPaginatedProducts(Request $request): LengthAwarePaginator;
    public function createProduct(array $data): Product;
    public function updateProduct(Product $product, array $data): Product;
    public function deleteProduct(Product $product): bool;
}

// app/Services/Product/ProductService.php
class ProductService implements ProductServiceInterface
{
    protected $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getPaginatedProducts(Request $request): LengthAwarePaginator
    {
        // Implementación con filtros, ordenamiento, etc.
    }

    public function createProduct(array $data): Product
    {
        // Normalizar datos
        if (isset($data['tags']) && !is_array($data['tags'])) {
            $data['tags'] = [$data['tags']];  // Convertir a array si no lo es
        }
        
        // Procesar imágenes/archivos si es necesario
        if (isset($data['image'])) {
            $data['image_path'] = $this->processImage($data['image']);
            unset($data['image']);
        }
        
        // Transacciones
        DB::beginTransaction();
        try {
            $product = $this->productRepository->create($data);
            // Operaciones relacionadas (tags, categorías, etc.)
            DB::commit();
            return $product;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function getProductForDisplay(Product $product): array
    {
        // Preparar datos completos para una vista
        $product = $this->productRepository->find($product->id, ['category', 'tags']);
        $relatedProducts = $this->getRelatedProducts($product);
        $categories = $this->categoryRepository->getActive();
        
        return [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'categories' => $categories
        ];
    }

    // Otros métodos...
}
```

### 5. Form Requests

- Validación y autorización separadas del controlador
- Reglas de validación claras y reutilizables

```php
// app/Http/Requests/Product/StoreProductRequest.php
class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create products');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
```

### 6. Controladores

- Extienden BaseController (no Controller directamente)
- Inyectan servicios por interfaz (nunca implementaciones concretas)
- Delegan validación a Form Requests dedicados
- **NO contienen ninguna lógica de negocio** (ni siquiera transformaciones simples)
- Responsabilidades estrictamente limitadas a:
  - Obtener datos de la request (ya validados por Form Requests)
  - Pasar datos al servicio apropiado
  - Registrar acciones para auditoría
  - Retornar respuestas HTTP
- Métodos try-catch que utilizan handleException del BaseController
- Utilizan métodos auxiliares del BaseController para respuestas consistentes

```php
// app/Http/Controllers/ProductController.php
class ProductController extends BaseController
{
    protected $productService;

    public function __construct(ProductServiceInterface $productService)
    {
        $this->productService = $productService;
    }

    public function index(Request $request)
    {
        try {
            // Delegar TODA la lógica al servicio
            $products = $this->productService->getPaginatedProducts($request);
            
            // Regístro de auditoría 
            $this->logAction('listar', 'productos', null, [
                'filters' => $request->all()
            ]);
            
            // Respuesta usando métodos estándar del BaseController
            return $this->respondWithSuccess('products/index', [
                'products' => $products,
                'filters' => $request->only(['search', 'sort', 'order', 'per_page'])
            ]);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Listar productos');
        }
    }

    public function show(Product $product)
    {
        try {
            // Delegar la preparación de datos al servicio
            $viewData = $this->productService->getProductForDisplay($product);
            
            // Registro para auditoría
            $this->logAction('ver', 'producto', $product->id);
            
            // Respuesta sin lógica adicional
            return $this->respondWithSuccess('products/show', $viewData);
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Ver producto');
        }
    }

    public function store(StoreProductRequest $request)
    {
        try {
            // Form Request garantiza validación
            $data = $request->validated();
            
            // Delegar al servicio (TODA lógica incluida manipulación de imágenes/archivos)
            $product = $this->productService->createProduct($data);
            
            // Registro para auditoría
            $this->logAction('crear', 'producto', $product->id);
            
            return $this->redirectWithSuccess('products.index', [], 'Producto creado correctamente');
        } catch (\Throwable $e) {
            return $this->handleException($e, 'Crear producto');
        }
    }

    // Otros métodos
}
```

## Sistema de Notificaciones

Siguiendo la memoria del proyecto, mantenemos un sistema unificado de notificaciones:

- Para notificaciones desde el servidor, usar sesiones flash: `session()->flash('success', $message)`
- Para notificaciones desde el cliente, usar directamente la API de sonner (toast.success, toast.error)
- El componente FlashMessages detecta automáticamente estos mensajes

## Beneficios de Esta Arquitectura

- **Mantenibilidad**: Cada capa evoluciona independientemente
- **Testabilidad**: Puedes mockear repositorios o servicios sin tocar controllers
- **Escalabilidad**: Agregar nuevos módulos sigue el mismo patrón
- **Coherencia**: Mismo flujo para validaciones, mensajes flash y manejo de errores

## Checklist para Nuevos Módulos

- [ ] Migración para tabla(s)
- [ ] Modelo Eloquent con relaciones y scopes
- [ ] RepositoryInterface + Repository
- [ ] ServiceInterface + Service
- [ ] Form Requests (Store + Update)
- [ ] Controller (extendiendo de BaseController)
- [ ] Binding en RepositoryServiceProvider
- [ ] Routes en web.php

## Ejemplo de Implementación

El módulo de Usuarios implementa completamente esta arquitectura SOLID y puede servir como referencia:

**UserController.php**: Se limita a delegar al servicio y devolver respuestas
```php
public function show(User $user)
{
    try {
        // Obtener datos completamente preparados desde el servicio
        $viewData = $this->userService->getUserWithRolesAndStats($user);
        
        // Registrar acción para auditoría
        $this->logAction('ver', 'usuario', $user->id);
        
        // Responder sin manipular los datos
        return $this->respondWithSuccess('users/show', $viewData);
    } catch (\Throwable $e) {
        return $this->handleException($e, 'Ver usuario');
    }
}
```

**UserService.php**: Centraliza toda la lógica de negocio
```php
public function getUserWithRolesAndStats(User $user): array
{
    // Cargar usuario con sus roles
    $user = $this->getUserById($user->id);
    
    // Transformar datos para la vista
    $user->role_names = $user->roles->pluck('name')->toArray();
    
    // Obtener datos relacionados
    $allRoles = $this->getAllRoles();
    
    // Preparar datos completos para la vista
    return [
        'user' => $user,
        'allRoles' => $allRoles
    ];
}
```

Esta separación clara de responsabilidades garantiza mantenibilidad, testabilidad y escalabilidad.

Archivos de referencia:
- `app/Repositories/User/UserRepositoryInterface.php`
- `app/Repositories/User/UserRepository.php`
- `app/Services/User/UserServiceInterface.php`
- `app/Services/User/UserService.php`
- `app/Http/Requests/User/StoreUserRequest.php`
- `app/Http/Requests/User/UpdateUserRequest.php`
- `app/Http/Controllers/UserController.php`
