<?php

namespace App\Models\Swagger;

/**
 * @OA\Schema(
 *     schema="AuditLogBasicResource",
 *     title="Audit Log Resource",
 *     description="Audit log resource representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="user_name", type="string", example="John Doe"),
 *     @OA\Property(property="type", type="string", example="item_created"),
 *     @OA\Property(property="description", type="string", example="Created new inventory item"),
 *     @OA\Property(property="warehouse_id", type="integer", example=1),
 *     @OA\Property(property="user_id", type="integer", example=1),
 *     @OA\Property(property="inventory_item_id", type="integer", example=1, nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-01-01T12:00:00Z")
 * )
 */
class AuditLogResource
{
    // This is a dummy class for Swagger documentation
}
