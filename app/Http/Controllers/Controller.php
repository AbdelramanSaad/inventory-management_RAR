<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\Info(
 *     title="Inventory Management and Audit System API",
 *     version="1.0.0",
 *     description="A comprehensive API for inventory management with audit logging, role-based access control, and real-time notifications",
 *     @OA\Contact(
 *         email="admin@inventory-system.com",
 *         name="API Support"
 *     ),
 *     @OA\License(
 *         name="MIT License",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for user authentication"
 * )
 *
 * @OA\Tag(
 *     name="Inventory Items",
 *     description="API Endpoints for inventory items management"
 * )
 *
 * @OA\Tag(
 *     name="Audit Logs",
 *     description="API Endpoints for audit logs"
 * )
 *
 * @OA\Tag(
 *     name="Warehouses",
 *     description="API Endpoints for warehouses management"
 * )
 *
 * // InventoryItemResource schema is defined in App\Http\Resources\Swagger\InventoryItemResourceSchema
 *
 *
 * // Schema definitions are moved to separate files in App\Http\Resources\Swagger namespace
 */

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
