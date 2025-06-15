<?php

namespace App\Repositories;

use App\Models\AuditLog;
use App\Repositories\Interfaces\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    /**
     * @var AuditLog
     */
    protected $model;

    /**
     * AuditLogRepository constructor.
     *
     * @param AuditLog $model
     */
    public function __construct(AuditLog $model)
    {
        $this->model = $model;
    }

    /**
     * Get all audit logs with optional filtering
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getFiltered(array $filters, int $perPage): LengthAwarePaginator
    {
        $warehouseId = $filters['warehouse_id'] ?? null;
        $cacheKey = "audit_logs_" . md5(json_encode($filters)) . "_page_" . request()->get('page', 1);
        
        if ($warehouseId) {
            $cacheKey .= "_warehouse_" . $warehouseId;
        }
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($filters, $perPage) {
            $query = $this->model->with(['user', 'warehouse', 'inventoryItem']);
            
            if (isset($filters['type']) && $filters['type']) {
                $query->where('type', $filters['type']);
            }
            
            if (isset($filters['warehouse_id']) && $filters['warehouse_id']) {
                $query->where('warehouse_id', $filters['warehouse_id']);
            }
            
            return $query->orderBy('created_at', 'desc')->paginate($perPage);
        });
    }

    /**
     * Create a new audit log
     *
     * @param array $data
     * @return AuditLog
     */
    public function create(array $data): AuditLog
    {
        $log = $this->model->create($data);
        
        // Clear cache for audit logs list
        $this->clearCache($log->warehouse_id);
        
        return $log;
    }
    
    /**
     * Clear cache for audit logs
     *
     * @param int|null $warehouseId
     * @return void
     */
    protected function clearCache(?int $warehouseId = null): void
    {
        $cacheKeys = ['audit_logs_'];
        
        if ($warehouseId) {
            $cacheKeys[] = "audit_logs_warehouse_{$warehouseId}";
        }
        
        foreach ($cacheKeys as $key) {
            Cache::getStore()->flush();
        }
    }
}
