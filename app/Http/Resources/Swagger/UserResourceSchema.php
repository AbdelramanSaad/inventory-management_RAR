<?php

namespace App\Http\Resources\Swagger;

/**
 * @OA\Schema(
 *     schema="UserResource",
 *     title="User Resource",
 *     description="User resource representation",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john@example.com"),
 *     @OA\Property(property="role", type="string", enum={"admin", "warehouse_manager", "inventory_clerk"}, example="admin"),
 *     @OA\Property(property="warehouse_id", type="integer", example=1, nullable=true),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2023-06-09T12:00:00Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2023-06-09T12:30:00Z")
 * )
 */
class UserResourceSchema
{
    // This class is only used for Swagger documentation
}
