<?php

namespace App\Http\Controllers;

use App\Models\AccreditationRequest;
use App\Models\Employee;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with role-filtered statistics.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $user = auth()->user();
        
        // Obtener estadísticas filtradas según el rol del usuario
        $stats = $this->getFilteredStats($user);
        
        return Inertia::render('dashboard', [
            'stats' => $stats,
            'user_role' => $user->roles->first()->name ?? 'guest'
        ]);
    }
    
    /**
     * Get filtered statistics based on user role
     */
    private function getFilteredStats($user): array
    {
        // Estadísticas base
        $stats = [
            'employees' => [
                'total' => 0,
                'active' => 0,
                'by_type' => []
            ],
            'providers' => [
                'total' => 0,
                'internal' => 0,
                'external' => 0
            ],
            'accreditations' => [
                'total' => 0,
                'draft' => 0,
                'submitted' => 0,
                'approved' => 0,
                'pending' => 0
            ]
        ];
        
        // Filtrar según el rol del usuario
        if ($user->hasRole(['admin', 'security_manager'])) {
            // Administradores ven todas las estadísticas
            $stats = $this->getAllStats();
            
        } elseif ($user->hasRole('area_manager')) {
            // Area managers ven estadísticas de su área
            if ($user->managedArea) {
                $stats = $this->getAreaManagerStats($user->managedArea->id);
            }
            
        } elseif ($user->hasRole('provider')) {
            // Providers ven solo sus estadísticas
            if ($user->provider) {
                $stats = $this->getProviderStats($user->provider->id);
            }
        }
        
        return $stats;
    }
    
    /**
     * Get all statistics (for admin/security_manager)
     */
    private function getAllStats(): array
    {
        return [
            'employees' => [
                'total' => Employee::count(),
                'active' => Employee::where('active', true)->count(),
                'by_type' => Employee::select('provider_id')
                    ->join('providers', 'employees.provider_id', '=', 'providers.id')
                    ->select('providers.type', DB::raw('count(*) as count'))
                    ->groupBy('providers.type')
                    ->pluck('count', 'type')
                    ->toArray()
            ],
            'providers' => [
                'total' => Provider::count(),
                'internal' => Provider::where('type', 'internal')->count(),
                'external' => Provider::where('type', 'external')->count()
            ],
            'accreditations' => [
                'total' => AccreditationRequest::count(),
                'draft' => AccreditationRequest::where('status', 'draft')->count(),
                'submitted' => AccreditationRequest::where('status', 'submitted')->count(),
                'approved' => AccreditationRequest::where('status', 'approved')->count(),
                'pending' => AccreditationRequest::whereIn('status', ['submitted', 'under_review'])->count()
            ]
        ];
    }
    
    /**
     * Get statistics for area manager
     */
    private function getAreaManagerStats($areaId): array
    {
        // Proveedores del área
        $areaProviders = Provider::where('area_id', $areaId)->pluck('id');
        
        return [
            'employees' => [
                'total' => Employee::whereIn('provider_id', $areaProviders)->count(),
                'active' => Employee::whereIn('provider_id', $areaProviders)
                    ->where('active', true)->count(),
                'by_type' => Employee::whereIn('provider_id', $areaProviders)
                    ->join('providers', 'employees.provider_id', '=', 'providers.id')
                    ->select('providers.type', DB::raw('count(*) as count'))
                    ->groupBy('providers.type')
                    ->pluck('count', 'type')
                    ->toArray()
            ],
            'providers' => [
                'total' => Provider::where('area_id', $areaId)->count(),
                'internal' => Provider::where('area_id', $areaId)->where('type', 'internal')->count(),
                'external' => Provider::where('area_id', $areaId)->where('type', 'external')->count()
            ],
            'accreditations' => [
                'total' => AccreditationRequest::whereHas('employee', function ($q) use ($areaProviders) {
                    $q->whereIn('provider_id', $areaProviders);
                })->count(),
                'draft' => AccreditationRequest::whereHas('employee', function ($q) use ($areaProviders) {
                    $q->whereIn('provider_id', $areaProviders);
                })->where('status', 'draft')->count(),
                'submitted' => AccreditationRequest::whereHas('employee', function ($q) use ($areaProviders) {
                    $q->whereIn('provider_id', $areaProviders);
                })->where('status', 'submitted')->count(),
                'approved' => AccreditationRequest::whereHas('employee', function ($q) use ($areaProviders) {
                    $q->whereIn('provider_id', $areaProviders);
                })->where('status', 'approved')->count(),
                'pending' => AccreditationRequest::whereHas('employee', function ($q) use ($areaProviders) {
                    $q->whereIn('provider_id', $areaProviders);
                })->whereIn('status', ['submitted', 'under_review'])->count()
            ]
        ];
    }
    
    /**
     * Get statistics for provider
     */
    private function getProviderStats($providerId): array
    {
        return [
            'employees' => [
                'total' => Employee::where('provider_id', $providerId)->count(),
                'active' => Employee::where('provider_id', $providerId)
                    ->where('active', true)->count(),
                'by_type' => [] // Provider only sees their own type
            ],
            'providers' => [
                'total' => 1, // Only their own provider
                'internal' => Provider::where('id', $providerId)->where('type', 'internal')->count(),
                'external' => Provider::where('id', $providerId)->where('type', 'external')->count()
            ],
            'accreditations' => [
                'total' => AccreditationRequest::whereHas('employee', function ($q) use ($providerId) {
                    $q->where('provider_id', $providerId);
                })->count(),
                'draft' => AccreditationRequest::whereHas('employee', function ($q) use ($providerId) {
                    $q->where('provider_id', $providerId);
                })->where('status', 'draft')->count(),
                'submitted' => AccreditationRequest::whereHas('employee', function ($q) use ($providerId) {
                    $q->where('provider_id', $providerId);
                })->where('status', 'submitted')->count(),
                'approved' => AccreditationRequest::whereHas('employee', function ($q) use ($providerId) {
                    $q->where('provider_id', $providerId);
                })->where('status', 'approved')->count(),
                'pending' => AccreditationRequest::whereHas('employee', function ($q) use ($providerId) {
                    $q->where('provider_id', $providerId);
                })->whereIn('status', ['submitted', 'under_review'])->count()
            ]
        ];
    }
}
