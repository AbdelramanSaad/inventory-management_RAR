<?php

namespace App\Repositories;

use App\Models\InventoryItem;
use App\Repositories\Interfaces\InventoryItemRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class InventoryItemRepository implements InventoryItemRepositoryInterface
{
    /**
     * @var InventoryItem
     */
    protected $model;

    /**
     * InventoryItemRepository constructor.
     *
     * @param InventoryItem $model
     */
    public function __construct(InventoryItem $model)
    {
        $this->model = $model;
    }

    /**
     * Get all inventory items with optional filtering
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getFiltered(array $filters, int $perPage): LengthAwarePaginator
    {
        $warehouseId = $filters['warehouse_id'] ?? null;
        $userId = $filters['user_id'] ?? null;
        $cacheKey = "inventory_items_" . md5(json_encode($filters)) . "_page_" . request()->get('page', 1);
        
        if ($warehouseId) {
            $cacheKey .= "_warehouse_" . $warehouseId;
        }
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($filters, $perPage) {
            $query = $this->model->with(['user', 'warehouse']);
            
            if (isset($filters['category']) && $filters['category']) {
                $query->where('category', $filters['category']);
            }
            
            if (isset($filters['warehouse_id']) && $filters['warehouse_id']) {
                $query->where('warehouse_id', $filters['warehouse_id']);
            }
            
            if (isset($filters['below_min_stock']) && $filters['below_min_stock']) {
                $query->whereRaw('quantity <= min_stock_level');
            }
            
            if (isset($filters['search']) && $filters['search']) {
                $search = $filters['search'];
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }
            
            return $query->orderBy('created_at', 'desc')->paginate($perPage);
        });
    }

    /**
     * Find inventory item by ID
     *
     * @param int $id
     * @return InventoryItem|null
     */
    public function find(int $id): ?InventoryItem
    {
        return Cache::remember("inventory_item_{$id}", now()->addMinutes(5), function () use ($id) {
            return $this->model->with(['user', 'warehouse'])->find($id);
        });
    }

    /**
     * Create a new inventory item
     *
     * @param array $data
     * @return InventoryItem
     */
    public function create(array $data): InventoryItem
    {
        $item = $this->model->create($data);
        
        // Clear cache for inventory items list
        $this->clearCache($item->warehouse_id);
        
        return $item;
    }

    /**
     * Update an inventory item
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $item = $this->model->find($id);
        
        if (!$item) {
            return false;
        }
        
        $result = $item->update($data);
        
        // Clear cache for this item and inventory items list
        Cache::forget("inventory_item_{$id}");
        $this->clearCache($item->warehouse_id);
        
        return $result;
    }

    /**
     * Delete an inventory item
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $item = $this->model->find($id);
        
        if (!$item) {
            return false;
        }
        
        $warehouseId = $item->warehouse_id;
        $result = $item->delete();
        
        // Clear cache for this item and inventory items list
        Cache::forget("inventory_item_{$id}");
        $this->clearCache($warehouseId);
        
        return $result;
    }
    
    /**
     * Clear cache for inventory items
     *
     * @param int|null $warehouseId
     * @return void
     */
    protected function clearCache(?int $warehouseId = null): void
    {
        $cacheKeys = ['inventory_items_'];
        
        if ($warehouseId) {
            $cacheKeys[] = "inventory_items_warehouse_{$warehouseId}";
        }
        
        foreach ($cacheKeys as $key) {
            Cache::getStore()->flush();
        }
    }
}
