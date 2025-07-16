<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\User;
use Illuminate\Http\Request;

class AreaManagerController extends Controller
{
    /**
     * Obtener usuarios disponibles para ser gerentes de área.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableManagers(Request $request)
    {
        try {
            $exceptAreaId = $request->input('except_area_id');
            
            // Consultar usuarios con rol area_manager activos
            $query = User::role('area_manager')
                ->where('active', true);
                
            // Filtrar para mostrar solo:
            // 1. Usuarios que no manejan ninguna área, O
            // 2. El gerente actual del área que se está editando (si existe)
            if ($exceptAreaId) {
                $area = Area::find($exceptAreaId);
                
                $query->where(function($q) use ($area) {
                    // Incluir usuarios que no manejan ninguna área
                    $q->whereDoesntHave('managedArea');
                    
                    // Incluir al gerente actual del área que se está editando (si existe)
                    if ($area && $area->manager_user_id) {
                        $q->orWhere('id', $area->manager_user_id);
                    }
                });
            } else {
                // Si no se está editando un área específica, solo mostrar usuarios sin áreas asignadas
                $query->whereDoesntHave('managedArea');
            }
            
            $managers = $query->get(['id', 'name', 'email']);
            
            return response()->json($managers);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al obtener gerentes disponibles: ' . $e->getMessage()], 500);
        }
    }
}
