<?php

namespace App\Http\Resources\Swagger;

/**
 * @OA\Schema(
 *     schema="InventoryItemResource",
 *     title="Inventory Item Resource",
 *     description="Inventory item resource representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Laptop XPS 15"),
 *     @OA\Property(property="description", type="string", example="Dell XPS 15 laptop with 16GB RAM"),
 *     @OA\Property(property="quantity", type="integer", example=10),
 *     @OA\Property(property="min_stock_level", type="integer", example=5),
 *     @OA\Property(property="unit_price", type="number", format="float", example=1299.99),
 *     @OA\Property(property="category", type="string", enum={"electronics", "furniture", "clothing", "other"}, example="electronics"),
 *     @OA\Property(property="image", type="string", example="inventory/2023-06-09/user_1_laptop.jpg"),
 *     @OA\Property(property="image_url", type="string", example="http://localhost:8000/storage/inventory/2023-06-09/user_1_laptop.jpg"),
 *     @OA\Property(property="is_low_stock", type="boolean", example=false),
 *     @OA\Property(
 *         property="warehouse",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Main Warehouse"),
 *         @OA\Property(property="location", type="string", example="New York")
 *     ),
 *     @OA\Property(
 *         property="user",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="John Doe")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-06-09T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-06-09T12:30:00Z")
 * )
 */
class InventoryItemResourceSchema
{
    // This class is only used for Swagger documentation
}
