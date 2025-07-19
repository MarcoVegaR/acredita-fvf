<?php

namespace App\Providers;

use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryInterface;
use App\Repositories\Role\RoleRepository;
use App\Repositories\Role\RoleRepositoryInterface;
use App\Repositories\Document\DocumentRepository;
use App\Repositories\Document\DocumentRepositoryInterface;
use App\Repositories\Image\ImageRepository;
use App\Repositories\Image\ImageRepositoryInterface;
use App\Repositories\Event\EventRepository;
use App\Repositories\Event\EventRepositoryInterface;
use App\Repositories\Zone\ZoneRepository;
use App\Repositories\Zone\ZoneRepositoryInterface;
use App\Repositories\Template\TemplateRepository;
use App\Repositories\Template\TemplateRepositoryInterface;
use App\Repositories\Area\AreaRepository;
use App\Repositories\Area\AreaRepositoryInterface;
use App\Repositories\Provider\EloquentProviderRepository;
use App\Repositories\Provider\ProviderRepositoryInterface;
use App\Repositories\Employee\EmployeeRepository;
use App\Repositories\Employee\EmployeeRepositoryInterface;

use App\Services\User\UserService;
use App\Services\User\UserServiceInterface;
use App\Services\Role\RoleService;
use App\Services\Role\RoleServiceInterface;
use App\Services\Document\DocumentService;
use App\Services\Document\DocumentServiceInterface;
use App\Services\Image\ImageService;
use App\Services\Image\ImageServiceInterface;
use App\Services\Event\EventService;
use App\Services\Event\EventServiceInterface;
use App\Services\Zone\ZoneService;
use App\Services\Zone\ZoneServiceInterface;
use App\Services\Template\TemplateService;
use App\Services\Template\TemplateServiceInterface;
use App\Services\Area\AreaService;
use App\Services\Area\AreaServiceInterface;
use App\Services\Provider\ProviderService;
use App\Services\Provider\ProviderServiceInterface;
use App\Services\Employee\EmployeeService;
use App\Services\Employee\EmployeeServiceInterface;
use App\Repositories\AccreditationRequest\AccreditationRequestRepository;
use App\Repositories\AccreditationRequest\AccreditationRequestRepositoryInterface;
use App\Services\AccreditationRequest\AccreditationRequestService;
use App\Services\AccreditationRequest\AccreditationRequestServiceInterface;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register repositories
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(RoleRepositoryInterface::class, RoleRepository::class);
        $this->app->bind(DocumentRepositoryInterface::class, DocumentRepository::class);
        $this->app->bind(ImageRepositoryInterface::class, ImageRepository::class);
        $this->app->bind(EventRepositoryInterface::class, EventRepository::class);
        $this->app->bind(ZoneRepositoryInterface::class, ZoneRepository::class);
        $this->app->bind(TemplateRepositoryInterface::class, TemplateRepository::class);
        $this->app->bind(AreaRepositoryInterface::class, AreaRepository::class);
        $this->app->bind(ProviderRepositoryInterface::class, EloquentProviderRepository::class);
        $this->app->bind(EmployeeRepositoryInterface::class, EmployeeRepository::class);
        $this->app->bind(AccreditationRequestRepositoryInterface::class, AccreditationRequestRepository::class);
        
        // Register services
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(RoleServiceInterface::class, RoleService::class);
        $this->app->bind(DocumentServiceInterface::class, DocumentService::class);
        $this->app->bind(ImageServiceInterface::class, ImageService::class);
        $this->app->bind(EventServiceInterface::class, EventService::class);
        $this->app->bind(ZoneServiceInterface::class, ZoneService::class);
        $this->app->bind(TemplateServiceInterface::class, TemplateService::class);
        $this->app->bind(AreaServiceInterface::class, AreaService::class);
        $this->app->bind(ProviderServiceInterface::class, ProviderService::class);
        $this->app->bind(EmployeeServiceInterface::class, EmployeeService::class);
        $this->app->bind(AccreditationRequestServiceInterface::class, AccreditationRequestService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
