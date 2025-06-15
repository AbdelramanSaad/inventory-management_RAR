<?php

namespace App\Http\Resources\Swagger;

/**
 * @OA\Schema(
 *     schema="WarehouseResource",
 *     title="Warehouse Resource",
 *     description="Warehouse resource representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Main Warehouse"),
 *     @OA\Property(property="location", type="string", example="123 Storage St, Warehouse City"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-06-09T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-06-09T12:30:00Z")
 * )
 */
class WarehouseResourceSchema
{
    // This class is only used for Swagger documentation
}
