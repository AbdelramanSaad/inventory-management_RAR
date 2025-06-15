<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Notifications\InventoryItemNotification;
use App\Repositories\Interfaces\InventoryItemRepositoryInterface;
use App\Repositories\Interfaces\AuditLogRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventoryItemService
{
    /**
     * @var InventoryItemRepositoryInterface
     */
    protected $inventoryItemRepository;

    /**
     * @var AuditLogRepositoryInterface
     */
    protected $auditLogRepository;

    /**
     * InventoryItemService constructor.
     *
     * @param InventoryItemRepositoryInterface $inventoryItemRepository
     * @param AuditLogRepositoryInterface $auditLogRepository
     */
    public function __construct(
        InventoryItemRepositoryInterface $inventoryItemRepository,
        AuditLogRepositoryInterface $auditLogRepository
    ) {
        $this->inventoryItemRepository = $inventoryItemRepository;
        $this->auditLogRepository = $auditLogRepository;
    }

    /**
     * Get filtered inventory items
     *
     * @param array $filters
     * @param int $perPage
     * @return mixed
     */
    public function getFiltered(array $filters, int $perPage = 15)
    {
        return $this->inventoryItemRepository->getFiltered($filters, $perPage);
    }

    /**
     * Find inventory item by ID
     *
     * @param int $id
     * @return InventoryItem|null
     */
    public function find(int $id): ?InventoryItem
    {
        return $this->inventoryItemRepository->find($id);
    }

    /**
     * Create a new inventory item
     *
     * @param array $data
     * @return InventoryItem
     */
    public function create(array $data): InventoryItem
    {
        return DB::transaction(function () use ($data) {
            // Handle image upload if present
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                $data['image'] = $this->uploadImage($data['image']);
            }

            $user = Auth::user();
            $data['user_id'] = $user->id;

            $item = $this->inventoryItemRepository->create($data);

            // Create audit log
            $this->auditLogRepository->create([
                'user_name' => $user->name,
                'type' => 'item_created',
                'description' => "Created inventory item: {$item->name}",
                'warehouse_id' => $item->warehouse_id,
                'user_id' => $user->id,
                'inventory_item_id' => $item->id,
                'created_at' => now(),
            ]);

            // Send notifications
            $this->sendItemCreatedNotifications($item);

            return $item;
        });
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
        return DB::transaction(function () use ($id, $data) {
            $item = $this->inventoryItemRepository->find($id);

            if (!$item) {
                return false;
            }

            $oldQuantity = $item->quantity;
            $stockAdjusted = isset($data['quantity']) && $data['quantity'] != $oldQuantity;

            // Handle image upload if present
            if (isset($data['image']) && $data['image'] instanceof UploadedFile) {
                // Delete old image if exists
                if ($item->image) {
                    Storage::delete($item->image);
                }
                $data['image'] = $this->uploadImage($data['image']);
            }

            $result = $this->inventoryItemRepository->update($id, $data);

            if ($result) {
                $user = Auth::user();
                $item->refresh();

                // Create audit log
                $logType = $stockAdjusted ? 'stock_adjusted' : 'item_updated';
                $description = $stockAdjusted 
                    ? "Stock adjusted for {$item->name} from {$oldQuantity} to {$item->quantity}"
                    : "Updated inventory item: {$item->name}";

                $this->auditLogRepository->create([
                    'user_name' => $user->name,
                    'type' => $logType,
                    'description' => $description,
                    'warehouse_id' => $item->warehouse_id,
                    'user_id' => $user->id,
                    'inventory_item_id' => $item->id,
                    'created_at' => now(),
                ]);

                // Send notifications
                if ($stockAdjusted) {
                    $this->sendStockAdjustedNotifications($item, $oldQuantity);
                } else {
                    $this->sendItemUpdatedNotifications($item);
                }

                // Check if item is below min stock level
                if ($item->isLowStock()) {
                    $this->sendLowStockNotifications($item);
                }
            }

            return $result;
        });
    }

    /**
     * Delete an inventory item
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id) {
            $item = $this->inventoryItemRepository->find($id);

            if (!$item) {
                return false;
            }

            $result = $this->inventoryItemRepository->delete($id);

            if ($result) {
                $user = Auth::user();

                // Create audit log
                $this->auditLogRepository->create([
                    'user_name' => $user->name,
                    'type' => 'item_deleted',
                    'description' => "Deleted inventory item: {$item->name}",
                    'warehouse_id' => $item->warehouse_id,
                    'user_id' => $user->id,
                    'inventory_item_id' => $item->id,
                    'created_at' => now(),
                ]);
            }

            return $result;
        });
    }

    /**
     * Upload inventory item image
     *
     * @param UploadedFile $image
     * @return string
     */
    protected function uploadImage(UploadedFile $image): string
    {
        $userId = Auth::id();
        $path = "inventory/" . date('Y-m-d') . "/user_{$userId}";
        return $image->store($path, 'public');
    }

    /**
     * Send notifications when an item is created
     *
     * @param InventoryItem $item
     * @return void
     */
    protected function sendItemCreatedNotifications(InventoryItem $item): void
    {
        // Get admin users and warehouse managers for this warehouse
        $users = $this->getNotifiableUsers($item->warehouse_id);

        foreach ($users as $user) {
            $user->notify(new InventoryItemNotification(
                $item,
                'item_created',
                "New inventory item '{$item->name}' has been created"
            ));
        }
    }

    /**
     * Send notifications when an item is updated
     *
     * @param InventoryItem $item
     * @return void
     */
    protected function sendItemUpdatedNotifications(InventoryItem $item): void
    {
        // Get admin users and warehouse managers for this warehouse
        $users = $this->getNotifiableUsers($item->warehouse_id);

        foreach ($users as $user) {
            $user->notify(new InventoryItemNotification(
                $item,
                'item_updated',
                "Inventory item '{$item->name}' has been updated"
            ));
        }
    }

    /**
     * Send notifications when stock is adjusted
     *
     * @param InventoryItem $item
     * @param int $oldQuantity
     * @return void
     */
    protected function sendStockAdjustedNotifications(InventoryItem $item, int $oldQuantity): void
    {
        // Get admin users and warehouse managers for this warehouse
        $users = $this->getNotifiableUsers($item->warehouse_id);

        foreach ($users as $user) {
            $user->notify(new InventoryItemNotification(
                $item,
                'stock_adjusted',
                "Stock for '{$item->name}' has been adjusted from {$oldQuantity} to {$item->quantity}"
            ));
        }
    }

    /**
     * Send notifications when stock is low
     *
     * @param InventoryItem $item
     * @return void
     */
    protected function sendLowStockNotifications(InventoryItem $item): void
    {
        // Get admin users and warehouse managers for this warehouse
        $users = $this->getNotifiableUsers($item->warehouse_id);

        foreach ($users as $user) {
            $user->notify(new InventoryItemNotification(
                $item,
                'low_stock',
                "Low stock alert: '{$item->name}' is below minimum stock level ({$item->quantity}/{$item->min_stock_level})"
            ));
        }
    }

    /**
     * Get users that should be notified (admins and warehouse managers)
     *
     * @param int $warehouseId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getNotifiableUsers(int $warehouseId)
    {
        return \App\Models\User::where(function ($query) use ($warehouseId) {
            $query->where('role', 'admin')
                  ->orWhere(function ($query) use ($warehouseId) {
                      $query->where('role', 'warehouse_manager')
                            ->where('warehouse_id', $warehouseId);
                  });
        })->get();
    }
}
