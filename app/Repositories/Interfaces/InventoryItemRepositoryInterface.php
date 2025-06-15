<?php

namespace App\Repositories\Interfaces;

use App\Models\InventoryItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface InventoryItemRepositoryInterface
{
    /**
     * Get all inventory items with optional filtering
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getFiltered(array $filters, int $perPage): LengthAwarePaginator;

    /**
     * Find inventory item by ID
     *
     * @param int $id
     * @return InventoryItem|null
     */
    public function find(int $id): ?InventoryItem;

    /**
     * Create a new inventory item
     *
     * @param array $data
     * @return InventoryItem
     */
    public function create(array $data): InventoryItem;

    /**
     * Update an inventory item
     *
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update(int $id, array $data): bool;

    /**
     * Delete an inventory item
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool;
}
