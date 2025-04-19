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
use App\Services\User\UserService;
use App\Services\User\UserServiceInterface;
use App\Services\Role\RoleService;
use App\Services\Role\RoleServiceInterface;
use App\Services\Document\DocumentService;
use App\Services\Document\DocumentServiceInterface;
use App\Services\Image\ImageService;
use App\Services\Image\ImageServiceInterface;
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
        
        // Register services
        $this->app->bind(UserServiceInterface::class, UserService::class);
        $this->app->bind(RoleServiceInterface::class, RoleService::class);
        $this->app->bind(DocumentServiceInterface::class, DocumentService::class);
        $this->app->bind(ImageServiceInterface::class, ImageService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
