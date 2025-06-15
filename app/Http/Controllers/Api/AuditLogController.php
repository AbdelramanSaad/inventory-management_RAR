<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @OA\Tag(
 *     name="Audit Logs",
 *     description="API Endpoints for Audit Logs management"
 * )
 */
class AuditLogController extends Controller
{
    /**
     * @var AuditLogService
     */
    protected $auditLogService;

    /**
     * AuditLogController constructor.
     *
     * @param AuditLogService $auditLogService
     */
    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    /**
     * Display a listing of the audit logs.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     * 
     * @OA\Get(
     *     path="/api/audit-logs",
     *     tags={"Audit Logs"},
     *     summary="List audit logs",
     *     description="Returns a paginated list of audit logs with filtering options",
     *     operationId="auditLogIndex",
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filter by audit log type",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"item_created", "item_updated", "item_deleted", "stock_adjusted"}
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="warehouse_id",
     *         in="query",
     *         description="Filter by warehouse ID",
     *         required=false,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="inventory_item_id",
     *         in="query",
     *         description="Filter by inventory item ID",
     *         required=false,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Number of items per page",
     *         required=false,
     *         @OA\Schema(
     *             type="integer",
     *             default=15
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/AuditLogResource")),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     )
     * )
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->only([
            'type',
            'warehouse_id',
        ]);

        // If user is not admin, restrict to their warehouse
        if (!$request->user()->isAdmin()) {
            $filters['warehouse_id'] = $request->user()->warehouse_id;
        }

        $auditLogs = $this->auditLogService->getFiltered($filters, $request->get('per_page', 15));

        return AuditLogResource::collection($auditLogs);
    }
}
