<?php

namespace App\Http\Resources\Swagger;

/**
 * @OA\Schema(
 *     schema="AuditLogResource",
 *     title="Audit Log Resource",
 *     description="Audit log resource representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", enum={"item_created", "item_updated", "item_deleted", "stock_adjusted"}, example="item_created"),
 *     @OA\Property(property="description", type="string", example="Created inventory item: Laptop XPS 15"),
 *     @OA\Property(property="user_name", type="string", example="John Doe"),
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
 *     @OA\Property(
 *         property="inventory_item",
 *         type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Laptop XPS 15")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-06-09T12:00:00Z")
 * )
 */
class AuditLogResourceSchema
{
    // This class is only used for Swagger documentation
}
