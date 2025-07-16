<?php

namespace App\Services\Area;

use App\Models\Area;
use App\Repositories\Area\AreaRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AreaService implements AreaServiceInterface
{
    /**
     * @var AreaRepositoryInterface
     */
    protected $areaRepository;

    /**
     * AreaService constructor.
     *
     * @param AreaRepositoryInterface $areaRepository
     */
    public function __construct(AreaRepositoryInterface $areaRepository)
    {
        $this->areaRepository = $areaRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function getPaginatedAreas(Request $request): LengthAwarePaginator
    {
        $perPage = (int) $request->input('per_page', 10);
        
        $filters = [];
        
        // Add search filter if provided
        if ($request->has('search')) {
            $filters['search'] = $request->input('search');
        }
        
        // Add active status filter if provided
        if ($request->has('active')) {
            $filters['active'] = $request->input('active');
        }
        
        // Add code filter if provided
        if ($request->has('code')) {
            $filters['code'] = $request->input('code');
        }
        
        // Set up sorting options
        $sortOptions = [
            'field' => $request->input('sort', 'id'),
            'direction' => $request->input('order', 'desc')
        ];
        
        return $this->areaRepository->paginate(
            $perPage,
            ['manager'], // Cargar la relación con el manager
            $filters,
            $sortOptions
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAreaStats(): array
    {
        return $this->areaRepository->getCountsByStatus();
    }

    /**
     * {@inheritdoc}
     */
    public function createArea(array $data): Area
    {
        // Verificar si ya existe un área con el mismo código
        if (isset($data['code']) && $this->areaRepository->findByCode($data['code'])) {
            throw new \Exception("Ya existe un área con el código '{$data['code']}'");
        }
        
        DB::beginTransaction();
        try {
            $area = $this->areaRepository->create($data);
            DB::commit();
            return $area;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAreaById(int $id): Area
    {
        $area = $this->areaRepository->find($id);
        
        if (!$area) {
            throw new \Exception("Área no encontrada");
        }
        
        return $area;
    }
    
    /**
     * {@inheritdoc}
     */
    public function getAreaByUuid(string $uuid): Area
    {
        $area = $this->areaRepository->findByUuid($uuid);
        
        if (!$area) {
            throw new \Exception("Área no encontrada");
        }
        
        return $area;
    }

    /**
     * {@inheritdoc}
     */
    public function updateArea(Area $area, array $data): Area
    {
        // Verificar si ya existe otra área con el mismo código
        if (isset($data['code']) && $data['code'] !== $area->code) {
            $existingArea = $this->areaRepository->findByCode($data['code']);
            if ($existingArea && $existingArea->id !== $area->id) {
                throw new \Exception("Ya existe un área con el código '{$data['code']}'");
            }
        }
        
        DB::beginTransaction();
        try {
            $area = $this->areaRepository->update($area->id, $data);
            DB::commit();
            return $area;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteArea(Area $area): bool
    {
        // En el futuro podríamos verificar aquí si hay proveedores asociados
        // y bloquear la eliminación o eliminar en cascada según la lógica de negocio
        
        DB::beginTransaction();
        try {
            $result = $this->areaRepository->delete($area->id);
            DB::commit();
            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
