<?php

namespace App\Models\Swagger;

/**
 * @OA\Schema(
 *     schema="InventoryItemModelResource",
 *     title="Inventory Item Resource",
 *     description="Inventory item resource representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="warehouse_id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Laptop"),
 *     @OA\Property(property="description", type="string", example="Dell XPS 15"),
 *     @OA\Property(property="quantity", type="integer", example=10),
 *     @OA\Property(property="min_stock_level", type="integer", example=5),
 *     @OA\Property(property="unit_price", type="number", format="float", example=1200.50),
 *     @OA\Property(property="category", type="string", example="electronics"),
 *     @OA\Property(property="image", type="string", example="https://example.com/laptop.jpg", nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
 *     @OA\Property(property="deleted_at", type="string", format="date-time", example="2023-01-01T12:00:00Z", nullable=true)
 * )
 */
class InventoryItemResource
{
    // This is a dummy class for Swagger documentation
}
