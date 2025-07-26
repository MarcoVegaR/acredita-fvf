<?php

namespace App\Services\Provider;

use App\Models\Provider;
use App\Models\User;
use App\Repositories\Provider\ProviderRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
// use Illuminate\Support\Facades\Mail;
// use App\Mail\WelcomeProviderMail;
// use App\Mail\PasswordResetMail;

class ProviderService implements ProviderServiceInterface
{
    /**
     * @var ProviderRepositoryInterface
     */
    protected $providerRepository;

    /**
     * ProviderService constructor.
     * 
     * @param ProviderRepositoryInterface $providerRepository
     */
    public function __construct(ProviderRepositoryInterface $providerRepository)
    {
        $this->providerRepository = $providerRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedProviders(Request $request): LengthAwarePaginator
    {
        $perPage = (int) $request->input('per_page', 10);
        $user = auth()->user();
        
        $filters = [];
        
        // Add search filter if provided
        if ($request->has('search')) {
            $filters['search'] = $request->input('search');
        }
        
        // Add area filter if provided
        if ($request->has('area_id')) {
            $filters['area_id'] = $request->input('area_id');
            \Illuminate\Support\Facades\Log::info('Filtro por área específica', [
                'area_id' => $request->input('area_id')
            ]);
        }
        
        // Si el usuario es area_manager y no se ha especificado un filtro de área,
        // filtrar automáticamente por las áreas que gestiona
        else if ($user && $user->hasRole('area_manager')) {
            $managedAreas = $user->managedAreas()->pluck('id')->toArray();
            if (!empty($managedAreas)) {
                $filters['area_ids'] = $managedAreas;
                \Illuminate\Support\Facades\Log::info('Filtro por áreas gestionadas', [
                    'area_ids' => $managedAreas,
                    'role' => $user->getRoleNames()->first(),
                    'user_id' => $user->id,
                    'user_name' => $user->name
                ]);
            }
        }
        
        // Add type filter if provided
        if ($request->has('type')) {
            $filters['type'] = $request->input('type');
            \Illuminate\Support\Facades\Log::info('Filtro por tipo', [
                'type' => $request->input('type')
            ]);
        }
        
        // Add active status filter if provided
        if ($request->has('active')) {
            $filters['active'] = $request->input('active');
        }
        
        // Set up sorting options
        $sortOptions = [
            'field' => $request->input('sort', 'created_at'),
            'direction' => $request->input('order', 'desc')
        ];
        
        return $this->providerRepository->paginate(
            $perPage,
            [], // Relations are included by default in repository
            $filters,
            $sortOptions
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getProviderByUuid(string $uuid): Provider
    {
        $provider = $this->providerRepository->findByUuid($uuid);
        
        if (!$provider) {
            throw new \Exception("Proveedor no encontrado");
        }
        
        return $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function createExternalProvider(array $data): Provider
    {
        // Verificar unicidad de RIF
        if (Provider::where('rif', $data['rif'])->exists()) {
            throw new \Exception("Ya existe un proveedor con este RIF");
        }
        
        // Verificar unicidad de email
        if (User::where('email', $data['user']['email'])->exists()) {
            throw new \Exception("Ya existe un usuario con este email");
        }
        
        // Generar contraseña si no se proporciona
        $plainPassword = '';
        if (!isset($data['user']['password']) || empty($data['user']['password'])) {
            $plainPassword = Str::random(10);
            $data['user']['password'] = Hash::make($plainPassword);
        } else {
            $plainPassword = $data['user']['password'];
            $data['user']['password'] = Hash::make($data['user']['password']);
        }
        
        // Crear proveedor externo
        $provider = $this->providerRepository->createExternal($data);
        
        // Enviar email de bienvenida (comentado hasta configurar SMTP)
        // if ($plainPassword) {
        //     Mail::to($provider->user->email)->send(new WelcomeProviderMail($provider, $plainPassword));
        // }
        
        return $provider;
    }

    /**
     * {@inheritdoc}
     */
    public function createInternalProvider(int $areaId, ?int $managerUserId = null, array $data = []): Provider
    {
        return $this->providerRepository->createInternal($areaId, $managerUserId, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function updateProvider(string $uuid, array $data): Provider
    {
        $provider = $this->getProviderByUuid($uuid);
        
        // Verificar unicidad de RIF si se está cambiando
        if (isset($data['rif']) && $data['rif'] !== $provider->rif) {
            if (Provider::where('rif', $data['rif'])->exists()) {
                throw new \Exception("Ya existe un proveedor con este RIF");
            }
        }
        
        // Verificar unicidad de email si se está cambiando
        if (isset($data['user']['email']) && $data['user']['email'] !== $provider->user->email) {
            if (User::where('email', $data['user']['email'])->exists()) {
                throw new \Exception("Ya existe un usuario con este email");
            }
        }
        
        // Actualizar proveedor
        return $this->providerRepository->updateProvider($uuid, $data);
    }

    /**
     * {@inheritdoc}
     */
    public function toggleActiveProvider(string $uuid, bool $active): void
    {
        $this->providerRepository->toggleActive($uuid, $active);
    }

    /**
     * {@inheritdoc}
     */
    public function resetProviderPassword(string $uuid): void
    {
        $provider = $this->getProviderByUuid($uuid);
        
        // Solo se puede resetear contraseña para proveedores externos
        if ($provider->type !== 'external') {
            throw new \Exception("Solo se puede restablecer la contraseña para proveedores externos");
        }
        
        // Generar nueva contraseña
        $plainPassword = Str::random(10);
        $hashedPassword = Hash::make($plainPassword);
        
        // Actualizar contraseña
        DB::transaction(function () use ($provider, $hashedPassword) {
            $provider->user->password = $hashedPassword;
            $provider->user->save();
        });
        
        // Enviar email con nueva contraseña (comentado hasta configurar SMTP)
        // Mail::to($provider->user->email)->send(new PasswordResetMail($provider, $plainPassword));
    }
    
    /**
     * {@inheritdoc}
     */
    public function getProviderForDisplay(string $uuid): array
    {
        $provider = $this->getProviderByUuid($uuid);
        
        // Si es un proveedor externo, cargar áreas disponibles para posible cambio
        $areas = [];
        if ($provider->type === 'external') {
            // Aquí se pueden cargar áreas si se necesitan para el formulario de edición
            // $areas = Area::where('active', true)->get();
        }
        
        return [
            'provider' => $provider,
            'areas' => $areas,
            'canResetPassword' => $provider->type === 'external'
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAccessibleProviders(): \Illuminate\Database\Eloquent\Collection
    {
        $user = auth()->user();
        
        if (!$user) {
            return collect();
        }
        
        // Admin and security_manager can access all providers
        if ($user->hasRole('admin') || $user->hasRole('security_manager')) {
            return $this->providerRepository->all();
        }
        
        // Area manager can access providers in their areas
        if ($user->hasRole('area_manager')) {
            // Get the areas managed by this user
            $managedAreas = $user->managedAreas()->pluck('id')->toArray();
            
            // Get providers from these areas
            return $this->providerRepository->findByAreaIds($managedAreas);
        }
        
        // Provider users can only access their own provider
        if ($user->hasRole('provider')) {
            $provider = $user->provider;
            if ($provider) {
                // Create a new Eloquent Collection with the provider
                return new \Illuminate\Database\Eloquent\Collection([$provider]);
            }
        }
        
        // Return an empty Eloquent Collection instead of a support Collection
        return new \Illuminate\Database\Eloquent\Collection();
    }
}
