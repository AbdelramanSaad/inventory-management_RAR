<?php

namespace App\Models\Swagger;

/**
 * @OA\Schema(
 *     schema="WarehouseModelResource",
 *     title="Warehouse Resource",
 *     description="Warehouse resource representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Main Warehouse"),
 *     @OA\Property(property="location", type="string", example="123 Storage St, City"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-01-01T12:00:00Z")
 * )
 */
class WarehouseResource
{
    // This is a dummy class for Swagger documentation
}
