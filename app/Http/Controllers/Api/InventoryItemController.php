<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInventoryItemRequest;
use App\Http\Requests\UpdateInventoryItemRequest;
use App\Http\Resources\InventoryItemResource;
use App\Services\InventoryItemService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * @OA\Tag(
 *     name="Inventory Items",
 *     description="API Endpoints for Inventory Items management"
 * )
 */
class InventoryItemController extends Controller
{
    /**
     * @var InventoryItemService
     */
    protected $inventoryItemService;

    /**
     * InventoryItemController constructor.
     *
     * @param InventoryItemService $inventoryItemService
     */
    public function __construct(InventoryItemService $inventoryItemService)
    {
        $this->inventoryItemService = $inventoryItemService;
    }

    /**
     * Display a listing of the inventory items.
     *
     * @param Request $request
     * @return AnonymousResourceCollection
     * 
     * @OA\Get(
     *     path="/api/inventory-items",
     *     tags={"Inventory Items"},
     *     summary="List inventory items",
     *     description="Returns a paginated list of inventory items with filtering options",
     *     operationId="inventoryItemIndex",
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         description="Filter by category",
     *         required=false,
     *         @OA\Schema(
     *             type="string",
     *             enum={"electronics", "furniture", "clothing", "other"}
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
     *         name="below_min_stock",
     *         in="query",
     *         description="Filter items with quantity below min_stock_level",
     *         required=false,
     *         @OA\Schema(
     *             type="boolean"
     *         )
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Search in name or description",
     *         required=false,
     *         @OA\Schema(
     *             type="string"
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
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/InventoryItemResource")),
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
        try {
            $filters = $request->only([
                'category',
                'warehouse_id',
                'below_min_stock',
                'search',
            ]);

            // If user is not admin, restrict to their warehouse
            if (!$request->user()->isAdmin()) {
                $filters['warehouse_id'] = $request->user()->warehouse_id;
            }

            $inventoryItems = $this->inventoryItemService->getFiltered($filters, $request->get('per_page', 15));
            
            return InventoryItemResource::collection($inventoryItems);
        } catch (\Exception $e) {
            // Log the error with detailed information
            \Illuminate\Support\Facades\Log::error('InventoryItem Index Error: ' . $e->getMessage());
            \Illuminate\Support\Facades\Log::error('Error Stack Trace: ' . $e->getTraceAsString());
            
            return response()->json(['error' => 'Error retrieving inventory items: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created inventory item.
     *
     * @param StoreInventoryItemRequest $request
     * @return InventoryItemResource
     * 
     * @OA\Post(
     *     path="/api/inventory-items",
     *     tags={"Inventory Items"},
     *     summary="Create a new inventory item",
     *     description="Creates a new inventory item with image upload",
     *     operationId="inventoryItemStore",
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="Laptop XPS 15"),
     *                 @OA\Property(property="description", type="string", example="Dell XPS 15 laptop with 16GB RAM"),
     *                 @OA\Property(property="quantity", type="integer", example=10),
     *                 @OA\Property(property="min_stock_level", type="integer", example=3),
     *                 @OA\Property(property="unit_price", type="number", format="float", example=1299.99),
     *                 @OA\Property(property="category", type="string", enum={"electronics", "furniture", "clothing", "other"}, example="electronics"),
     *                 @OA\Property(property="warehouse_id", type="integer", example=1),
     *                 @OA\Property(property="image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Inventory item created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/InventoryItemResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function store(StoreInventoryItemRequest $request): InventoryItemResource
    {
        $inventoryItem = $this->inventoryItemService->create($request->validated());

        return new InventoryItemResource($inventoryItem);
    }

    /**
     * Display the specified inventory item.
     *
     * @param int $id
     * @return InventoryItemResource|JsonResponse
     * 
     * @OA\Get(
     *     path="/api/inventory-items/{id}",
     *     tags={"Inventory Items"},
     *     summary="Get inventory item details",
     *     description="Returns details of a specific inventory item",
     *     operationId="inventoryItemShow",
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Inventory item ID",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful operation",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/InventoryItemResource")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Inventory item not found"
     *     )
     * )
     */
    public function show(int $id)
    {
        $inventoryItem = $this->inventoryItemService->find($id);

        if (!$inventoryItem) {
            return response()->json(['message' => 'Inventory item not found'], 404);
        }

        // Check if user has access to this item
        $user = auth()->user();
        if (!$user->isAdmin() && $user->warehouse_id !== $inventoryItem->warehouse_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return new InventoryItemResource($inventoryItem);
    }

    /**
     * Update the specified inventory item.
     *
     * @param UpdateInventoryItemRequest $request
     * @param int $id
     * @return JsonResponse
     * 
     * @OA\Put(
     *     path="/api/inventory-items/{id}",
     *     tags={"Inventory Items"},
     *     summary="Update an inventory item",
     *     description="Updates an existing inventory item including stock adjustment",
     *     operationId="inventoryItemUpdate",
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Inventory item ID",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 @OA\Property(property="name", type="string", example="Updated Laptop XPS 15"),
     *                 @OA\Property(property="description", type="string", example="Updated Dell XPS 15 laptop with 32GB RAM"),
     *                 @OA\Property(property="quantity", type="integer", example=15),
     *                 @OA\Property(property="min_stock_level", type="integer", example=5),
     *                 @OA\Property(property="unit_price", type="number", format="float", example=1499.99),
     *                 @OA\Property(property="category", type="string", enum={"electronics", "furniture", "clothing", "other"}, example="electronics"),
     *                 @OA\Property(property="image", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Inventory item updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Inventory item updated successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Inventory item not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     )
     * )
     */
    public function update(UpdateInventoryItemRequest $request, int $id): JsonResponse
    {
        $result = $this->inventoryItemService->update($id, $request->validated());

        if (!$result) {
            return response()->json(['message' => 'Inventory item not found'], 404);
        }

        return response()->json(['message' => 'Inventory item updated successfully']);
    }

    /**
     * Remove the specified inventory item.
     *
     * @param int $id
     * @return JsonResponse
     * 
     * @OA\Delete(
     *     path="/api/inventory-items/{id}",
     *     tags={"Inventory Items"},
     *     summary="Delete an inventory item",
     *     description="Soft deletes an inventory item (admin only)",
     *     operationId="inventoryItemDestroy",
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Inventory item ID",
     *         required=true,
     *         @OA\Schema(
     *             type="integer"
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Inventory item deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Inventory item deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthenticated"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Only admins can delete inventory items"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Inventory item not found"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        // Check if user is admin
        if (!auth()->user()->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $result = $this->inventoryItemService->delete($id);

        if (!$result) {
            return response()->json(['message' => 'Inventory item not found'], 404);
        }

        return response()->json(['message' => 'Inventory item deleted successfully']);
    }
}
